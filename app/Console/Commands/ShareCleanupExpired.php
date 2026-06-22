<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Room;
use App\Models\Share;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Hourly cleanup pass for the `shares` aggregate and the `rooms` table
 * (design.md > Components and Interfaces > 3 > Scheduled cleanup,
 *  tasks 3.4 and 12.3 in tasks.md).
 *
 * Share deletion criteria (Requirements 3.6, 3.7, 16.7):
 *
 *   1. `expires_at < now() - 1 hour`     - more than 1 hour past expiry,
 *      i.e. outside the on-read fallback window enforced by
 *      {@see \App\Services\ExpiryManager::enforceOnRead()}.
 *   2. NOT favourited                    - favourited shares are exempt
 *      from auto-expiry while the favourite mark remains. We treat a
 *      share as "favourited" when *either*:
 *        a. `shares.is_favourite = true` (owner-side flag for
 *           account-owned shares), OR
 *        b. there is at least one matching row in `account_favourites`
 *           (any account has favourited this share via the pivot).
 *
 * Room deletion criteria (Requirement 7.6, design.md > Components and
 * Interfaces > 7):
 *
 *   1. `expires_at <= now()`             - room has reached its expiry,
 *      AND
 *   2. `last_activity_at <= now()->subSeconds(60)` OR
 *      `last_activity_at IS NULL`        - every device that previously
 *      joined has either explicitly disconnected or has been inactive
 *      for at least 60 seconds (a NULL value means no device has ever
 *      registered presence, so there is nothing keeping the room alive).
 *
 * For each share that satisfies both conditions, we cascade its media
 * via Spatie's `clearMediaCollection()` before deleting the share row, so
 * the physical files on disk are removed in the same operation. Room
 * deletion follows the same pattern: any share owned by the room
 * (`owner_type='room', owner_id=room.id`) has its media cleared and the
 * row removed, after which the room itself is deleted.
 *
 * Failures are logged per share or per room and do not abort the run;
 * the command always exits 0 unless something prevents it from starting
 * at all. This keeps the scheduler healthy and lets the next hourly run
 * pick up any rows that survived a transient failure.
 */
class ShareCleanupExpired extends Command
{
    /**
     * Artisan signature. Kept simple - no options - because the cleanup
     * is purely deterministic given the current time.
     */
    protected $signature = 'shares:cleanup-expired';

    protected $description = 'Delete shares expired more than 1 hour ago and inactive expired rooms, cascading their media.';

    public function handle(): int
    {
        $now = Carbon::now();

        $sharesDeleted = $this->cleanupShares($now);
        $roomsDeleted = $this->cleanupRooms($now);

        $message = sprintf(
            'Share cleanup completed. Deleted %d share(s), %d room(s).',
            $sharesDeleted,
            $roomsDeleted,
        );
        $this->info($message);
        Log::info($message, [
            'shares_deleted' => $sharesDeleted,
            'rooms_deleted' => $roomsDeleted,
            'now' => $now->toIso8601String(),
        ]);

        return self::SUCCESS;
    }

    /**
     * Delete shares whose expiry is more than one hour in the past and
     * which are not favourited. Returns the number of rows deleted.
     */
    private function cleanupShares(Carbon $now): int
    {
        // The grace window in Requirement 3.7 is "more than 1 hour past
        // expiry". Using `<` against `now()->subHour()` matches that
        // boundary: a share that expired exactly one hour ago is still
        // inside the grace window and is left for the next pass.
        $cutoff = $now->copy()->subHour();

        $this->info("Starting share cleanup. Cutoff: {$cutoff->toIso8601String()}");

        // Stream rows in batches so the command stays bounded in memory
        // even when an outage backs up many expired shares. `chunkById`
        // is safe across delete operations because it iterates by primary
        // key with a moving cursor instead of OFFSET.
        $deleted = 0;
        $failed = 0;

        Share::query()
            ->where('expires_at', '<', $cutoff)
            ->where('is_favourite', false)
            ->whereNotIn('id', function ($sub): void {
                // Exclude any share that has been favourited via the
                // account_favourites pivot (Requirement 16.7).
                $sub->select('share_id')->from('account_favourites');
            })
            ->orderBy('id')
            ->chunkById(200, function ($shares) use (&$deleted, &$failed): void {
                foreach ($shares as $share) {
                    /** @var Share $share */
                    if ($this->deleteShareWithMedia($share)) {
                        $deleted++;
                    } else {
                        $failed++;
                    }
                }
            });

        if ($failed > 0) {
            $this->info("Share pass: {$deleted} deleted, {$failed} failure(s) logged.");
        }

        return $deleted;
    }

