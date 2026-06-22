<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Principal\Principal;
use App\Exceptions\ShareExpiredException;
use App\Models\Share;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

/**
 * Expiry_Manager service (design.md > Components and Interfaces > 3).
 *
 * Centralises the three expiry-related responsibilities required by
 * Requirement 3:
 *
 *   - {@see self::parseOption()} maps an opaque option token to an absolute
 *     UTC `Carbon` timestamp (3.1, 3.2, 3.3, 7.5, 16.9).
 *   - {@see self::enforceOnRead()} is the read-time fallback that deletes
 *     an expired Share + its media and raises {@see ShareExpiredException}
 *     so the caller can map to HTTP 404 (3.4, 3.8).
 *   - {@see self::optionRule()} returns a Form Request validation rule
 *     restricting input to the allowed option set for the principal kind
 *     (3.5, 16.9).
 *
 * The scheduled background cleanup (3.6, 3.7) lives in the
 * `App\Console\Commands\ShareCleanupExpired` artisan command (task 3.4) and
 * is intentionally not part of this service - this class only covers the
 * synchronous, request-bound concerns that need a single call site.
 */
class ExpiryManager
{
    /**
     * Option token used when no expiry was supplied on a create/update
     * request (Requirement 3.2).
     */
    public const DEFAULT_OPTION = '24h';

    /**
     * Owner-type token (matches {@see Principal::type()}) under which the
     * `30d` option is unlocked. Mirrors the `Rule::in(['1h','6h','24h',
     * '7d','30d'])` branch in design.md > Component 3 > Validation.
     */
    private const ACCOUNT_TYPE = 'account';

    /**
     * Map of `option` => duration applied via Carbon's mutator API.
     *
     * Kept as a single source of truth so the parseOption(), optionRule(),
     * and allowedOptionsFor() helpers cannot drift apart.
     *
     * The closures all return a brand-new {@see Carbon} based on the
     * caller-supplied "now" so every call uses one consistent baseline
     * timestamp - relevant when a request happens to straddle a second
     * boundary between option lookup and timestamp materialisation.
     *
     * @var array<string, callable(Carbon): Carbon>
     */
    private const OPTION_DURATIONS = [
        '1h'  => [self::class, 'add1h'],
        '6h'  => [self::class, 'add6h'],
        '24h' => [self::class, 'add24h'],
        '7d'  => [self::class, 'add7d'],
        '30d' => [self::class, 'add30d'],
    ];

    /**
     * Options available to every principal kind (Requirement 3.1).
     *
     * @var list<string>
     */
    private const COMMON_OPTIONS = ['1h', '6h', '24h', '7d'];

    /**
     * Additional option enabled only for Account principals (Requirement
     * 16.9). Kept as a separate list so a future tier (e.g. paid plans
     * with longer windows) can append further options without rewriting
     * the option resolution logic.
     *
     * @var list<string>
     */
    private const ACCOUNT_ONLY_OPTIONS = ['30d'];

