<?php

declare(strict_types=1);

namespace App\Services\Scanning;

use App\Models\MediaScan;

/**
 * Dev/no-op backend: marks uploads clean when ClamAV/VirusTotal unavailable.
 */
class NullScanBackend implements ScanBackend
{
    public function scanFile(string $path): array
    {
        return [
            'status'  => MediaScan::STATUS_CLEAN,
            'payload' => ['backend' => 'null', 'path' => basename($path)],
        ];
    }
}
