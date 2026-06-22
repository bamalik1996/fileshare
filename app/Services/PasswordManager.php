<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Password_Manager service for share-level password protection (Requirement 2).
 *
 * Centralises password length validation, bcrypt hashing, and verification so
 * controllers and the {@see \App\Services\ShareService} never deal with the
 * plaintext password directly. The plaintext value is passed in, hashed, and
 * discarded; nothing is ever returned, logged, or persisted in cleartext.
 *
 * Acceptance criteria covered:
 *   2.1 - bcrypt hash via Hasher::make(); plaintext never returned or stored.
 *   2.2 - validate() rejects length <6 or >128 with a {@see ValidationException}.
 *   2.5 - verify() compares against the stored bcrypt hash.
 *   2.8 - companion to ShareService when an owner clears the password.
 */
class PasswordManager
{
    /**
     * Minimum password character length per Requirement 2.1 / 2.2.
     */
    public const MIN_LENGTH = 6;

    /**
     * Maximum password character length per Requirement 2.1 / 2.2.
     */
    public const MAX_LENGTH = 128;

    /**
     * Validation error key returned in {@see ValidationException::errors()}.
     */
    public const ERROR_KEY = 'password';

    public function __construct(private readonly ?Hasher $hasher = null)
    {
    }

    /**
     * Validate length and return a bcrypt hash of $plain.
     *
     * @throws ValidationException When length is out of range.
     */
    public function hash(string $plain): string
    {
        $this->validate($plain);

        return $this->hasher()->make($plain);
    }

    /**
     * Verify a plaintext attempt against a stored bcrypt hash.
     *
     * Returns false for empty inputs without invoking the hasher so callers
     * can short-circuit obvious miss cases without leaking timing.
     */
    public function verify(string $plain, string $hash): bool
    {
        if ($plain === '' || $hash === '') {
            return false;
        }

        return $this->hasher()->check($plain, $hash);
    }

    /**
     * Enforce the 6..128 character length policy.
     *
     * Length is measured in Unicode characters (mb_strlen) rather than bytes
     * so passwords composed of multi-byte glyphs are not falsely rejected at
     * the upper bound and not falsely accepted at the lower bound.
     *
     * @throws ValidationException With key {@see self::ERROR_KEY} when the
     *                             length falls outside [6, 128].
     */
    public function validate(string $plain): void
    {
        $length = mb_strlen($plain);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw ValidationException::withMessages([
                self::ERROR_KEY => [
                    sprintf(
                        'The password must be between %d and %d characters.',
                        self::MIN_LENGTH,
                        self::MAX_LENGTH,
                    ),
                ],
            ]);
        }
    }

    private function hasher(): Hasher
    {
        return $this->hasher ?? Hash::driver();
    }
}
