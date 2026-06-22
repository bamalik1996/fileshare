<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MediaScan;
use App\Models\Share;
use App\Services\Scanning\NullScanBackend;
use App\Services\Scanning\ScanBackend;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VirusScanner
{
    public function __construct(private readonly ScanBackend $backend)
    {
    }

    public static function make(): self
    {
        $driver = config('airtoshare.scan_backend', 'null');

        return new self(match ($driver) {
            'clamav', 'virustotal' => new NullScanBackend(), // extend with real backends later
            default => new NullScanBackend(),
        });
    }

    public function queueForMedia(Media $media, ?Share $share = null): MediaScan
    {
        if ($share !== null && $share->is_e2ee) {
            return MediaScan::query()->updateOrCreate(
                ['media_uuid' => $media->uuid],
                [
                    'status'     => MediaScan::STATUS_SKIPPED_E2EE,
                    'backend'    => 'skipped',
                    'retry_count'=> 0,
                    'queued_at'  => now(),
                    'scanned_at' => now(),
                ],
            );
        }

        return MediaScan::query()->updateOrCreate(
            ['media_uuid' => $media->uuid],
            [
                'status'      => MediaScan::STATUS_PENDING,
                'backend'     => config('airtoshare.scan_backend', 'null'),
                'retry_count' => 0,
                'queued_at'   => now(),
            ],
        );
    }

    public function scan(string $mediaUuid): void
    {
        $media = Media::query()->where('uuid', $mediaUuid)->first();
        if ($media === null) {
            return;
        }

        $scan = MediaScan::query()->where('media_uuid', $mediaUuid)->first();
        if ($scan === null) {
            $scan = $this->queueForMedia($media);
        }

        if ($scan->status === MediaScan::STATUS_SKIPPED_E2EE) {
            return;
        }

        $path = $media->getPath();
        if (! is_string($path) || ! file_exists($path)) {
            $this->mark($scan, MediaScan::STATUS_ERROR, ['reason' => 'missing_file']);

            return;
        }

        try {
            $result = $this->backend->scanFile($path);
            $this->mark($scan, $result['status'], $result['payload'] ?? []);
        } catch (\Throwable $e) {
            $retries = (int) $scan->retry_count + 1;
            if ($retries >= 3) {
                $this->mark($scan, MediaScan::STATUS_ERROR, ['reason' => $e->getMessage(), 'retries' => $retries]);
            } else {
                $scan->retry_count = $retries;
                $scan->save();
            }

            Log::warning('VirusScanner: scan failed', [
                'media_uuid' => $mediaUuid,
                'retry'      => $retries,
                'reason'     => $e->getMessage(),
            ]);
        }
    }

    private function mark(MediaScan $scan, string $status, array $payload): void
    {
        $scan->status = $status;
        $scan->result_payload = $payload;
        $scan->scanned_at = now();
        $scan->save();

        Log::info('VirusScanner: completed', [
            'media_uuid' => $scan->media_uuid,
            'status'     => $status,
            'backend'    => $scan->backend,
            'retries'    => $scan->retry_count,
        ]);
    }
}
