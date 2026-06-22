<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Room;
use App\Models\RoomPresence;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Prune stale room presence rows (Requirement 10.5).
 */
class CleanupRoomPresence implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $cutoff = Carbon::now()->subSeconds(30);

        RoomPresence::query()
            ->where('last_seen_at', '<', $cutoff)
            ->delete();

        Room::query()->chunkById(100, function ($rooms) use ($cutoff): void {
            foreach ($rooms as $room) {
                $latest = RoomPresence::query()
                    ->where('room_id', $room->id)
                    ->max('last_seen_at');

                if ($latest === null) {
                    continue;
                }

                $room->forceFill([
                    'last_activity_at' => Carbon::parse($latest),
                ])->saveQuietly();
            }
        });
    }
}
