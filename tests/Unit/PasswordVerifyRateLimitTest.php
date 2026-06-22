<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\PasswordVerifyRateLimit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Unit tests for {@see \App\Http\Middleware\PasswordVerifyRateLimit}
 * (task 4.4).
 *
 * Exercises Requirement 2.7 directly:
 *   - bucket key is `(ip, share_id)`
 *   - threshold default is 5 failures
 *   - past the threshold, the middleware short-circuits with HTTP 401
 *     before bcrypt is ever invoked
 *   - response body is identical to the SharePasswordGate body so the
 *     rate-limited case cannot be told apart from a wrong-password case
 *     (Requirement 2.6).
 *
 * The middleware itself only *reads* the bucket; the verification
 * controller (downstream) is responsible for `RateLimiter::hit()` on
 * failure and `RateLimiter::clear()` on success. We simulate that
 * controller by hitting the same key directly in the tests.
 */
class PasswordVerifyRateLimitTest extends TestCase
{
    private PasswordVerifyRateLimit $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        // Every test gets a clean limiter store so buckets do not leak
        // between cases. The cache driver is `array` per phpunit.xml.
        $this->middleware = new PasswordVerifyRateLimit();
    }

    public function test_passes_through_when_no_share_param_resolved(): void
    {
        // Without a share id, this middleware has nothing to scope on
        // and must defer to the gate / controller for rejection.
        $request = Request::create('/some/path', 'POST');
        $route = new Route(['POST'], '/some/path', []);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $called = false;
        $response = $this->middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok', 200);
        });

        $this->assertTrue($called);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_passes_through_when_bucket_below_threshold(): void
    {
        $request = $this->makeRequest('203.0.113.10', 'share-uuid-1');

        $called = false;
        $response = $this->middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok', 200);
        });

        $this->assertTrue($called);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_blocks_with_401_when_bucket_exhausted(): void
    {
        $request = $this->makeRequest('203.0.113.10', 'share-uuid-2');
        $key = PasswordVerifyRateLimit::keyFor($request, 'share-uuid-2');
        $maxAttempts = (int) config('airtoshare.password_verify_rate_limit.max_attempts', 5);

        // Simulate the controller recording $maxAttempts failed bcrypt
        // attempts. The bucket is now exhausted.
        for ($i = 0; $i < $maxAttempts; $i++) {
            RateLimiter::hit($key, 60);
        }

        $called = false;
        $response = $this->middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok', 200);
        });

        $this->assertFalse($called, 'Downstream handler must not run once the bucket is saturated.');
        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString(
            PasswordVerifyRateLimit::ERROR_MESSAGE,
            (string) $response->getContent()
        );
    }

    public function test_buckets_are_scoped_per_share(): void
    {
        // A saturated bucket for one share must NOT carry over to a
        // different share. Otherwise an attacker could brute-force all
        // shares for an IP by hammering one of them.
        $shareA = $this->makeRequest('203.0.113.10', 'share-A');
        $shareB = $this->makeRequest('203.0.113.10', 'share-B');

        $keyA = PasswordVerifyRateLimit::keyFor($shareA, 'share-A');
        $maxAttempts = (int) config('airtoshare.password_verify_rate_limit.max_attempts', 5);
        for ($i = 0; $i < $maxAttempts; $i++) {
            RateLimiter::hit($keyA, 60);
        }

        $responseA = $this->middleware->handle($shareA, fn () => new Response('ok', 200));
        $responseB = $this->middleware->handle($shareB, fn () => new Response('ok', 200));

        $this->assertSame(401, $responseA->getStatusCode());
        $this->assertSame(200, $responseB->getStatusCode());
    }

    public function test_buckets_are_scoped_per_ip(): void
    {
        // Same share, two IPs: one IP's failures must not block another.
        $alice = $this->makeRequest('203.0.113.10', 'share-shared');
        $eve = $this->makeRequest('203.0.113.99', 'share-shared');

        $keyAlice = PasswordVerifyRateLimit::keyFor($alice, 'share-shared');
        $maxAttempts = (int) config('airtoshare.password_verify_rate_limit.max_attempts', 5);
        for ($i = 0; $i < $maxAttempts; $i++) {
            RateLimiter::hit($keyAlice, 60);
        }

        $responseAlice = $this->middleware->handle($alice, fn () => new Response('ok', 200));
        $responseEve = $this->middleware->handle($eve, fn () => new Response('ok', 200));

        $this->assertSame(401, $responseAlice->getStatusCode());
        $this->assertSame(200, $responseEve->getStatusCode());
    }

    public function test_blocked_response_is_json_when_request_expects_json(): void
    {
        $request = $this->makeRequest('203.0.113.10', 'share-json', '/api/v1/shares/share-json/password');
        $request->headers->set('Accept', 'application/json');

        $key = PasswordVerifyRateLimit::keyFor($request, 'share-json');
        $maxAttempts = (int) config('airtoshare.password_verify_rate_limit.max_attempts', 5);
        for ($i = 0; $i < $maxAttempts; $i++) {
            RateLimiter::hit($key, 60);
        }

        $response = $this->middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame('error', $payload['status']);
        $this->assertSame(PasswordVerifyRateLimit::ERROR_MESSAGE, $payload['message']);
    }

    public function test_key_for_uses_ip_and_share_id(): void
    {
        $request = $this->makeRequest('198.51.100.42', 'abc123');

        $expected = PasswordVerifyRateLimit::LIMITER . ':198.51.100.42|abc123';
        $this->assertSame($expected, PasswordVerifyRateLimit::keyFor($request, 'abc123'));
    }

    public function test_route_param_object_with_get_key_is_used_for_bucket(): void
    {
        // Route model binding produces an object; the middleware should
        // call getKey() to derive the bucket id. We use a lightweight
        // anonymous class instead of a real Eloquent model to keep the
        // test free of database setup.
        $bound = new class {
            public function getKey(): int { return 77; }
        };

        $request = Request::create('/some/path', 'POST', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $route = new Route(['POST'], '/some/path/{share}', []);
        $route->bind($request);
        $route->setParameter('share', $bound);
        $request->setRouteResolver(fn () => $route);

        // No hits yet → pass through.
        $response = $this->middleware->handle($request, fn () => new Response('ok', 200));
        $this->assertSame(200, $response->getStatusCode());

        // Hit the bucket using the same key shape the middleware would
        // compute for $bound, then verify the next request short-circuits.
        $key = PasswordVerifyRateLimit::keyFor($request, '77');
        $maxAttempts = (int) config('airtoshare.password_verify_rate_limit.max_attempts', 5);
        for ($i = 0; $i < $maxAttempts; $i++) {
            RateLimiter::hit($key, 60);
        }
        $response2 = $this->middleware->handle($request, fn () => new Response('ok', 200));
        $this->assertSame(401, $response2->getStatusCode());
    }

    public function test_threshold_honours_configured_max_attempts(): void
    {
        config()->set('airtoshare.password_verify_rate_limit.max_attempts', 2);

        $request = $this->makeRequest('203.0.113.10', 'share-conf');
        $key = PasswordVerifyRateLimit::keyFor($request, 'share-conf');

        // 1 hit → still under threshold.
        RateLimiter::hit($key, 60);
        $response = $this->middleware->handle($request, fn () => new Response('ok', 200));
        $this->assertSame(200, $response->getStatusCode());

        // 2 hits → reached threshold; further requests are blocked.
        RateLimiter::hit($key, 60);
        $response = $this->middleware->handle($request, fn () => new Response('ok', 200));
        $this->assertSame(401, $response->getStatusCode());

        config()->set('airtoshare.password_verify_rate_limit.max_attempts', 5);
    }

    /**
     * Build a request whose route exposes the share parameter as a
     * plain string id, mirroring how the SharePasswordGate-protected
     * verification controller will be wired in routes/web.php.
     */
    private function makeRequest(string $ip, string $shareId, string $path = '/share-pw/share-id'): Request
    {
        $request = Request::create($path, 'POST', server: ['REMOTE_ADDR' => $ip]);
        $route = new Route(['POST'], '/share-pw/{share}', []);
        $route->bind($request);
        $route->setParameter('share', $shareId);
        $request->setRouteResolver(fn () => $route);

        return $request;
    }
}
