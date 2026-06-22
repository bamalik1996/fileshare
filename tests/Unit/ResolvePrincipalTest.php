<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Principal\AccountPrincipal;
use App\Domain\Principal\ApiKeyPrincipal;
use App\Domain\Principal\IpPrincipal;
use App\Http\Middleware\ResolvePrincipal;
use App\Models\Account;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Unit tests for the ResolvePrincipal middleware (task 2.5).
 *
 * Validates the three-branch decision tree drawn in design.md >
 * Architecture > Authentication and Authorisation Layers:
 *
 *   1. /api/v2/* requests → ApiKeyPrincipal (Requirement 18.5)
 *   2. authenticated session → AccountPrincipal (Requirement 16.4)
 *   3. otherwise → IpPrincipal (Requirement 16.13 guest fallback)
 *
 * The middleware writes the resolved principal into the Symfony
 * attribute bag under the `principal` key; the AppServiceProvider
 * registers a `$request->principal()` macro that reads the same slot.
 * Both the bag and the macro are exercised here.
 */
class ResolvePrincipalTest extends TestCase
{
    public function test_resolves_to_ip_principal_for_anonymous_web_request(): void
    {
        $middleware = app(ResolvePrincipal::class);
        $request = Request::create('/share/abc', 'GET', server: ['REMOTE_ADDR' => '203.0.113.42']);

        $captured = null;
        $middleware->handle($request, function (Request $r) use (&$captured): Response {
            $captured = $r;
            return new Response('ok');
        });

        $this->assertNotNull($captured);
        $principal = $captured->attributes->get('principal');
        $this->assertInstanceOf(IpPrincipal::class, $principal);
        $this->assertSame('ip', $principal->type());
        $this->assertSame('203.0.113.42', $principal->identifier());

        // Macro reads the same value.
        $this->assertSame($principal, $captured->principal());
    }

    public function test_resolves_to_account_principal_for_authenticated_session(): void
    {
        // Stub Account whose primary key is 99. We don't go through the
        // real auth guard because that would require a database; instead
        // we use Laravel's setUserResolver hook which Auth::user() and
        // request->user() both honour.
        $account = new Account();
        $account->id = 99;
        $account->exists = true;

        $middleware = app(ResolvePrincipal::class);
        $request = Request::create('/dashboard', 'GET', server: ['REMOTE_ADDR' => '203.0.113.42']);
        $request->setUserResolver(fn () => $account);

        $middleware->handle($request, fn ($r) => new Response('ok'));

        $principal = $request->attributes->get('principal');
        $this->assertInstanceOf(AccountPrincipal::class, $principal);
        $this->assertSame('account', $principal->type());
        $this->assertSame('99', $principal->identifier());
    }

    public function test_falls_back_to_ip_for_authenticated_user_that_is_not_an_account(): void
    {
        // The base User model is an Authenticatable too, but it is not
        // the Account model that Requirement 16.4 ties to the share
        // owner. The middleware must NOT promote it.
        $user = new \App\Models\User();
        $user->id = 7;
        $user->exists = true;

        $middleware = app(ResolvePrincipal::class);
        $request = Request::create('/dashboard', 'GET', server: ['REMOTE_ADDR' => '198.51.100.7']);
        $request->setUserResolver(fn () => $user);

        $middleware->handle($request, fn ($r) => new Response('ok'));

        $principal = $request->attributes->get('principal');
        $this->assertInstanceOf(IpPrincipal::class, $principal);
        $this->assertSame('198.51.100.7', $principal->identifier());
    }

    public function test_resolves_to_api_key_principal_when_api_key_auth_has_bound_account(): void
    {
        // Simulates the post-25.2 wiring where ApiKeyAuth runs first
        // for /api/v2/* routes and sets `api_key_account` + `api_key_id`
        // on the attribute bag before ResolvePrincipal runs.
        $account = new Account();
        $account->id = 42;
        $account->exists = true;

        $middleware = app(ResolvePrincipal::class);
        $request = Request::create('/api/v2/shares', 'GET', server: ['REMOTE_ADDR' => '203.0.113.7']);
        $request->attributes->set('api_key_account', $account);
        $request->attributes->set('api_key_id', 'key-1234');

        $middleware->handle($request, fn ($r) => new Response('ok'));

        $principal = $request->attributes->get('principal');
        $this->assertInstanceOf(ApiKeyPrincipal::class, $principal);
        $this->assertSame('account', $principal->type());
        $this->assertSame('42', $principal->identifier());
        $this->assertSame('key-1234', $principal->apiKeyId());
    }

    public function test_preserves_pre_set_api_key_principal_on_v2_route(): void
    {
        // If ApiKeyAuth has already constructed the ApiKeyPrincipal
        // itself, we honour it verbatim so the apiKeyId is preserved.
        $existing = new ApiKeyPrincipal(accountId: 5, apiKeyId: 'pre-existing');

        $middleware = app(ResolvePrincipal::class);
        $request = Request::create('/api/v2/shares/abc', 'GET');
        $request->attributes->set('principal', $existing);

        $middleware->handle($request, fn ($r) => new Response('ok'));

        $this->assertSame($existing, $request->attributes->get('principal'));
    }

    public function test_v2_request_without_api_key_falls_back_to_ip(): void
    {
        // ResolvePrincipal does not itself parse the bearer token; the
        // ApiKeyAuth middleware (task 25.2) is responsible for that and
        // for issuing 401 if the route requires it. Until that lands,
        // an unauthenticated v2 request should look like a guest IP
        // request — *not* a hard 500.
        $middleware = app(ResolvePrincipal::class);
        $request = Request::create('/api/v2/shares', 'GET', server: ['REMOTE_ADDR' => '198.51.100.99']);

        $middleware->handle($request, fn ($r) => new Response('ok'));

        $principal = $request->attributes->get('principal');
        $this->assertInstanceOf(IpPrincipal::class, $principal);
        $this->assertSame('198.51.100.99', $principal->identifier());
    }

    public function test_macro_returns_ip_principal_when_attribute_is_unset(): void
    {
        // Defensive: the macro must never return null even if it is
        // somehow called outside the middleware stack (e.g. during a
        // unit test that constructs a Request manually). The fallback
        // is an IpPrincipal derived from the request IP.
        $request = Request::create('/anything', 'GET', server: ['REMOTE_ADDR' => '192.0.2.10']);

        $principal = $request->principal();

        $this->assertInstanceOf(IpPrincipal::class, $principal);
        $this->assertSame('192.0.2.10', $principal->identifier());
    }
}
