<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Principal\AccountPrincipal;
use App\Domain\Principal\ApiKeyPrincipal;
use App\Domain\Principal\IpPrincipal;
use App\Domain\Principal\Principal;
use App\Models\Account;
use App\Services\ShareService;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the {@see Principal} for every incoming request and binds it
 * to the request lifecycle so downstream code (controllers, services,
 * gates, broadcast authorisations) can read a single, consistent owner
 * value via {@see Request::principal()}.
 *
 * The decision tree mirrors design.md > Architecture > Authentication
 * and Authorisation Layers:
 *
 *  1. `/api/v2/*` requests → {@see ApiKeyPrincipal}, populated by the
 *     dedicated `ApiKeyAuth` middleware (task 25.2). Until that wiring
 *     lands, this middleware does not attempt the bearer-token lookup
 *     itself; if `ApiKeyAuth` has not bound an Account, the request
 *     falls through to the same IP branch a guest would get. The
 *     `ApiKeyAuth` middleware is responsible for issuing 401 responses
 *     (Requirement 18.6) before the controller runs.
 *  2. Authenticated session with a logged-in {@see Account} →
 *     {@see AccountPrincipal} (Requirement 16.4). All shares created
 *     during the session inherit the Account owner.
 *  3. Otherwise → {@see IpPrincipal} from the request IP. This is the
 *     default guest flow that Requirement 16.13 mandates we preserve
 *     verbatim.
 *
 * The resolved principal is stored in two places:
 *
 *  - `$request->attributes->set('principal', …)` — Symfony's typed bag.
 *    This is the canonical, dynamic-property-safe storage location and
 *    survives even on PHP runtimes that disable arbitrary public
 *    property assignment on the underlying SymfonyRequest.
 *  - `$request->principal` accessor — an `Illuminate\Http\Request`
 *    macro registered in {@see \App\Providers\AppServiceProvider::boot()}
 *    that reads the same attribute. Downstream code can call either
 *    `$request->principal()` (preferred) or `$request->attributes->get('principal')`.
 */
class ResolvePrincipal
{
    public function __construct(private readonly ShareService $shareService)
    {
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $principal = $this->resolve($request);

        // Symfony attribute bag is the canonical store: typed, immune to
        // PHP 8.2+ dynamic-property deprecation, and exposed through the
        // Request::principal() macro for ergonomic reads.
        $request->attributes->set('principal', $principal);

        return $next($request);
    }

    /**
     * Picks the right Principal kind for the current request without
     * mutating any request state. Extracted for unit-testing the
     * decision tree in isolation.
     */
    public function resolve(Request $request): Principal
    {
        // Branch 1: API v2 path. ApiKeyAuth (task 25.2) is responsible
        // for actually authenticating the bearer token and injecting an
        // ApiKeyPrincipal under the `principal` attribute *before* this
        // middleware runs in the v2 stack, OR exposing the resolved
        // Account via the attribute bag's `api_key_account` slot. We
        // honour either contract here so the order of registration in
        // bootstrap/app.php stays flexible.
        if ($request->is('api/v2/*')) {
            $existing = $request->attributes->get('principal');
            if ($existing instanceof ApiKeyPrincipal) {
                return $existing;
            }

            $account = $request->attributes->get('api_key_account');
            $apiKeyId = $request->attributes->get('api_key_id');
            if ($account instanceof Account && $apiKeyId !== null) {
                return new ApiKeyPrincipal(
                    accountId: (int) $account->getKey(),
                    apiKeyId: (string) $apiKeyId,
                );
            }

            // ApiKeyAuth has not bound an Account yet (either because
            // it has not run, or because the request is anonymous on a
            // route that hasn't applied it). Fall through to the guest
            // branches below; ApiKeyAuth itself will short-circuit with
            // 401 for routes that require it.
        }

        // Branch 2: authenticated session backed by an Account model.
        // We deliberately key on the Account class (not just any
        // Authenticatable) so the existing `users` guard, which is
        // unrelated to the AirToShareA share flow, never accidentally
        // promotes a User to an AccountPrincipal.
        $user = $this->authenticatedUser($request);
        if ($user instanceof Account) {
            $this->claimGuestContentOnce($request, $user);

            return new AccountPrincipal((int) $user->getKey());
        }

        // Branch 3: fall back to IP. Requirement 16.13 requires that
        // every existing IP-based behaviour keep working unchanged for
        // anonymous visitors. `request->ip()` already honours trusted
        // proxies if any are configured.
        return new IpPrincipal((string) $request->ip());
    }

    /**
     * Resolve the currently-authenticated user without throwing if the
     * auth subsystem is not configured (e.g. during tests that boot the
     * framework but never define a guard). Returns null when no
     * authenticated user is bound.
     */
    private function authenticatedUser(Request $request): ?Authenticatable
    {
        try {
            $user = $request->user('account');
            if ($user instanceof Account) {
                return $user;
            }

            $user = $request->user();
        } catch (\Throwable) {
            return null;
        }

        return $user instanceof Authenticatable ? $user : null;
    }

    private function claimGuestContentOnce(Request $request, Account $account): void
    {
        if (! $request->hasSession()) {
            $this->shareService->claimGuestContentForAccount($account, (string) $request->ip());

            return;
        }

        if ($request->session()->get('guest_content_claimed_for') === (string) $account->getKey()) {
            return;
        }

        $this->shareService->claimGuestContentForAccount($account, (string) $request->ip());
        $request->session()->put('guest_content_claimed_for', (string) $account->getKey());
    }
}
