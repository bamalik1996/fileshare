<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Principal\RoomPrincipal;
use App\Exceptions\RoomAllocationException;
use App\Models\Room;
use App\Models\Share;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Room_Service (design.md > Components and Interfaces > 7).
 *
 * Owns the lifecycle of a Room: code allocation, code validation, and
 * code-driven lookup. The service is intentionally narrow - it does not
 * know about HTTP, rate limiting, or password verification (those live
 * in the controller / middleware layers, see tasks 12.2 / 12.3).
 *
 * Acceptance criteria covered:
 *   7.1 - 6-character Room Code drawn from the 32-character alphabet
 *         `ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (no `O`, `I`, `0`, `1`).
 *   7.2 - up to 5 retries on collision before raising
 *         {@see RoomAllocationException}.
 *   7.3 - case-insensitive code lookup that excludes expired rooms.
 *   7.4 - lookup with an invalid format / non-existent / expired code
 *         returns `null`; no Room state is modified. Callers map the
 *         null to a "Room not found" error.
 *   7.5 - same expiry option set as IP principals (no `30d`), parsed via
 *         {@see ExpiryManager}.
 *   7.7 - optional password is hashed via {@see PasswordManager::hash()}
 *         and persisted; verification happens later in the password gate
 *         middleware against the stored hash.
 *
 * Acceptance criteria 7.6 (room deletion conditioned on expiry AND
 * inactivity) and 7.8 (rate-limited Room Code submissions) are deliberately
 * out of scope here - they live in {@see \App\Console\Commands\ShareCleanupExpired}
 * and {@see \App\Http\Middleware\RoomCodeRateLimit} respectively.
 */
class RoomService
{
    /**
     * The 32-character alphabet from which Room Codes are sampled
     * (Requirement 7.1). Visually-confusable characters `O`, `I`, `0`,
     * `1` are excluded so codes can be transcribed from a screen to a
     * keypad without ambiguity.
     */
    public const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Code length in characters (Requirement 7.1).
     */
    public const CODE_LENGTH = 6;

    /**
     * Maximum number of generation attempts before a unique code is
     * declared unallocatable (Requirement 7.2).
     */
    public const MAX_GENERATION_ATTEMPTS = 5;

    /**
     * Anchored regex that matches the format defined in Requirement 7.1.
     *
     * Equivalent to the design's `/^[A-Z2-9&&[^OI]]{6}$/i` notation,
     * rewritten in PCRE syntax (PHP's PCRE engine does not support
     * character-class intersection):
     *   - `A-HJ-NP-Z` enumerates A..Z minus `I` and `O`.
     *   - `2-9`      excludes `0` and `1`.
     *   - `i` flag makes the match case-insensitive so the
     *     case-insensitive lookup contract from Requirement 7.3 is honoured
     *     at the format-validation boundary too.
     */
    public const FORMAT_REGEX = '/^[A-HJ-NP-Z2-9]{6}$/i';

    /**
     * Sentinel principal identifier used while parsing the expiry option
     * for a not-yet-persisted Room. {@see ExpiryManager::parseOption()}
     * only consults `Principal::type()`, so the identifier value does
     * not affect the resolved timestamp - we just need a valid
     * {@see RoomPrincipal} to communicate "treat this as a Room owner"
     * (Requirement 7.5).
     */
    private const PENDING_ROOM_ID = 'pending';

    public function __construct(
        private readonly ExpiryManager $expiryManager,
        private readonly PasswordManager $passwordManager,
    ) {
    }

    /**
     * Allocate a new Room with a unique 6-character code.
     *
     * The expiry option is parsed against a Room principal, so accepted
     * tokens are exactly `1h`, `6h`, `24h`, `7d` (Requirement 7.5); any
     * other value (including `30d`) raises an
     * {@see \InvalidArgumentException} from {@see ExpiryManager}, which
     * is caller-mapped to HTTP 422.
     *
     * If a non-empty `$password` is supplied, it is hashed by
     * {@see PasswordManager::hash()} (which also enforces the 6..128
     * character length policy from Requirement 2.2) and stored on the
     * Room. A `null` or empty string disables password protection
     * (Requirement 7.7).
     *
     * Allocation strategy (Requirements 7.1, 7.2):
     *   1. Generate a candidate code using {@see random_int} over the
     *      32-character alphabet.
     *   2. Check uniqueness against the `rooms` table. The column has a
     *      `UNIQUE` index so concurrent inserts are caught at the DB
     *      level too - we treat a {@see QueryException} on save as a
     *      late-arriving collision and retry the same way.
     *   3. After 5 failed attempts, raise {@see RoomAllocationException}.
     *
     * @throws \InvalidArgumentException                     When `$expiry`
     *         is not in `{1h, 6h, 24h, 7d, null}`.
     * @throws \Illuminate\Validation\ValidationException    When
     *         `$password` is non-empty and outside the 6..128 length range.
     * @throws RoomAllocationException                       After
     *         {@see self::MAX_GENERATION_ATTEMPTS} consecutive collisions.
     */
    public function create(?string $expiry, ?string $password): Room
    {
        $expiresAt = $this->expiryManager->parseOption(
            $expiry,
            new RoomPrincipal(self::PENDING_ROOM_ID),
        );

        $passwordHash = null;
        if (is_string($password) && $password !== '') {
            $passwordHash = $this->passwordManager->hash($password);
        }

        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_GENERATION_ATTEMPTS; $attempt++) {
            $code = $this->generateCode();

            // Cheap pre-check that avoids burning a save attempt on the
            // common collision case. The authoritative uniqueness check
            // is the DB's UNIQUE index, caught below.
            if (Room::query()->where('code', $code)->exists()) {
                continue;
            }

            try {
                $room = Room::create([
                    'code' => $code,
                    'password_hash' => $passwordHash,
                    'expires_at' => $expiresAt,
                ]);

                // Task 12: every Room owns a Share aggregate for content,
                // password gating, realtime, and clipboard sync.
                Share::query()->create([
                    'owner_type'    => Share::OWNER_TYPE_ROOM,
                    'owner_id'      => (string) $room->id,
                    'expires_at'    => $room->expires_at,
                    'password_hash' => $room->password_hash,
                ]);

                return $room;
            } catch (QueryException $e) {
                // A row with the same code was inserted by another
                // process between our existence check and our save.
                // Treat as a collision: the retry budget already covers
                // this case (Requirement 7.2 "up to 5 times").
                $lastException = $e;
            }
        }

        Log::warning('RoomService: exhausted Room Code allocation retries', [
            'attempts' => self::MAX_GENERATION_ATTEMPTS,
            'reason' => $lastException?->getMessage(),
        ]);

        throw new RoomAllocationException(
            sprintf(
                'Could not allocate a unique Room Code after %d attempts.',
                self::MAX_GENERATION_ATTEMPTS,
            ),
            $lastException,
        );
    }

    /**
     * Look up a Room by its code, case-insensitively, excluding rooms
     * whose `expires_at` is at or before the current time
     * (Requirements 7.3, 7.4).
     *
     * Returns `null` when:
     *   - `$code` is not a valid 6-character Room Code;
     *   - no row matches (case-insensitive); or
     *   - the matching row is expired.
     *
     * The lookup never deletes the row even when expired - deletion is
     * conditioned on inactivity as well (Requirement 7.6) and is owned
     * by the scheduled cleanup command.
     */
    public function findByCode(string $code): ?Room
    {
        if (! $this->validateFormat($code)) {
            return null;
        }

        $normalised = strtoupper($code);

        return Room::query()
            ->whereRaw('UPPER(code) = ?', [$normalised])
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    /**
     * Verify that `$code` matches the 6-character Room Code format from
     * Requirement 7.1 (case-insensitive).
     *
     * Returns `true` when the input is a 6-character string composed
     * solely of characters from {@see self::ALPHABET}, in any letter
     * case. Returns `false` otherwise (wrong length, contains
     * disallowed glyphs `O`, `I`, `0`, `1`, contains whitespace, etc.).
     */
    public function validateFormat(string $code): bool
    {
        return preg_match(self::FORMAT_REGEX, $code) === 1;
    }

    /**
     * Generate a single 6-character candidate code by sampling
     * {@see self::ALPHABET} with {@see random_int} (CSPRNG-backed).
     *
     * Using `random_int` rather than `mt_rand` ensures the code space is
     * uniformly distributed and not predictable from prior outputs - a
     * weak generator would let an attacker enumerate candidate codes
     * faster than the 32^6 search space implies.
     *
     * Marked `protected` (rather than private) so test doubles can
     * override it with deterministic outputs - the retry semantics in
     * {@see self::create()} cannot be exercised reliably otherwise.
     */
    protected function generateCode(): string
    {
        $alphabet = self::ALPHABET;
        $maxIndex = strlen($alphabet) - 1;
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= $alphabet[random_int(0, $maxIndex)];
        }

        return $code;
    }
}
