<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\ClipboardUpdated;
use App\Jobs\BroadcastClipboardUpdated;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Clipboard sync across devices in the same Room (Requirement 10).
 */
class ClipboardSyncService
{
    public const MAX_CLIPBOARD_CHARS = 500_000;

    /**
     * Persist clipboard text with last-write-wins semantics and broadcast.
     *
     * @throws ValidationException When text exceeds {@see self::MAX_CLIPBOARD_CHARS}.
     */
    public function update(Room $room, string $text, ?Carbon $clientTimestamp = null): bool
    {
        if (mb_strlen($text) > self::MAX_CLIPBOARD_CHARS) {
            throw ValidationException::withMessages([
                'text' => ['Clipboard text may not exceed 500,000 characters.'],
            ]);
        }

        $timestamp = $clientTimestamp ?? Carbon::now();

        $affected = DB::table('rooms')
            ->where('id', $room->id)
            ->where(function ($query) use ($timestamp): void {
                $query->whereNull('clipboard_updated_at')
                    ->orWhere('clipboard_updated_at', '<', $timestamp);
            })
            ->update([
                'clipboard_text'       => $text,
                'clipboard_updated_at' => $timestamp,
                'last_activity_at'   => Carbon::now(),
                'updated_at'           => Carbon::now(),
            ]);

        if ($affected === 0) {
            return false;
        }

        $room->refresh();

        BroadcastClipboardUpdated::dispatch($room->id, $text, $timestamp->toIso8601String());

        return true;
    }

    /**
     * Record or refresh device presence for a Room.
     */
    public function touchPresence(Room $room, string $deviceId): void
    {
        $now = Carbon::now();

        DB::table('room_presences')->upsert(
            [
                [
                    'room_id'      => $room->id,
                    'device_id'    => $deviceId,
                    'last_seen_at' => $now,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ],
            ],
            ['room_id', 'device_id'],
            ['last_seen_at', 'updated_at'],
        );

        $room->forceFill(['last_activity_at' => $now])->saveQuietly();
    }
}
