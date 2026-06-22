<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks device presence in a Room for clipboard sync and cleanup (Req 10.5).
 *
 * @property int $room_id
 * @property string $device_id
 * @property \Illuminate\Support\Carbon $last_seen_at
 */
class RoomPresence extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'room_id',
        'device_id',
        'last_seen_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