    /**
     * Parse an expiry option for the given principal and return the
     * absolute UTC timestamp at which the resulting Share should expire.
     *
     * Behaviour matrix:
     *   - `null`                   → defaults to {@see self::DEFAULT_OPTION}
     *                                (Requirement 3.2).
     *   - `"1h" | "6h" | "24h" | "7d"` → accepted for any principal.
     *   - `"30d"`                  → accepted only when
     *                                `$principal->type() === 'account'`
     *                                (Requirement 16.9). Rejected with
     *                                `\InvalidArgumentException` for any
     *                                other principal kind.
     *   - anything else            → rejected with
     *                                `\InvalidArgumentException`. Form
     *                                Request validation should reject the
     *                                input with HTTP 422 first
     *                                (Requirement 3.5); this guard is the
     *                                last line of defence for direct
     *                                service-layer callers.
     *
     * The returned timestamp is always materialised in UTC at second
     * precision to satisfy Requirement 3.3.
     *
     * @throws \InvalidArgumentException When the option is not in the
     *                                   set allowed for the principal.
     */
    public function parseOption(?string $opt, Principal $principal): Carbon
    {
        $option = $opt ?? self::DEFAULT_OPTION;

        if (! $this->isOptionAllowedFor($option, $principal)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid expiry option "%s" for principal of type "%s".',
                $option,
                $principal->type(),
            ));
        }

        // Ensure the baseline is captured in UTC at the start of the call;
        // the closure derives the final value from this single instant.
        $now = Carbon::now('UTC')->startOfSecond();
        $applier = self::OPTION_DURATIONS[$option];

        return ($applier)($now);
    }

    /**
     * Read-time expiry enforcement (Requirements 3.4, 3.8).
     *
     * If `$share->isExpired()` is true:
     *   1. Detach all media via Spatie's `clearMediaCollection()` so the
     *      physical files are removed from disk.
     *   2. Delete the parent Share row.
     *   3. Raise {@see ShareExpiredException} (HTTP 404).
     *
     * The deletion is best-effort: any failure during cleanup is logged
     * and the exception is still thrown so the read response always
     * surfaces 404, never the partially-deleted state.
     *
     * @throws ShareExpiredException When the share's expires_at <= now().
     */
    public function enforceOnRead(Share $share): void
    {
        if (! $share->isExpired()) {
            return;
        }

        try {
            $share->clearMediaCollection();
        } catch (\Throwable $e) {
            // Don't let a media-store failure mask the 404 response from
            // the caller. Log it so operators can investigate; the
            // hourly ShareCleanupExpired command (task 3.4) will retry
            // the cleanup on the next pass.
            Log::warning('ExpiryManager: failed to clear media for expired share', [
                'share_id' => $share->id,
                'share_uuid' => $share->uuid,
                'reason' => $e->getMessage(),
            ]);
        }

        try {
            $share->delete();
        } catch (\Throwable $e) {
            Log::warning('ExpiryManager: failed to delete expired share row', [
                'share_id' => $share->id,
                'share_uuid' => $share->uuid,
                'reason' => $e->getMessage(),
            ]);
        }

        throw new ShareExpiredException();
    }

    /**
     * Form Request rule helper for the allowed option set
     * (Requirement 3.5, 16.9).
     *
     * Usage in a Form Request:
     *
     *     return [
     *         'expiry' => ['nullable', 'string', $expiryManager->optionRule($request->principal())],
     *     ];
     *
     * The returned {@see In} rule yields HTTP 422 on the controller
     * boundary for any value outside the allowed set, so no Share row is
     * created or modified for invalid input (which is exactly what 3.5
     * mandates, since Form Request validation runs before the controller
     * action even executes).
     */
    public function optionRule(Principal $principal): In
    {
        return Rule::in($this->allowedOptionsFor($principal));
    }

    /**
     * The set of expiry option tokens the given principal may pass to
     * {@see self::parseOption()}.
     *
     * Exposed publicly so callers that don't go through Form Request
     * validation (e.g. the JSON API or admin tooling) can still consult
     * the canonical list rather than hard-coding it.
     *
     * @return list<string>
     */
    public function allowedOptionsFor(Principal $principal): array
    {
        if ($principal->type() === self::ACCOUNT_TYPE) {
            return [...self::COMMON_OPTIONS, ...self::ACCOUNT_ONLY_OPTIONS];
        }

        return self::COMMON_OPTIONS;
    }

    private function isOptionAllowedFor(string $option, Principal $principal): bool
    {
        return in_array($option, $this->allowedOptionsFor($principal), true);
    }

    // -- duration appliers ---------------------------------------------------
    //
    // A small set of named static helpers rather than inline closures so the
    // PHP 8.2 `[self::class, 'methodName']` callable syntax in
    // OPTION_DURATIONS is unambiguous and the code remains debuggable in a
    // stack trace.

    private static function add1h(Carbon $now): Carbon
    {
        return $now->copy()->addHour();
    }

    private static function add6h(Carbon $now): Carbon
    {
        return $now->copy()->addHours(6);
    }

    private static function add24h(Carbon $now): Carbon
    {
        return $now->copy()->addHours(24);
    }

    private static function add7d(Carbon $now): Carbon
    {
        return $now->copy()->addDays(7);
    }

    private static function add30d(Carbon $now): Carbon
    {
        return $now->copy()->addDays(30);
    }
}
