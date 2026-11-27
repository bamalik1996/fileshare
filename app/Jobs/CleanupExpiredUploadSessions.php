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
 *
 * It also clears chunk directories left behind by completed uploads (the
 * assembled file already lives in the media library) and directories with
 * no session row at all.
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

        $cleared = $this->clearLeftoverChunkDirectories();

        if ($cleared > 0) {
            Log::info('CleanupExpiredUploadSessions: cleared leftover chunk directories', [
                'count' => $cleared,
            ]);
        }
    }

    /**
     * Remove chunk directories that no longer serve an in-flight upload:
     *
     *  - the session completed more than an hour ago (the grace period
     *    covers queued assembly, which marks the session completed before
     *    the job concatenates the chunks);
     *  - no session row exists and the directory is older than 24 hours.
     */
    private function clearLeftoverChunkDirectories(): int
    {
        $disk = Storage::disk('local');
        $completedBefore = now()->subHour();
        $orphanBefore = now()->subHours(24)->getTimestamp();
        $cleared = 0;

        foreach ($disk->directories('chunks') as $directory) {
            $uuid = basename($directory);
            $session = UploadSession::query()->where('uuid', $uuid)->first();

            if ($session !== null) {
                if ($session->completed_at === null || $session->completed_at->gte($completedBefore)) {
                    continue;
                }
            } elseif (@filemtime($disk->path($directory)) > $orphanBefore) {
                continue;
            }

            $disk->deleteDirectory($directory);
            $cleared++;
        }

        return $cleared;
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
