<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ShareNotification model (design.md > Data Models > share_notifications).
 *
 * Backs Requirement 11 (Pre-expiry notifications). One row per (share,
 * cycle, channel) tuple, where a "cycle" is the value of the share's
 * `expires_at` at arming time. The composite unique key
 * `(share_id, cycle_expires_at, channel)` enforces the "at most one
 * reminder per Share per channel per expiry cycle" rule (Requirement 11.3).
 *
 * `send_at` is `cycle_expires_at - 60 minutes` (or `now()` for shares
 * already inside the 60-minute window when armed, per Requirement 11.5).
 * `sent_at` records actual delivery (or terminal failure, per
 * Requirement 11.7) so the worker treats the row as completed even when
 * the second retry fails.
 *
 * Only `created_at` exists at the schema level; `updated_at` is disabled.
 *
 * @property int $share_id
 * @property \Illuminate\Support\Carbon $cycle_expires_at
 * @property string $channel
 * @property \Illuminate\Support\Carbon $send_at
 * @property ?\Illuminate\Support\Carbon $sent_at
 * @property int $failure_count
 * @property ?\Illuminate\Support\Carbon $created_at
 */
class ShareNotification extends Model
{
    public const CHANNEL_BROWSER = 'browser';
    public const CHANNEL_EMAIL = 'email';

    /**
     * Schema only stores `created_at`.
     */
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'share_id',
        'cycle_expires_at',
        'channel',
        'send_at',
        'sent_at',
        'failure_count',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'cycle_expires_at' => 'datetime',
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
        'failure_count' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Owning Share. Cascade delete is enforced by the FK so cancelling a
     * share automatically tears down pending reminder rows
     * (Requirement 11.8).
     */
    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class);
    }

    /**
     * Pending = `sent_at` is null, i.e. the worker has not yet delivered
     * (or marked terminally failed) this row.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('sent_at');
    }
}
