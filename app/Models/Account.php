<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Account model (design.md > Data Models > accounts).
 *
 * Backs Requirement 16 (optional user accounts). Lives alongside the existing
 * {@see User} model: User stays as Laravel's default scaffold so any future
 * admin/dev tooling remains undisturbed, while Account is the authentication
 * principal for the AirToShareA share/account flows. Both extend
 * {@see Authenticatable} but use separate tables (`users` vs `accounts`).
 *
 * The password is stored in `password_hash` (bcrypt cost ≥ 12 enforced at the
 * service layer per Requirement 16.2). Laravel's password broker normally
 * looks up `password`, so we override {@see getAuthPasswordName()} to point at
 * `password_hash` instead. The `hashed` cast keeps `Auth::attempt(['password'
 * => 'plain'])` working when a custom guard is configured against this model.
 *
 * @property string $email
 * @property string $password_hash
 * @property ?\Illuminate\Support\Carbon $email_verified_at
 * @property ?string $remember_token
 */
class Account extends Authenticatable implements MustVerifyEmail
{
    use MustVerifyEmailTrait;
    use Notifiable;

    /** @var list<string> */
    protected $fillable = [
        'email',
        'password_hash',
        'email_verified_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_hash' => 'hashed',
    ];

    /**
     * Tell the auth subsystem the password column is `password_hash`,
     * not the default `password`. This makes `Auth::attempt()`,
     * password-broker resets, and remember-me reads address the right
     * column without any custom UserProvider.
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /**
     * Required by {@see Authenticatable} when the column is non-default.
     * Returning the column value explicitly keeps every Laravel auth path
     * (Hash::check, password broker, sanctum) consistent regardless of
     * whether they call `getAuthPassword()` or read the attribute.
     */
    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    /**
     * Shares owned by this Account via the polymorphic owner pair on
     * `shares` (`owner_type='account'`, `owner_id=account.id`). The morph
     * alias `account` is registered in {@see \App\Providers\AppServiceProvider}.
     */
    public function shares(): HasMany
    {
        return $this->hasMany(Share::class, 'owner_id')
            ->where('owner_type', Share::OWNER_TYPE_ACCOUNT);
    }

    /**
     * Favourited shares for this Account (Requirement 16.7-8).
     *
     * Persisted via the `account_favourites` pivot. The application layer
     * caps the row count at 50 per Account; the schema enforces uniqueness
     * via the composite key `(account_id, share_id)`.
     */
    public function favourites(): BelongsToMany
    {
        // The pivot only has `created_at` (no `updated_at`), so we declare
        // the single column explicitly instead of using `withTimestamps()`.
        return $this->belongsToMany(Share::class, 'account_favourites')
            ->withPivot('created_at');
    }

    /**
     * API keys minted by this Account (Requirement 18).
     *
     * Cascade delete on the FK side ensures revocation when the Account is
     * removed (Requirement 16.11).
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }
}
