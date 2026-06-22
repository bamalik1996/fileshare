<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Principal\Principal;
use App\Http\Middleware\SharePasswordGate;
use App\Support\IpAddressMatcher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Share aggregate (design.md > Data Models > shares).
 *
 * Replaces the per-feature `shared_texts` / `media_files` tables for new
 * code paths. Owns text content and media files for a single owner, where
 * the owner is one of {IP literal, Room, Account} via the polymorphic
 * (`owner_type`, `owner_id`) pair.
 *
 * Spatie HasMedia integration mirrors the existing {@see MediaFile} model
 * so legacy and new code paths stay interoperable during the deprecation
 * window described in design.md > Data Models > Backward-compat tables.
 *
 * @property string $uuid
 * @property string $owner_type   One of `ip`, `room`, `account`.
 * @property string $owner_id
 * @property ?string $text_content
 * @property ?string $markdown_source
 * @property ?string $password_hash
 * @property \Illuminate\Support\Carbon $expires_at
 * @property ?string $public_slug
 * @property int $public_view_count
 * @property bool $is_e2ee
 * @property bool $is_favourite
 */
class Share extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * Owner-type tokens persisted in `shares.owner_type`. Kept in sync with
     * {@see Principal::type()} and the morph map registered in
     * {@see \App\Providers\AppServiceProvider}.
     */
    public const OWNER_TYPE_IP = 'ip';
    public const OWNER_TYPE_ROOM = 'room';
    public const OWNER_TYPE_ACCOUNT = 'account';

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'owner_type',
        'owner_id',
        'text_content',
        'markdown_source',
        'password_hash',
        'expires_at',
        'public_slug',
        'public_view_count',
        'is_e2ee',
        'is_favourite',
        'notify_browser',
        'notify_email',
        'notify_email_address',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'expires_at' => 'datetime',
        'is_e2ee' => 'bool',
        'is_favourite' => 'bool',
        'notify_browser' => 'bool',
        'notify_email' => 'bool',
        'public_view_count' => 'integer',
    ];

    /** @var array<string, string> */
    protected $attributes = [
        'public_view_count' => 0,
        'is_e2ee' => false,
        'is_favourite' => false,
        'notify_browser' => false,
        'notify_email' => false,
    ];

    /**
     * Auto-populate `uuid` so callers do not have to remember.
     */
    protected static function booted(): void
    {
        static::creating(function (self $share): void {
            if (empty($share->uuid)) {
                $share->uuid = (string) Str::uuid();
            }
        });

        static::deleting(function (self $share): void {
            if (class_exists(\App\Services\NotificationService::class)) {
                app(\App\Services\NotificationService::class)->cancelFor($share);
            }
        });
    }

    /**
     * Polymorphic owner relation.
     *
     * Resolves to:
     *  - `null` when `owner_type === 'ip'` (IP owners are pure value objects
     *    and intentionally have no backing Eloquent model)
     *  - a {@see Room} when `owner_type === 'room'`
     *  - an {@see Account} when `owner_type === 'account'`
     *
     * The morph aliases (`ip`, `room`, `account`) are registered in the
     * application service provider so that Eloquent can map the persisted
     * owner_type token back to the right concrete class.
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Active = not expired. Excludes the cleanup grace window deliberately;
     * the grace window is enforced separately by `ShareCleanupExpired`.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', Carbon::now());
    }

    /**
     * Restrict to shares owned by the given Principal.
     *
     * Uses both columns of the composite `(owner_type, owner_id, expires_at)`
     * index so that owner-scoped lookups remain index-only.
     */
    public function scopeOwnedBy(Builder $query, Principal $principal): Builder
    {
        return $query
            ->where('owner_type', $principal->type())
            ->where('owner_id', $principal->identifier());
    }

    /**
     * True when the share's expiry timestamp is at or before the current
     * instant. Mirrors the read-time check in
     * `ExpiryManager::enforceOnRead()` (Requirements 3.4, 3.8).
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->lessThanOrEqualTo(Carbon::now());
    }

    public function hasPassword(): bool
    {
        return is_string($this->password_hash) && $this->password_hash !== '';
    }

    public function ownedByPrincipal(Principal $principal): bool
    {
        if ($this->owner_type !== $principal->type()) {
            return false;
        }

        if ($this->owner_type === self::OWNER_TYPE_IP) {
            return IpAddressMatcher::sameHost(
                (string) $this->owner_id,
                $principal->identifier(),
            );
        }

        return (string) $this->owner_id === (string) $principal->identifier();
    }

    /**
     * Whether the current request may subscribe to this share's private
     * broadcast channel (owner, open share, or verified password session).
     */
    public function allowsBroadcastSubscription(Request $request): bool
    {
        if (! $this->hasPassword()) {
            return true;
        }

        if ($this->ownedByPrincipal($request->principal())) {
            return true;
        }

        if (! $request->hasSession()) {
            return false;
        }

        $map = $request->session()->get(SharePasswordGate::SESSION_KEY, []);

        return is_array($map) && (($map[$this->id] ?? false) === true);
    }

    /**
     * Pre-expiry reminder rows (Requirement 11). At most one per
     * (channel, cycle_expires_at) tuple thanks to the composite unique
     * index on `share_notifications`. Cascade delete on the FK side
     * cancels pending reminders when the Share is destroyed
     * (Requirement 11.8).
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(ShareNotification::class);
    }

    /**
     * Draft chunked-upload session attached to this Share (Requirement 9).
     * Modelled `HasOne` because the upload page binds at most one
     * in-progress session to the active Share at a time; legacy single-
     * request uploads have no session row.
     */
    public function uploadSession(): HasOne
    {
        return $this->hasOne(UploadSession::class);
    }

    /**
     * Accounts that have favourited this Share (inverse of
     * {@see Account::favourites()}, Requirement 16.7-8).
     */
    public function favouritedBy(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'account_favourites')
            ->withPivot('created_at');
    }
}
