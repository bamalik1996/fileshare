<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Account registration, login, logout, and deletion (Requirement 16).
 */
class AccountService
{
    private const MIN_PASSWORD = 8;

    private const MAX_PASSWORD = 128;

    private const BCRYPT_COST = 12;

    /** Grace period before a scheduled account deletion is purged permanently. */
    public const DELETION_GRACE_HOURS = 48;

    /**
     * @throws ValidationException
     */
    public function register(string $email, string $password): Account
    {
        $email = strtolower(trim($email));

        $this->assertValidEmail($email);
        $this->assertValidPassword($password);

        if (Account::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Unable to create account with these credentials.'],
            ]);
        }

        $account = Account::query()->create([
            'email'         => $email,
            'password_hash' => $this->hashPassword($password),
        ]);

        event(new Registered($account));

        return $account;
    }

    /**
     * @throws ValidationException
     */
    public function login(string $email, string $password): Account
    {
        $email = strtolower(trim($email));
        $account = Account::query()->where('email', $email)->first();

        if ($account === null || ! Hash::check($password, $account->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        Auth::guard('account')->login($account, remember: true);

        return $account;
    }

    public function logout(): void
    {
        Auth::guard('account')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    /**
     * Mark the account for permanent deletion after {@see DELETION_GRACE_HOURS}.
     * The account stays usable so the owner can cancel in time.
     */
    public function scheduleDeletion(Account $account): void
    {
        $account->deletion_scheduled_at = now()->addHours(self::DELETION_GRACE_HOURS);
        $account->save();
    }

    public function cancelDeletion(Account $account): void
    {
        if ($account->deletion_scheduled_at === null) {
            return;
        }

        $account->deletion_scheduled_at = null;
        $account->save();
    }

    /**
     * Permanently remove accounts whose grace period has elapsed.
     *
     * @return int Number of accounts purged
     */
    public function purgeScheduledDeletions(): int
    {
        $due = Account::query()
            ->whereNotNull('deletion_scheduled_at')
            ->where('deletion_scheduled_at', '<=', now())
            ->get();

        $purged = 0;
        foreach ($due as $account) {
            $this->deleteAccount($account);
            $purged++;
        }

        return $purged;
    }

    public function deleteAccount(Account $account): void
    {
        $account->shares()->each(function ($share): void {
            try {
                $share->clearMediaCollection();
            } catch (\Throwable) {
                // best-effort cascade
            }
            $share->delete();
        });

        $account->apiKeys()->delete();
        $account->favourites()->detach();
        $account->delete();
    }

    private function assertValidEmail(string $email): void
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => ['Please enter a valid email address.'],
            ]);
        }
    }

    private function assertValidPassword(string $password): void
    {
        $len = strlen($password);
        if ($len < self::MIN_PASSWORD || $len > self::MAX_PASSWORD) {
            throw ValidationException::withMessages([
                'password' => ['Password must be between ' . self::MIN_PASSWORD . ' and ' . self::MAX_PASSWORD . ' characters.'],
            ]);
        }
    }

    private function hashPassword(string $password): string
    {
        return Hash::make($password, ['rounds' => self::BCRYPT_COST]);
    }
}
