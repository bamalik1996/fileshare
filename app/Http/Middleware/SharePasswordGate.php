<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Share;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate that protects access to password-protected Shares (Requirement 2).
 *
 * Reads `session('share_pw_ok')` — a per-session map of share ids that
 * have already passed `bcrypt::check` during this browser session — and
 * decides whether to let the request through.
 *
 * Decision tree (design.md > Components > Password_Manager):
 *
 *   1. Resolve the {@see Share} from the route parameter (model binding
 *      preferred, otherwise lookup by `uuid` or `public_slug`).
 *      If we cannot resolve a Share → return 401 with the same generic
 *      "Password required" body so the response is byte-identical to the
 *      "wrong password" case (Requirement 2.6: must not disclose whether
 *      the share exists).
 *   2. If `share.password_hash` is null → pass through (Requirement 2.4).
 *   3. If `session('share_pw_ok')[$share->id] === true` → pass through
 *      (Requirement 2.5: previously-verified session may proceed).
 *   4. Otherwise → return 401 with the generic body (Requirement 2.3).
 *
 * Response shape:
 *  - For requests that expect JSON (`Accept: application/json` or paths
 *    starting with `/api/`), a JSON body `{status:"error", message:
 *    "Password required"}` is returned (matches the contract used by
 *    every other AirToShareA error response).
 *  - For browser requests, an HTML password-challenge view would be
 *    rendered; until task 4.x lands the dedicated view, we fall back to
 *    a plain-text 401 body so the contract on the wire is unambiguous.
 *
 * The `share_id` route parameter name is configurable so the middleware
 * can be applied to web routes (`/s/{share}`), public routes
 * (`/p/{slug}`), and API routes (`/api/v1/shares/{share}`) without
 * duplication. Pass an alternate parameter name with
 * `->middleware('share.password:slug')`.
 */
class SharePasswordGate
{
    /** Session map key. The id is numeric (Share primary key). */
    public const SESSION_KEY = 'share_pw_ok';

    /** Generic, non-disclosing error message (Requirement 2.6). */
    public const ERROR_MESSAGE = 'Password required';

    public static function grantVerifiedShare(Request $request, int $shareId): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $map = $request->session()->get(self::SESSION_KEY, []);
        if (! is_array($map)) {
            $map = [];
        }

        $map[$shareId] = true;
        $request->session()->put(self::SESSION_KEY, $map);
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $parameter = 'share'): Response
    {
        $share = $this->resolveShare($request, $parameter);

        // Step 1: cannot resolve a Share. Return the generic 401 so the
        // response is identical to the "wrong password" case. We do NOT
        // 404 here because that would leak share existence to a probe
        // that already knows a valid uuid/slug format (Requirement 2.6).
        if ($share === null) {
            return $this->unauthorized($request);
        }

        // Step 2: no password set on the Share. Pass through unmodified.
        if (! $this->hasPassword($share)) {
            return $next($request);
        }

        // Owners may access their own protected share without re-entering
        // the password (Requirement 14.6 / broadcast owner parity).
        if ($share->ownedByPrincipal($request->principal())) {
            return $next($request);
        }

        // Step 3: existing session token grants access for this Share.
        if ($this->sessionHasVerifiedShare($request, (int) $share->id)) {
            return $next($request);
        }

        // Step 4: password required. Identical body to step 1.
        return $this->unauthorized($request, $share);
    }

    /**
     * Resolve the Share for the current request. Returns null when no
     * Share can be located (whether the route param is missing,
     * malformed, or the row simply does not exist).
     *
     * Lookup order:
     *  1. Route model binding has already produced a Share instance.
     *  2. The string parameter matches an existing `shares.uuid`.
     *  3. The string parameter matches an existing `shares.public_slug`.
     */
    private function resolveShare(Request $request, string $parameter): ?Share
    {
        $value = $request->route($parameter);

        if ($value instanceof Share) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        // UUID lookup first (the primary external identifier per
        // design.md > Data Models > shares).
        $share = Share::query()->where('uuid', $value)->first();
        if ($share !== null) {
            return $share;
        }

        // Public slug fallback (Requirement 17 routes also pass through
        // this gate when the share is password-protected).
        return Share::query()->where('public_slug', $value)->first();
    }

    private function hasPassword(Share $share): bool
    {
        return is_string($share->password_hash) && $share->password_hash !== '';
    }

    /**
     * Check whether the current session has already passed bcrypt for
     * this Share. The session map is keyed by the integer primary key
     * so callers across the codebase use one canonical identifier.
     */
    private function sessionHasVerifiedShare(Request $request, int $shareId): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $map = $request->session()->get(self::SESSION_KEY, []);
        if (! is_array($map)) {
            return false;
        }

        return ($map[$shareId] ?? false) === true;
    }

    /**
     * Build the non-disclosing 401 response. The body is constant so a
     * caller cannot distinguish "share does not exist" from "wrong
     * password" by comparing payloads (Requirement 2.6).
     */
    private function unauthorized(Request $request, ?Share $share = null): Response
    {
        if ($this->expectsJson($request)) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => self::ERROR_MESSAGE,
            ], 401);
        }

        if ($share !== null) {
            return response()->view('share.password', [
                'identifier' => $share->uuid,
                'type'       => 'share',
                'returnUrl'  => $request->fullUrl(),
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

        $path = $request->path();
        return str_starts_with($path, 'api/');
    }
}
