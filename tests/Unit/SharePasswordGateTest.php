<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\SharePasswordGate;
use App\Models\Share;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Unit tests for {@see \App\Http\Middleware\SharePasswordGate} (task 4.4).
 *
 * Validates the four-branch decision tree drawn in design.md >
 * Components > Password_Manager. The middleware is exercised in
 * isolation by simulating route binding and an array session store; we
 * deliberately do not boot a full HTTP kernel so the tests stay fast
 * and focused on the gate's contract.
 *
 * Acceptance criteria covered:
 *   2.3 - 401 when no session token and password_hash is set.
 *   2.4 - pass-through when password_hash is null.
 *   2.6 - response body is identical between "no share" and "wrong
 *         password" paths (non-disclosing).
 */
class SharePasswordGateTest extends TestCase
{
    private SharePasswordGate $gate;

    protected function setUp(): void
    {
        // In-memory SQLite so the gate's UUID/slug fallback lookups
        // resolve against a real schema without touching the dev DB.
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';

        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        \DB::purge('sqlite');

        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->string('owner_type');
            $table->string('owner_id');
            $table->longText('text_content')->nullable();
            $table->longText('markdown_source')->nullable();
            $table->string('password_hash')->nullable();
            $table->timestamp('expires_at');
            $table->char('public_slug', 12)->nullable()->unique();
            $table->unsignedInteger('public_view_count')->default(0);
            $table->boolean('is_e2ee')->default(false);
            $table->boolean('is_favourite')->default(false);
            $table->timestamps();
        });

        $this->gate = new SharePasswordGate();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_passes_through_when_share_has_no_password(): void
    {
        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.10',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $request = $this->makeRequest($share);
        $called = false;

        $response = $this->gate->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok', 200);
        });

        $this->assertTrue($called, 'next() must be invoked when password_hash is null.');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    public function test_passes_through_when_session_already_verified(): void
    {
        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.10',
            'password_hash' => 'irrelevant-hash',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $request = $this->makeRequest($share);
        $request->setLaravelSession($this->makeSession([
            SharePasswordGate::SESSION_KEY => [$share->id => true],
        ]));

        $called = false;
        $response = $this->gate->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok', 200);
        });

        $this->assertTrue($called);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_returns_401_when_password_required_and_session_unverified(): void
    {
        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.10',
            'password_hash' => 'irrelevant-hash',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $request = $this->makeRequest($share);

        $called = false;
        $response = $this->gate->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok', 200);
        });

        $this->assertFalse($called, 'next() must NOT run when password is required.');
        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString(SharePasswordGate::ERROR_MESSAGE, (string) $response->getContent());
    }

    public function test_returns_401_when_session_flag_is_false(): void
    {
        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.10',
            'password_hash' => 'irrelevant-hash',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $request = $this->makeRequest($share);
        $request->setLaravelSession($this->makeSession([
            SharePasswordGate::SESSION_KEY => [$share->id => false],
        ]));

        $response = $this->gate->handle($request, fn () => new Response('ok', 200));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_returns_401_when_route_param_is_missing(): void
    {
        // Requirement 2.6: the body for "no share at all" must be the
        // same shape as "wrong password" so a probe cannot distinguish
        // the two outcomes.
        $request = Request::create('/s/missing', 'GET');
        $route = new Route(['GET'], '/s/{share}', []);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $response = $this->gate->handle($request, fn () => new Response('ok', 200));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_resolves_share_by_uuid_string_param(): void
    {
        // When route model binding is not configured, the gate should
        // still resolve a Share via uuid lookup. Pass-through expected
        // because no password is set.
        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.10',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $request = Request::create('/s/' . $share->uuid, 'GET');
        $route = new Route(['GET'], '/s/{share}', []);
        $route->bind($request);
        $route->setParameter('share', $share->uuid);
        $request->setRouteResolver(fn () => $route);

        $response = $this->gate->handle($request, fn () => new Response('ok', 200));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_resolves_share_by_public_slug_param(): void
    {
        $share = Share::create([
            'owner_type' => 'account',
            'owner_id' => '7',
            'public_slug' => 'abcdef123456',
            'password_hash' => 'irrelevant-hash',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $request = Request::create('/p/' . $share->public_slug, 'GET');
        $route = new Route(['GET'], '/p/{share}', []);
        $route->bind($request);
        $route->setParameter('share', $share->public_slug);
        $request->setRouteResolver(fn () => $route);

        $response = $this->gate->handle($request, fn () => new Response('ok', 200));

        // Password set + no session token = 401.
        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_returns_json_body_for_api_paths(): void
    {
        // Requirement 2.3: JSON callers (typically /api/v1/* or
        // Accept: application/json) get the structured error envelope.
        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.10',
            'password_hash' => 'irrelevant-hash',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $request = Request::create('/api/v1/shares/' . $share->uuid, 'GET');
        $request->headers->set('Accept', 'application/json');
        $route = new Route(['GET'], '/api/v1/shares/{share}', []);
        $route->bind($request);
        $route->setParameter('share', $share);
        $request->setRouteResolver(fn () => $route);

        $response = $this->gate->handle($request, fn () => new Response('ok'));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame('error', $payload['status']);
        $this->assertSame(SharePasswordGate::ERROR_MESSAGE, $payload['message']);
    }

    public function test_no_share_response_body_matches_wrong_password_response_body(): void
    {
        // Requirement 2.6: byte-for-byte equality of the unauthorised
        // body across the "no such share" and "wrong session" paths.
        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.10',
            'password_hash' => 'irrelevant-hash',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $missingRequest = Request::create('/api/v1/shares/00000000-0000-4000-8000-000000000000', 'GET');
        $missingRequest->headers->set('Accept', 'application/json');
        $missingRoute = new Route(['GET'], '/api/v1/shares/{share}', []);
        $missingRoute->bind($missingRequest);
        $missingRoute->setParameter('share', '00000000-0000-4000-8000-000000000000');
        $missingRequest->setRouteResolver(fn () => $missingRoute);

        $wrongRequest = Request::create('/api/v1/shares/' . $share->uuid, 'GET');
        $wrongRequest->headers->set('Accept', 'application/json');
        $wrongRoute = new Route(['GET'], '/api/v1/shares/{share}', []);
        $wrongRoute->bind($wrongRequest);
        $wrongRoute->setParameter('share', $share);
        $wrongRequest->setRouteResolver(fn () => $wrongRoute);

        $missingResponse = $this->gate->handle($missingRequest, fn () => new Response('ok'));
        $wrongResponse = $this->gate->handle($wrongRequest, fn () => new Response('ok'));

        $this->assertSame($missingResponse->getStatusCode(), $wrongResponse->getStatusCode());
        $this->assertSame((string) $missingResponse->getContent(), (string) $wrongResponse->getContent());
    }

    public function test_supports_alternate_route_parameter_name(): void
    {
        // Test that the middleware honours its $parameter argument so
        // it can be applied to routes that name the bound model
        // differently (e.g. /p/{slug} for the public gallery).
        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.10',
            'public_slug' => 'altparam12345',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $request = Request::create('/p/' . $share->public_slug, 'GET');
        $route = new Route(['GET'], '/p/{slug}', []);
        $route->bind($request);
        $route->setParameter('slug', $share->public_slug);
        $request->setRouteResolver(fn () => $route);

        // Without the alternate parameter, the gate looks for `share`,
        // does not find anything, and returns 401.
        $defaultResponse = $this->gate->handle($request, fn () => new Response('ok'));
        $this->assertSame(401, $defaultResponse->getStatusCode());

        // With the alternate parameter passed via middleware args, the
        // gate finds the share and (since no password is set) lets the
        // request through.
        $request2 = Request::create('/p/' . $share->public_slug, 'GET');
        $route2 = new Route(['GET'], '/p/{slug}', []);
        $route2->bind($request2);
        $route2->setParameter('slug', $share->public_slug);
        $request2->setRouteResolver(fn () => $route2);

        $aliasedResponse = $this->gate->handle(
            $request2,
            fn () => new Response('ok', 200),
            'slug'
        );
        $this->assertSame(200, $aliasedResponse->getStatusCode());
    }

    public function test_session_map_keyed_by_share_primary_key(): void
    {
        // The session map is keyed by the integer primary key, NOT the
        // uuid, so middleware and controller agree on the canonical
        // identifier.
        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.10',
            'password_hash' => 'irrelevant-hash',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $request = $this->makeRequest($share);
        $request->setLaravelSession($this->makeSession([
            // Wrong key (uuid instead of primary key).
            SharePasswordGate::SESSION_KEY => [$share->uuid => true],
        ]));

        $response = $this->gate->handle($request, fn () => new Response('ok', 200));

        $this->assertSame(401, $response->getStatusCode());
    }

    /**
     * Build an in-memory request with a bound Share route param so the
     * gate's primary lookup branch is exercised.
     */
    private function makeRequest(Share $share, string $method = 'GET'): Request
    {
        $request = Request::create('/s/' . $share->uuid, $method);
        $route = new Route([$method], '/s/{share}', []);
        $route->bind($request);
        $route->setParameter('share', $share);
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    private function makeSession(array $data = []): Store
    {
        $store = new Store('test-session', new \Illuminate\Session\ArraySessionHandler(60));
        foreach ($data as $key => $value) {
            $store->put($key, $value);
        }
        return $store;
    }
}
