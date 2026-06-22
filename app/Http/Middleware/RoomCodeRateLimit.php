<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limit on Room Code submissions per source IP (Requirement 7.8).
 *
 * After {@see config('airtoshare.room_code_rate_limit.max_attempts')}
 * invalid Room Code submissions inside the rolling
 * `decay_seconds` window from the same IP, this middleware blocks
 * further submissions from that IP for `block_seconds` (default 5
 * minutes) by returning HTTP 429 with a non-disclosing body and
 * *without* performing a Room Code lookup. The downstream controller
 * is the source of truth for what counts as "invalid":
 *
 *   - Bad format / unknown code / expired code → caller invokes
 *     {@see self::recordInvalidAttempt()} so the bucket fills.
 *   - Successful match                          → caller invokes
 *     {@see self::clear()} so a legitimate user is not penalised by
 *     prior typos.
 *
 * Why a "soft" middleware (read-only, with hits done by the caller):
 * lifting the increment into the controller means we can avoid hitting
 * the bucket on cases that were rejected for unrelated reasons (e.g.
 * malformed JSON body, missing CSRF) which the framework rejects above
 * the controller. The middleware still owns the *block* check so the
 * bcrypt-equivalent code-lookup is short-circuited once the bucket is
 * saturated, exactly as Requirement 7.8 mandates.
 *
 * Bucket layout:
 *   - `room_invalid:{ip}`   : a Laravel {@see RateLimiter} bucket whose
 *                              hit count grows by one on each failed
 *                              submission. Decay: `decay_seconds`.
 *   - `room_blocked:{ip}`   : a sticky boolean flag set when the bucket
 *                              first reaches the threshold. Lives for
 *                              `block_seconds`. The middleware reads
 *                              this flag (rather than the hit count
 *                              directly) so the 5-minute block survives
 *                              the natural 60-second bucket decay.
 *
 * Configuration: {@see config('airtoshare.room_code_rate_limit')}.
 */
class RoomCodeRateLimit
{
    /**
     * Cache prefix for the per-IP hit bucket. Public so the controller
     * (or any future caller) can compute the same key without inlining
     * the literal string.
     */
    public const BUCKET_PREFIX = 'room_invalid:';

    /**
     * Cache prefix for the sticky "blocked" flag. The flag is set once
     * the bucket reaches `max_attempts` and lives for `block_seconds`.
     */
    public const BLOCKED_PREFIX = 'room_blocked:';

    /**
     * Generic, non-disclosing error message returned in the 429 body
     * (Requirement 7.8 wording: "rate-limited access without performing
     * Room Code lookup").
     */
    public const ERROR_MESSAGE = 'Too many attempts. Please try again later.';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $this->ipFor($request);

        if ($this->isBlocked($ip)) {
            return $this->blocked($request);
        }

        return $next($request);
    }

    /**
     * Record a single invalid Room Code submission for the request's
     * source IP. Called by {@see \App\Http\Controllers\RoomController}
     * on every code submission that fails format validation, lookup, or
     * expiry checks.
     *
     * Once the bucket reaches `max_attempts` hits inside `decay_seconds`,
     * the sticky `room_blocked:{ip}` flag is set so the next request
     * short-circuits inside this middleware instead of running another
     * lookup.
     *
     * Returns the number of invalid attempts recorded against this IP
     * inside the current window after this hit, so callers can log it
     * if they want.
     */
    public static function recordInvalidAttempt(Request $request): int
    {
        $ip = self::ipForStatic($request);
        $bucketKey = self::BUCKET_PREFIX . $ip;
        $decay = self::decaySeconds();
        $maxAttempts = self::maxAttemptsStatic();

        // RateLimiter::hit() returns the new total. We pass the bucket
        // decay as the TTL so a quiet 60s window naturally rolls the
        // counter back to zero.
        $hits = RateLimiter::hit($bucketKey, $decay);

        if ($hits >= $maxAttempts) {
            // Sticky block flag survives the bucket's 60s decay so the
            // 5-minute block from Requirement 7.8 is enforced even
            // after the underlying counter has expired.
            Cache::put(
                self::BLOCKED_PREFIX . $ip,
                true,
                self::blockSecondsStatic(),
            );
        }

        return (int) $hits;
    }

    /**
     * Drop both the hit bucket and the sticky block flag for the
     * request's IP. Called by the controller after a successful Room
     * Code match so a recipient who eventually types the right code is
     * not held back by a few earlier typos.
     */
    public static function clear(Request $request): void
    {
        $ip = self::ipForStatic($request);
        RateLimiter::clear(self::BUCKET_PREFIX . $ip);
        Cache::forget(self::BLOCKED_PREFIX . $ip);
    }

    /**
     * Convenience wrapper: tell the caller whether this IP is currently
     * blocked, without going through the middleware path. Useful for
     * tests and for the controller to reason about its own state.
     */
    public static function isIpBlocked(Request $request): bool
    {
        return (bool) Cache::get(self::BLOCKED_PREFIX . self::ipForStatic($request), false);
    }

    private function isBlocked(string $ip): bool
    {
        return (bool) Cache::get(self::BLOCKED_PREFIX . $ip, false);
    }

    private function blocked(Request $request): Response
    {
        if ($this->expectsJson($request)) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => self::ERROR_MESSAGE,
            ], 429);
        }

        return new Response(self::ERROR_MESSAGE, 429, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function expectsJson(Request $request): bool
    {
        if ($request->expectsJson()) {
            return true;
        }

        return str_starts_with($request->path(), 'api/');
    }

    private function ipFor(Request $request): string
    {
        return self::ipForStatic($request);
    }

    private static function ipForStatic(Request $request): string
    {
        return (string) ($request->ip() ?? 'unknown');
    }

    private static function maxAttemptsStatic(): int
    {
        $configured = config('airtoshare.room_code_rate_limit.max_attempts', 10);
        $value = is_numeric($configured) ? (int) $configured : 10;

        return max(1, $value);
    }

    private static function decaySeconds(): int
    {
        $configured = config('airtoshare.room_code_rate_limit.decay_seconds', 60);
        $value = is_numeric($configured) ? (int) $configured : 60;

        return max(1, $value);
    }

    private static function blockSecondsStatic(): int
    {
        $configured = config('airtoshare.room_code_rate_limit.block_seconds', 5 * 60);
        $value = is_numeric($configured) ? (int) $configured : 5 * 60;

        return max(1, $value);
    }
}
