<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ApiKey model (design.md > Data Models > api_keys).
 *
 * Backs Requirement 18 (Public API). The full key is never persisted: only
 * its first 8 plaintext characters land in `key_prefix` (so the lookup index
 * stays cheap) and the bcrypt hash of the full plaintext lands in `key_hash`.
 * `revoked_at` is checked alongside `Hash::check()` in the auth middleware.
 *
 * @property int $account_id
 * @property string $name
 * @property string $key_prefix   First 8 chars of the plaintext (verbatim).
 * @property string $key_hash     bcrypt of the full plaintext.
 * @property ?\Illuminate\Support\Carbon $revoked_at
 * @property ?\Illuminate\Support\Carbon $last_used_at
 */
class ApiKey extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'account_id',
        'name',
        'key_prefix',
        'key_hash',
        'revoked_at',
        'last_used_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'revoked_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    /** @var list<string> */
    protected $hidden = [
        'key_hash',
    ];

    /**
     * Owning Account.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Active = not revoked. Queries for "is this key usable?" should chain
     * `->whereNull('revoked_at')` indirectly via this scope.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    /**
     * True when the key has been revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null
            && $this->revoked_at->lessThanOrEqualTo(Carbon::now());
    }
}
