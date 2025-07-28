<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MediaFile;
use Illuminate\Support\Facades\Log;

class CleanupExpiredMedia extends Command
{
    protected $signature = 'media:cleanup-expired';
    protected $description = 'Clean up expired media files';

    public function handle()
    {
        $this->info('Starting cleanup of expired media files...');
        
        $deletedCount = MediaFile::cleanupExpired();
        
        $this->info("Cleanup completed. Deleted {$deletedCount} expired files.");
        Log::info("Media cleanup completed. Deleted {$deletedCount} expired files.");
        
        return 0;
    }
}