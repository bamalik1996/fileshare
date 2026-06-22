<?php

declare(strict_types=1);

namespace App\Services\Scanning;

interface ScanBackend
{
    /**
     * @return array{status: string, payload?: array}
     */
    public function scanFile(string $path): array;
}
