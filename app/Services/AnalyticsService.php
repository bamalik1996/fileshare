<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Share;
use App\Models\ShareEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Records and aggregates Share analytics events (SaaS features, Phase 1).
 *
 * Design goals:
 *  - Never break the hosting request. Every write path is wrapped so a
 *    logging failure can never bubble up into a download or page view.
 *  - Never persist PII. The client IP is salted with the application key
 *    and truncated to a short hash so unique-visitor counts work without
 *    the table ever holding a raw address.
 *  - No network calls. Country is read from edge/proxy headers when the
 *    deployment provides them; user-agent parsing is a dependency-free
 *    regex pass. This keeps recording cheap enough to run inline.
 */
class AnalyticsService
{
    /**
     * Record a view against a Share (e.g. a public gallery hit).
     */
    public function recordView(Share $share, Request $request): void
    {
        $this->record($share, ShareEvent::EVENT_VIEW, $request);
    }

    /**
     * Record a download. Resolves the owning Share from the Spatie media
     * row; silently no-ops for media that is not backed by a Share (e.g.
     * legacy IP-guest MediaFile uploads), since those have no dashboard.
     */
    public function recordDownloadForMedia(Media $media, Request $request): void
    {
        try {
            $model = $media->model;

            if (! $model instanceof Share) {
                return;
            }

            $this->record($model, ShareEvent::EVENT_DOWNLOAD, $request, (string) $media->uuid);
        } catch (\Throwable $e) {
            Log::warning('Analytics download record failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Core writer. Guarded so analytics never interferes with the response.
     */
    public function record(Share $share, string $eventType, Request $request, ?string $mediaUuid = null): void
    {
        try {
            $agent = $this->parseUserAgent((string) $request->userAgent());

            ShareEvent::query()->create([
                'share_id'   => $share->id,
                'media_uuid' => $mediaUuid,
                'event_type' => $eventType,
                'ip_hash'    => $this->hashIp((string) $request->ip()),
                'country'    => $this->resolveCountry($request),
                'city'       => $this->resolveCity($request),
                'device'     => $agent['device'],
                'browser'    => $agent['browser'],
                'os'         => $agent['os'],
                'referrer'   => $this->cleanReferrer($request->headers->get('referer')),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Analytics event record failed', [
                'share_id'   => $share->id ?? null,
                'event_type' => $eventType,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Aggregated counters for a single Share, used by the dashboard summary.
     *
     * @return array{views:int, downloads:int, unique_visitors:int, last_event_at:?string}
     */
    public function summaryFor(Share $share): array
    {
        $rows = ShareEvent::query()
            ->selectRaw('event_type, COUNT(*) as c, COUNT(DISTINCT ip_hash) as u, MAX(created_at) as last_at')
            ->where('share_id', $share->id)
            ->groupBy('event_type')
            ->get();

        $views = 0;
        $downloads = 0;
        $uniques = [];
        $lastAt = null;

        foreach ($rows as $row) {
            if ($row->event_type === ShareEvent::EVENT_VIEW) {
                $views = (int) $row->c;
            } elseif ($row->event_type === ShareEvent::EVENT_DOWNLOAD) {
                $downloads = (int) $row->c;
            }
            $uniques[] = (int) $row->u;
            if ($row->last_at !== null && ($lastAt === null || $row->last_at > $lastAt)) {
                $lastAt = $row->last_at;
            }
        }

        return [
            'views'           => $views,
            'downloads'       => $downloads,
            'unique_visitors' => count($uniques) ? max($uniques) : 0,
            'last_event_at'   => $lastAt,
        ];
    }

    /**
     * Bulk per-Share counters keyed by share_id, for the list view.
     *
     * @param  iterable<int>  $shareIds
     * @return array<int, array{views:int, downloads:int}>
     */
    public function summaryForShareIds(iterable $shareIds): array
    {
        $ids = collect($shareIds)->map(fn ($id) => (int) $id)->filter()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $rows = ShareEvent::query()
            ->selectRaw('share_id, event_type, COUNT(*) as c')
            ->whereIn('share_id', $ids->all())
            ->groupBy('share_id', 'event_type')
            ->get();

        $out = [];
        foreach ($ids as $id) {
            $out[$id] = ['views' => 0, 'downloads' => 0];
        }

        foreach ($rows as $row) {
            $sid = (int) $row->share_id;
            if ($row->event_type === ShareEvent::EVENT_VIEW) {
                $out[$sid]['views'] = (int) $row->c;
            } elseif ($row->event_type === ShareEvent::EVENT_DOWNLOAD) {
                $out[$sid]['downloads'] = (int) $row->c;
            }
        }

        return $out;
    }

    /**
     * Detailed analytics payload for one Share's drill-down panel.
     *
     * @return array{
     *     summary: array{views:int, downloads:int, unique_visitors:int, last_event_at:?string},
     *     timeline: array<int, array{date:string, views:int, downloads:int}>,
     *     countries: array<int, array{label:string, count:int}>,
     *     devices: array<int, array{label:string, count:int}>,
     *     browsers: array<int, array{label:string, count:int}>,
     *     recent: array<int, array<string, mixed>>
     * }
     */
    public function detailFor(Share $share, int $days = 30): array
    {
        $since = now()->subDays($days)->startOfDay();

        $timeline = ShareEvent::query()
            ->selectRaw('DATE(created_at) as d, event_type, COUNT(*) as c')
            ->where('share_id', $share->id)
            ->where('created_at', '>=', $since)
            ->groupBy('d', 'event_type')
            ->orderBy('d')
            ->get();

        $byDate = [];
        foreach ($timeline as $row) {
            $date = (string) $row->d;
            $byDate[$date] ??= ['date' => $date, 'views' => 0, 'downloads' => 0];
            if ($row->event_type === ShareEvent::EVENT_VIEW) {
                $byDate[$date]['views'] = (int) $row->c;
            } elseif ($row->event_type === ShareEvent::EVENT_DOWNLOAD) {
                $byDate[$date]['downloads'] = (int) $row->c;
            }
        }

        $recent = ShareEvent::query()
            ->where('share_id', $share->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (ShareEvent $e) => [
                'event_type' => $e->event_type,
                'media_uuid' => $e->media_uuid,
                'country'    => $e->country,
                'city'       => $e->city,
                'device'     => $e->device,
                'browser'    => $e->browser,
                'os'         => $e->os,
                'referrer'   => $e->referrer,
                'at'         => optional($e->created_at)->toIso8601String(),
                'at_human'   => optional($e->created_at)->diffForHumans(),
            ])
            ->all();

        return [
            'summary'   => $this->summaryFor($share),
            'timeline'  => array_values($byDate),
            'countries' => $this->topBreakdown($share, 'country', 8),
            'devices'   => $this->topBreakdown($share, 'device', 5),
            'browsers'  => $this->topBreakdown($share, 'browser', 6),
            'recent'    => $recent,
        ];
    }

    /**
     * @return array<int, array{label:string, count:int}>
     */
    private function topBreakdown(Share $share, string $column, int $limit): array
    {
        return ShareEvent::query()
            ->selectRaw("COALESCE($column, 'Unknown') as label, COUNT(*) as c")
            ->where('share_id', $share->id)
            ->groupBy('label')
            ->orderByDesc('c')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['label' => (string) $row->label, 'count' => (int) $row->c])
            ->all();
    }

    /**
     * Salted + truncated IP hash. Returns null for an empty IP so we never
     * store a meaningful fingerprint for an unknown client.
     */
    private function hashIp(string $ip): ?string
    {
        $ip = trim($ip);
        if ($ip === '') {
            return null;
        }

        $salt = (string) config('app.key');

        return substr(hash('sha256', $salt . '|' . $ip), 0, 32);
    }

    private function resolveCountry(Request $request): ?string
    {
        // Common edge/proxy country headers (Cloudflare, some CDNs, GCP LB).
        foreach (['CF-IPCountry', 'X-Country-Code', 'X-AppEngine-Country'] as $header) {
            $value = $request->headers->get($header);
            if (is_string($value) && strlen($value) === 2 && ctype_alpha($value)) {
                $value = strtoupper($value);
                if ($value !== 'XX') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function resolveCity(Request $request): ?string
    {
        $value = $request->headers->get('CF-IPCity') ?? $request->headers->get('X-AppEngine-City');

        if (is_string($value) && $value !== '') {
            return Str::limit($value, 115, '');
        }

        return null;
    }

    private function cleanReferrer(?string $referrer): ?string
    {
        if (! is_string($referrer) || $referrer === '') {
            return null;
        }

        return Str::limit($referrer, 250, '');
    }

    /**
     * Dependency-free user-agent classification.
     *
     * @return array{device:string, browser:string, os:string}
     */
    private function parseUserAgent(string $ua): array
    {
        if ($ua === '') {
            return ['device' => 'Unknown', 'browser' => 'Unknown', 'os' => 'Unknown'];
        }

        return [
            'device'  => $this->detectDevice($ua),
            'browser' => $this->detectBrowser($ua),
            'os'      => $this->detectOs($ua),
        ];
    }

    private function detectDevice(string $ua): string
    {
        if (preg_match('/iPad|Tablet|PlayBook|Nexus 7|Nexus 10/i', $ua)) {
            return 'Tablet';
        }

        if (preg_match('/Mobile|iPhone|Android.*Mobile|Windows Phone|BlackBerry|IEMobile/i', $ua)) {
            return 'Mobile';
        }

        if (preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit/i', $ua)) {
            return 'Bot';
        }

        return 'Desktop';
    }

    private function detectBrowser(string $ua): string
    {
        $map = [
            'Edg'       => 'Edge',
            'OPR'       => 'Opera',
            'Opera'     => 'Opera',
            'SamsungBrowser' => 'Samsung Internet',
            'Chrome'    => 'Chrome',
            'CriOS'     => 'Chrome',
            'Firefox'   => 'Firefox',
            'FxiOS'     => 'Firefox',
            'Safari'    => 'Safari',
            'MSIE'      => 'Internet Explorer',
            'Trident'   => 'Internet Explorer',
        ];

        foreach ($map as $needle => $label) {
            if (stripos($ua, $needle) !== false) {
                return $label;
            }
        }

        return 'Other';
    }

    private function detectOs(string $ua): string
    {
        if (preg_match('/Windows NT/i', $ua)) {
            return 'Windows';
        }
        if (preg_match('/iPhone|iPad|iPod/i', $ua)) {
            return 'iOS';
        }
        if (preg_match('/Android/i', $ua)) {
            return 'Android';
        }
        if (preg_match('/Mac OS X|Macintosh/i', $ua)) {
            return 'macOS';
        }
        if (preg_match('/CrOS/i', $ua)) {
            return 'ChromeOS';
        }
        if (preg_match('/Linux/i', $ua)) {
            return 'Linux';
        }

        return 'Other';
    }
}
