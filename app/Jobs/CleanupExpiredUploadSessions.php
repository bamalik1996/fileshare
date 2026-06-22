<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\UploadChunk;
use App\Models\UploadSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Delete stale upload sessions and their chunk files (Requirement 9.8).
 *
 * Runs hourly via the scheduler (task 14.3). A session is stale when
 * `first_chunk_at` is more than 24 hours in the past and `completed_at`
 * is still null.
 */
class CleanupExpiredUploadSessions implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $cutoff = now()->subHours(24);

        $sessions = UploadSession::query()
            ->whereNull('completed_at')
            ->whereNotNull('first_chunk_at')
            ->where('first_chunk_at', '<', $cutoff)
            ->get();

        $deleted = 0;

        foreach ($sessions as $session) {
            $this->purgeSession($session);
            $deleted++;
        }

        if ($deleted > 0) {
            Log::info('CleanupExpiredUploadSessions: purged stale sessions', [
                'count' => $deleted,
            ]);
        }
    }

    private function purgeSession(UploadSession $session): void
    {
        $chunks = UploadChunk::query()
            ->where('session_id', $session->id)
            ->get();

        foreach ($chunks as $chunk) {
            if ($chunk->stored_path !== '') {
                Storage::disk('local')->delete($chunk->stored_path);
            }
        }

        Storage::disk('local')->deleteDirectory('chunks/' . $session->uuid);

        UploadChunk::query()->where('session_id', $session->id)->delete();
        $session->delete();
    }
}
