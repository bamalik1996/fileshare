<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ExpiryManager;
use App\Services\PublicGalleryService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public read-only gallery at GET /p/{slug} (Requirement 17).
 */
class PublicShareController extends Controller
{
    public function __construct(
        private readonly PublicGalleryService $publicGallery,
        private readonly ExpiryManager $expiryManager,
        private readonly \App\Services\AnalyticsService $analytics,
    ) {
    }

    public function show(Request $request, string $slug): View|Response
    {
        $share = $this->publicGallery->findBySlug($slug);

        if ($share === null) {
            return response()->view('errors.404', [], 404, [
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
        }

        try {
            $this->expiryManager->enforceOnRead($share);
        } catch (\App\Exceptions\ShareExpiredException) {
            return response()->view('errors.404', [], 404, [
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
        }

        // Revoked links stop working immediately (SaaS features, Phase 2).
        // Respond with the same generic 404 used for unknown slugs so a
        // revoked slug is indistinguishable from one that never existed.
        if ($share->isRevoked()) {
            return response()->view('errors.404', [], 404, [
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
        }

        $share->increment('public_view_count');

        // Detailed per-visit analytics (device, country, referrer) on top of
        // the lightweight aggregate counter above.
        $this->analytics->recordView($share, $request);

        $media = $share->getMedia()->map(fn ($m) => [
            'uuid'         => $m->uuid,
            'name'         => $m->file_name,
            'mime_type'    => $m->mime_type,
            'size'         => $m->size,
            'original_url' => $m->getUrl(),
            'preview_url'  => $m->getFullUrl(),
        ]);

        return response()
            ->view('share.public', [
                'share'    => $share,
                'media'    => $media,
                'branding' => $share->brandingPayload(),
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
