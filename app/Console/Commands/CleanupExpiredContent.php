<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SharedText;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CleanupExpiredContent extends Command
{
    protected $signature = 'cleanup:expired-content';
    protected $description = 'Clean up expired shared texts and media files';

    public function handle()
    {
        $this->info('Starting cleanup of expired content...');
        
        // Delete expired shared texts
        $expiredTexts = SharedText::where('expires_at', '<', Carbon::now())->get();
        
        foreach ($expiredTexts as $text) {
            // Delete associated media files
            $text->clearMediaCollection();
            $text->delete();
        }
        
        // Delete texts that haven't been accessed for more than 1 hour
        $inactiveTexts = SharedText::where('last_accessed', '<', Carbon::now()->subHour())
            ->whereNotNull('last_accessed')
            ->get();
            
        foreach ($inactiveTexts as $text) {
            $text->clearMediaCollection();
            $text->delete();
        }
        
        $totalDeleted = $expiredTexts->count() + $inactiveTexts->count();
        
        $this->info("Cleanup completed. Deleted {$totalDeleted} expired entries.");
        Log::info("Cleanup completed. Deleted {$totalDeleted} expired entries.");
        
        return 0;
    }
}