    /**
     * Delete rooms that have expired AND have no recent activity.
     *
     * A room is eligible when its `expires_at` is at or before `$now`
     * AND its `last_activity_at` is either null (never visited) or at
     * or before `$now - 60s`. The 60s threshold matches the inactivity
     * proxy described in Requirement 7.6: every device must have either
     * explicitly disconnected or been inactive for >= 60 seconds.
     *
     * Returns the number of rooms deleted.
     */
    private function cleanupRooms(Carbon $now): int
    {
        $inactivityCutoff = $now->copy()->subSeconds(60);

        $this->info(sprintf(
            'Starting room cleanup. Expired before: %s, last activity at or before: %s.',
            $now->toIso8601String(),
            $inactivityCutoff->toIso8601String(),
        ));

        $deleted = 0;
        $failed = 0;

        Room::query()
            ->where('expires_at', '<=', $now)
            ->where(function ($q) use ($inactivityCutoff): void {
                // NULL means no device has ever registered presence on
                // the room, so there is nothing keeping it alive.
                $q->whereNull('last_activity_at')
                    ->orWhere('last_activity_at', '<=', $inactivityCutoff);
            })
            ->orderBy('id')
            ->chunkById(200, function ($rooms) use (&$deleted, &$failed): void {
                foreach ($rooms as $room) {
                    /** @var Room $room */
                    if ($this->deleteRoomWithShares($room)) {
                        $deleted++;
                    } else {
                        $failed++;
                    }
                }
            });

        if ($failed > 0) {
            $this->info("Room pass: {$deleted} deleted, {$failed} failure(s) logged.");
        }

        return $deleted;
    }

    /**
     * Cascade a share's media via Spatie's `clearMediaCollection()` and
     * then delete the share row. Returns true on success, false on any
     * failure (which is also logged for operator visibility).
     */
    private function deleteShareWithMedia(Share $share): bool
    {
        try {
            // Cascade media first so that, even if the row delete fails,
            // files on disk go with the attempt rather than being
            // orphaned.
            $share->clearMediaCollection();
            $share->delete();

            return true;
        } catch (Throwable $e) {
            Log::warning('ShareCleanupExpired: failed to delete share', [
                'share_id' => $share->id,
                'share_uuid' => $share->uuid,
                'reason' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete a Room and any Shares it owns.
     *
     * Each owned share's media is cleared via the same Spatie cascade
     * used for the share-cleanup branch, so the on-disk files belonging
     * to a room's content go with the room.
     */
    private function deleteRoomWithShares(Room $room): bool
    {
        try {
            // Clear any shares owned by this room. We query the
            // polymorphic pair directly rather than via the `shares()`
            // relation so the cleanup is independent of relationship
            // scoping (kept consistent in case the morph alias is ever
            // changed).
            Share::query()
                ->where('owner_type', Share::OWNER_TYPE_ROOM)
                ->where('owner_id', (string) $room->id)
                ->orderBy('id')
                ->chunkById(200, function ($shares): void {
                    foreach ($shares as $share) {
                        /** @var Share $share */
                        // Even if a single share fails to clear we
                        // continue with the rest and surface the
                        // failure via the warning log; the room-level
                        // delete below will still be attempted.
                        $this->deleteShareWithMedia($share);
                    }
                });

            $room->delete();

            Log::info('ShareCleanupExpired: deleted expired room', [
                'room_id' => $room->id,
                'room_code' => $room->code,
                'expires_at' => optional($room->expires_at)->toIso8601String(),
                'last_activity_at' => optional($room->last_activity_at)->toIso8601String(),
            ]);

            return true;
        } catch (Throwable $e) {
            Log::warning('ShareCleanupExpired: failed to delete room', [
                'room_id' => $room->id,
                'room_code' => $room->code ?? null,
                'reason' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
