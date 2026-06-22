<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\RoomCodeRateLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Unit tests for {@see \App\Http\Middleware\RoomCodeRateLimit}
 * (task 12.2).
 *
 * Validates Requirement 7.8 directly:
 *   - bucket key is `room_invalid:{ip}`
 *   - 10 invalid submissions inside `decay_seconds` (default 60s) flip
 *     the sticky `room_blocked:{ip}` flag for `block_seconds`
 *     (default 5 min)
 *   - while blocked, the middleware short-circuits with HTTP 429 and a
 *     non-disclosing body, *without* invoking the downstream handler
 *     (i.e. without performing a lookup)
 *   - clearing on a successful match releases the bucket so legitimate
 *     users are not penalised by prior typos
 *   - buckets are scoped per IP so one attacker cannot block other IPs
 */
class RoomCodeRateLimitTest extends TestCase
{
    private RoomCodeRateLimit $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        // Pin every config knob the middleware consumes so tests are
        // immune to default drift in config/airtoshare.php.
        config()->set('airtoshare.room_code_rate_limit.max_attempts', 10);
        config()->set('airtoshare.room_code_rate_limit.decay_seconds', 60);
        config()->set('airtoshare.room_code_rate_limit.block_seconds', 5 * 60);

        $this->middleware = new RoomCodeRateLimit();
    }

    protected function tearDown(): void
    {
        // The cache store is `array` (per phpunit.xml) so it is reset
        // between tests automatically when the application is rebuilt.
        // Clear the explicit keys defensively in case a future suite
        // shares a container.
        Cache::flush();
        parent::tearDown();
    }

    public function test_passes_through_when_no_invalid_submissions_recorded(): void
    {
        // Acceptance criterion 7.8 default: an unmonitored IP just
        // forwards to the downstream handler.
        $request = $this->makeRequest('203.0.113.10');

        $called = false;
        $response = $this->middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok', 200);
        });

        $this->assertTrue($called);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_block_fires_after_threshold_and_short_circuits(): void
    {
        // Acceptance criterion 7.8: 10 invalid submissions in 60 s flip
        // the block flag; the next request returns 429 without running
        // the downstream handler (i.e. no lookup performed).
        $request = $this->makeRequest('203.0.113.10');

        // Simulate 10 failed attempts via the controller's contract.
        for ($i = 0; $i < 10; $i++) {
            RoomCodeRateLimit::recordInvalidAttempt($request);
        }

        $called = false;
        $response = $this->middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok', 200);
        });

        $this->assertFalse($called, 'Downstream handler must not run while blocked.');
        $this->assertSame(429, $response->getStatusCode());
        $this->assertStringContainsString(
            RoomCodeRateLimit::ERROR_MESSAGE,
            (string) $response->getContent(),
        );
    }

    public function test_below_threshold_does_not_block(): void
    {
        $request = $this->makeRequest('203.0.113.10');

        // 9 hits should NOT trip the block (default threshold is 10).
        for ($i = 0; $i < 9; $i++) {
            RoomCodeRateLimit::recordInvalidAttempt($request);
        }

        $called = false;
        $response = $this->middleware->handle($request, function () use (&$called) {
            $called = true;
            return new Response('ok', 200);
        });

        $this->assertTrue($called);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse(RoomCodeRateLimit::isIpBlocked($request));
    }

    public function test_buckets_are_scoped_per_ip(): void
    {
        // A blocked attacker IP must not lock out a different,
        // legitimate IP. This is the exact wording of Requirement 7.8
        // ("from the same IP within a rolling 60-second window").
        $alice = $this->makeRequest('203.0.113.10');
        $eve = $this->makeRequest('203.0.113.99');

        for ($i = 0; $i < 10; $i++) {
            RoomCodeRateLimit::recordInvalidAttempt($alice);
        }

        $aliceResponse = $this->middleware->handle($alice, fn () => new Response('ok', 200));
        $eveResponse = $this->middleware->handle($eve, fn () => new Response('ok', 200));

        $this->assertSame(429, $aliceResponse->getStatusCode());
        $this->assertSame(200, $eveResponse->getStatusCode());
    }

    public function test_clear_releases_the_bucket_and_block(): void
    {
        // Successful match clears both the hit counter AND the sticky
        // block flag, so a recipient who eventually types the right
        // code after a few typos can proceed.
        $request = $this->makeRequest('203.0.113.10');

        for ($i = 0; $i < 10; $i++) {
            RoomCodeRateLimit::recordInvalidAttempt($request);
        }
        $this->assertTrue(RoomCodeRateLimit::isIpBlocked($request));

        RoomCodeRateLimit::clear($request);

        $this->assertFalse(RoomCodeRateLimit::isIpBlocked($request));

        $response = $this->middleware->handle($request, fn () => new Response('ok', 200));
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_block_response_is_json_for_api_callers(): void
    {
        $request = $this->makeRequest('203.0.113.10', '/api/v2/rooms/lookup');
        $request->headers->set('Accept', 'application/json');

        for ($i = 0; $i < 10; $i++) {
            RoomCodeRateLimit::recordInvalidAttempt($request);
        }

        $response = $this->middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame('error', $payload['status']);
        $this->assertSame(RoomCodeRateLimit::ERROR_MESSAGE, $payload['message']);
    }

    public function test_record_invalid_attempt_returns_running_total(): void
    {
        // Sanity check on the helper's return value so callers can log
        // the running counter without re-querying the bucket.
        $request = $this->makeRequest('203.0.113.10');

        $this->assertSame(1, RoomCodeRateLimit::recordInvalidAttempt($request));
        $this->assertSame(2, RoomCodeRateLimit::recordInvalidAttempt($request));
        $this->assertSame(3, RoomCodeRateLimit::recordInvalidAttempt($request));
    }

    public function test_threshold_honours_configured_max_attempts(): void
    {
        // Drop the threshold to 3 so the test stays readable. The
        // middleware must read the configured value rather than a
        // hard-coded constant.
        config()->set('airtoshare.room_code_rate_limit.max_attempts', 3);

        $request = $this->makeRequest('203.0.113.10');

        // 2 hits → still under threshold.
        RoomCodeRateLimit::recordInvalidAttempt($request);
        RoomCodeRateLimit::recordInvalidAttempt($request);
        $response = $this->middleware->handle($request, fn () => new Response('ok', 200));
        $this->assertSame(200, $response->getStatusCode());

        // 3rd hit reaches the threshold; the next request blocks.
        RoomCodeRateLimit::recordInvalidAttempt($request);
        $response = $this->middleware->handle($request, fn () => new Response('ok', 200));
        $this->assertSame(429, $response->getStatusCode());
    }

    public function test_block_persists_after_bucket_decay(): void
    {
        // Critical Requirement 7.8 invariant: the 5-minute block must
        // outlive the 60-second hit window. Even if the underlying
        // RateLimiter bucket is cleared (its TTL elapsed), the sticky
        // block flag must keep the IP locked out.
        $request = $this->makeRequest('203.0.113.10');

        for ($i = 0; $i < 10; $i++) {
            RoomCodeRateLimit::recordInvalidAttempt($request);
        }

        // Simulate the bucket decaying away while the block flag stays.
        RateLimiter::clear(RoomCodeRateLimit::BUCKET_PREFIX . '203.0.113.10');

        $response = $this->middleware->handle($request, fn () => new Response('ok', 200));

        $this->assertSame(429, $response->getStatusCode(), 'Block flag must outlive the hit bucket.');
    }

    private function makeRequest(string $ip, string $path = '/r/ABCDEF'): Request
    {
        return Request::create($path, 'GET', server: ['REMOTE_ADDR' => $ip]);
    }
}
