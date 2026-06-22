<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Room model (design.md > Data Models > rooms).
 *
 * Backs Requirement 7 (Room codes for cross-network sharing) and Requirement 10
 * (Clipboard sync across devices). The room code is a 6-character string drawn
 * from the alphabet `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (no `O`, `I`, `0`, `1`),
 * persisted in normalised uppercase form for case-insensitive lookups.
 *
 * Wiring to Share: a Room owns at most one Share (the rooms's content) via the
 * polymorphic `(owner_type='room', owner_id=room.id)` pair on the `shares`
 * table. Modelled here as a `HasMany` because Eloquent expresses morphMany
 * relationships uniformly, even when application invariants permit only one
 * row.
 *
 * @property string $code                 Normalised uppercase 6-character code.
 * @property ?string $password_hash
 * @property \Illuminate\Support\Carbon $expires_at
 * @property ?\Illuminate\Support\Carbon $last_activity_at
 * @property ?string $clipboard_text
 * @property ?\Illuminate\Support\Carbon $clipboard_updated_at
 */
class Room extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'code',
        'password_hash',
        'expires_at',
        'last_activity_at',
        'clipboard_text',
        'clipboard_updated_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'expires_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'clipboard_updated_at' => 'datetime',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * Shares owned by this Room.
     *
     * Uses the polymorphic owner relation defined on {@see Share}. The morph
     * alias `room` is registered in {@see \App\Providers\AppServiceProvider}.
     */
    public function shares(): HasMany
    {
        return $this->hasMany(Share::class, 'owner_id')
            ->where('owner_type', Share::OWNER_TYPE_ROOM);
    }

    /**
     * Active = expiry strictly in the future. Mirrors `Share::scopeActive`.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', Carbon::now());
    }

    /**
     * True when expiry has been reached.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null
            && $this->expires_at->lessThanOrEqualTo(Carbon::now());
    }
}
