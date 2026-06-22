<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Brute-force protection for the password verification endpoint
 * (Requirement 2.7).
 *
 * After {@see config('airtoshare.password_verify_rate_limit.max_attempts')}
 * verification failures from the same `(ip, share_id)` bucket within
 * `decay_seconds`, further requests are blocked for `block_seconds` and
 * return HTTP 401 *without* invoking bcrypt. The 401 response body is
 * byte-identical to the "wrong password" body served by
 * {@see SharePasswordGate}, satisfying Requirement 2.6 (must not disclose
 * whether the failure was rate-limit, wrong password, or non-existent
 * share).
 *
 * Bookkeeping protocol:
 *  - This middleware does not record failures itself; it only checks
 *    whether the bucket is currently exhausted before allowing the
 *    request to proceed.
 *  - The downstream controller / service that performs the bcrypt
 *    verification is responsible for calling
 *    {@see RateLimiter::hit()} on the same key after a failed attempt
 *    and {@see RateLimiter::clear()} on success. The shared key is
 *    exposed via {@see PasswordVerifyRateLimit::keyFor()}.
 *
 * Why a "soft" middleware:
 *  - Putting the increment in the controller keeps the bcrypt path and
 *    the failure-counting path co-located, so we can avoid a hit when
 *    the request fails the SharePasswordGate for unrelated reasons (no
 *    Share, missing password). Lifting the check into the middleware
 *    still lets us short-circuit the bcrypt call once the bucket is
 *    exhausted, which is the property Requirement 2.7 actually cares
 *    about.
 *
 * Configuration: {@see config/airtoshare.php#password_verify_rate_limit}.
 */
class PasswordVerifyRateLimit
{
    /**
     * Named RateLimiter bucket. Registered in
     * {@see \App\Providers\RouteServiceProvider::boot()} so callers can
     * also reach the limiter through Laravel's `throttle:share-pw`
     * middleware alias if they prefer the framework-default contract.
     */
    public const LIMITER = 'share-pw';

    /** Generic, non-disclosing error body (matches {@see SharePasswordGate}). */
    public const ERROR_MESSAGE = 'Password required';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $parameter = 'share'): Response
    {
        $shareId = $this->resolveShareId($request, $parameter);

        // No share id resolvable → leave the verification path alone.
        // The downstream gate will return its own non-disclosing 401.
        if ($shareId === null) {
            return $next($request);
        }

        $key = $this->buildKey($request, $shareId);
        $maxAttempts = $this->maxAttempts();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->blocked($request);
        }

        return $next($request);
    }

    /**
     * Build the shared bucket key used by both this middleware (read)
     * and the downstream verification controller (write). The key is
     * deliberately scoped to (ip, share_id) so a single attacker cannot
     * exhaust attempts for a victim share by hitting it from one IP
     * (Requirement 2.7's exact wording: "from the same IP within a
     * rolling 15-minute window").
     */
    public static function keyFor(Request $request, string|int $shareId): string
    {
        $ip = (string) ($request->ip() ?? 'unknown');
        return self::LIMITER . ':' . $ip . '|' . (string) $shareId;
    }

    /**
     * Convenience for callers (e.g. the password-verify controller) who
     * have already resolved the Share themselves and want the same key
     * shape this middleware uses.
     */
    private function buildKey(Request $request, string|int $shareId): string
    {
        return self::keyFor($request, $shareId);
    }

    /**
     * Resolve the share identifier from the route. We accept either the
     * Share's primary key (when route model binding has run) or the
     * uuid / slug literal that other middleware uses. The bucket only
     * needs *some* stable per-share token; the controller uses the
     * same source via {@see SharePasswordGate} so reads and writes
     * agree.
     */
    private function resolveShareId(Request $request, string $parameter): string|int|null
    {
        $value = $request->route($parameter);

        if (is_object($value) && method_exists($value, 'getKey')) {
            $key = $value->getKey();
            if ($key !== null) {
                return $key;
            }
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_int($value)) {
            return $value;
        }

        return null;
    }

    /**
     * 5 by default per Requirement 2.7; overridable via config so tests
     * and emergency knobs can tune it without touching code.
     */
    private function maxAttempts(): int
    {
        $configured = config('airtoshare.password_verify_rate_limit.max_attempts', 5);
        $value = is_numeric($configured) ? (int) $configured : 5;

        return max(1, $value);
    }

    /**
     * Return the non-disclosing 401 used by every share-password
     * failure mode. Mirrors {@see SharePasswordGate::unauthorized()} so
     * that the response shape is constant from a client's perspective.
     */
    private function blocked(Request $request): Response
    {
        if ($this->expectsJson($request)) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => self::ERROR_MESSAGE,
            ], 401);
        }

        return new Response(self::ERROR_MESSAGE, 401, [
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
}
