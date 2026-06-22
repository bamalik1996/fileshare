<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\VirusScanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScanMediaForViruses implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $mediaUuid)
    {
    }

    public function handle(): void
    {
        VirusScanner::make()->scan($this->mediaUuid);
    }
}
