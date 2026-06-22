<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\RoomCodeRateLimit;
use App\Http\Middleware\SharePasswordGate;
use App\Models\Room;
use App\Models\Share;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Feature tests for {@see \App\Http\Controllers\RoomController}
 * (task 12.2).
 *
 * Covers acceptance criteria for the controller end of Requirement 7:
 *   7.3 - case-insensitive lookup with a valid code grants access.
 *   7.4 - bad format / unknown / expired code surfaces a generic 404
 *         and does NOT modify Room state.
 *   7.7 - password-protected rooms delegate to SharePasswordGate;
 *         unverified callers receive the same 401 body the gate emits.
 *   7.8 - 10 invalid submissions in 60s flips the per-IP rate-limit
 *         block; subsequent requests are short-circuited with HTTP 429
 *         without performing a Room Code lookup.
 *
 * Uses an in-memory SQLite schema so the controller's findByCode path
 * runs against real Eloquent rows without touching the dev database.
 */
class RoomControllerTest extends TestCase
{
    protected function setUp(): void
    {
        // Pin the DB connection BEFORE the framework boots so the
        // application picks up the in-memory SQLite settings.
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION']    = 'sqlite';
        $_ENV['DB_DATABASE']      = ':memory:';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE']   = ':memory:';

        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
            'foreign_key_constraints' => false,
        ]);
        \DB::purge('sqlite');

        // Pin the rate-limit thresholds so the test names match the
        // numbers used in the assertions even if config defaults drift.
        config()->set('airtoshare.room_code_rate_limit.max_attempts', 10);
        config()->set('airtoshare.room_code_rate_limit.decay_seconds', 60);
        config()->set('airtoshare.room_code_rate_limit.block_seconds', 5 * 60);

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->char('code', 6)->unique();
            $table->string('password_hash')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('last_activity_at')->nullable();
            $table->mediumText('clipboard_text')->nullable();
            $table->timestamp('clipboard_updated_at')->nullable();
            $table->timestamps();
        });

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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Cache::flush();
        parent::tearDown();
    }

    // -- store ---------------------------------------------------------------

    public function test_store_creates_a_room_and_returns_code_and_url(): void
    {
        Carbon::setTestNow('2030-01-01 12:00:00');

        $response = $this->postJson('/rooms', [
            'expiry' => '1h',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['status', 'code', 'expires_at', 'url']);
        $body = $response->json();
        $this->assertSame('success', $body['status']);
        $this->assertSame(6, strlen($body['code']));
        $this->assertSame(1, preg_match('/^[A-HJ-NP-Z2-9]{6}$/', $body['code']));
        $this->assertStringEndsWith('/r/' . $body['code'], $body['url']);

        // The Room is persisted (not just held in memory).
        $this->assertDatabaseHas('rooms', ['code' => $body['code']]);
    }

    public function test_store_uses_24h_default_expiry_when_omitted(): void
    {
        // Acceptance criterion 3.2 carries through: omitting expiry on
        // a Room create defaults to 24h.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $response = $this->postJson('/rooms', []);

        $response->assertStatus(201);
        $expiresAt = $response->json('expires_at');
        $this->assertNotNull($expiresAt);
        $this->assertSame(
            '2030-01-02T12:00:00+00:00',
            $expiresAt,
        );
    }

    public function test_store_rejects_30d_expiry_for_room_owner(): void
    {
        // Acceptance criterion 7.5: rooms do not get the 30d window.
        $response = $this->postJson('/rooms', ['expiry' => '30d']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['expiry']);
        // No Room created.
        $this->assertSame(0, Room::count());
    }

    public function test_store_rejects_invalid_expiry_option(): void
    {
        // Acceptance criterion 3.5: invalid option ⇒ 422 with no Room
        // creation side effect.
        $response = $this->postJson('/rooms', ['expiry' => 'banana']);

        $response->assertStatus(422);
        $this->assertSame(0, Room::count());
    }

    public function test_store_rejects_short_password(): void
    {
        // PasswordManager enforces 6..128. Surfaced as 422.
        $response = $this->postJson('/rooms', [
            'expiry' => '1h',
            'password' => 'abc',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
        $this->assertSame(0, Room::count());
    }

    public function test_store_persists_password_hash_when_provided(): void
    {
        // Acceptance criterion 7.7: optional password is hashed and stored.
        $response = $this->postJson('/rooms', [
            'expiry' => '1h',
            'password' => 'hunter22',
        ]);
        $response->assertStatus(201);

        /** @var Room $room */
        $room = Room::firstWhere('code', $response->json('code'));
        $this->assertNotNull($room->password_hash);
        $this->assertStringStartsWith('$2', $room->password_hash);
        $this->assertStringNotContainsString('hunter22', $room->password_hash);
    }

    // -- show: lookup --------------------------------------------------------

    public function test_show_redirects_to_share_for_valid_code(): void
    {
        // Acceptance criterion 7.3: valid code grants access to the
        // Room's Share.
        $room = $this->makeRoom();
        $share = Share::create([
            'owner_type' => Share::OWNER_TYPE_ROOM,
            'owner_id'   => (string) $room->id,
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $response = $this->get('/r/' . $room->code);

        $response->assertStatus(302);
        $response->assertRedirect('/s/' . $share->uuid);
    }

    public function test_show_is_case_insensitive(): void
    {
        // Acceptance criterion 7.3 explicitly mandates case-insensitive
        // matching - users frequently retype the code in their own case.
        $room = $this->makeRoom();
        $share = Share::create([
            'owner_type' => Share::OWNER_TYPE_ROOM,
            'owner_id'   => (string) $room->id,
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $response = $this->get('/r/' . strtolower($room->code));

        $response->assertStatus(302);
        $response->assertRedirect('/s/' . $share->uuid);
    }

    public function test_show_returns_landing_when_room_has_no_share(): void
    {
        // Until the room has any shared content, the landing payload
        // identifies the room without a redirect target.
        $room = $this->makeRoom();

        $response = $this->getJson('/r/' . $room->code);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'code'   => $room->code,
        ]);
    }

    public function test_show_returns_404_for_unknown_code(): void
    {
        // Acceptance criterion 7.4: unknown ⇒ generic 404.
        $response = $this->getJson('/r/ZZZZZZ');

        $response->assertStatus(404);
        $response->assertJson(['status' => 'error']);
    }

    public function test_show_returns_404_for_expired_room(): void
    {
        // Acceptance criterion 7.4: expired ⇒ same 404.
        Carbon::setTestNow('2030-01-01 12:00:00');
        $room = Room::create([
            'code' => 'ABCDEF',
            'expires_at' => Carbon::now()->subSecond(),
        ]);

        $response = $this->getJson('/r/ABCDEF');

        $response->assertStatus(404);
        // Lookup must NOT delete the row (deletion is the cleanup
        // command's job, conditioned on inactivity too).
        $this->assertNotNull(Room::find($room->id));
    }

    public function test_show_returns_404_for_invalid_format(): void
    {
        // Acceptance criterion 7.4: bad format ⇒ same 404, no DB hit.
        $response = $this->getJson('/r/AB-DEF');

        $response->assertStatus(404);
    }

    public function test_show_404_bodies_are_byte_identical_across_failure_modes(): void
    {
        // Non-disclosing 404 (Requirement 7.4): a probe must not be
        // able to tell "bad format" from "unknown" from "expired".
        Carbon::setTestNow('2030-01-01 12:00:00');
        Room::create([
            'code' => 'BCDEFG',
            'expires_at' => Carbon::now()->subSecond(),
        ]);

        $badFormat = $this->getJson('/r/AB-DEF');
        $unknown   = $this->getJson('/r/ZZZZZZ');
        $expired   = $this->getJson('/r/BCDEFG');

        $this->assertSame(404, $badFormat->getStatusCode());
        $this->assertSame(404, $unknown->getStatusCode());
        $this->assertSame(404, $expired->getStatusCode());
        $this->assertSame((string) $badFormat->getContent(), (string) $unknown->getContent());
        $this->assertSame((string) $unknown->getContent(), (string) $expired->getContent());
    }

    // -- show: password gate -------------------------------------------------

    public function test_show_returns_401_for_password_protected_room_without_session(): void
    {
        // Acceptance criterion 7.7 + 2.3: password-protected room with
        // no session token ⇒ 401 with the SharePasswordGate body.
        $room = Room::create([
            'code'          => 'BCDEFG',
            'password_hash' => 'irrelevant-hash',
            'expires_at'    => Carbon::now()->addHour(),
        ]);
        Share::create([
            'owner_type' => Share::OWNER_TYPE_ROOM,
            'owner_id'   => (string) $room->id,
            'password_hash' => 'irrelevant-hash',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $response = $this->getJson('/r/BCDEFG');

        $response->assertStatus(401);
        $response->assertJson(['message' => SharePasswordGate::ERROR_MESSAGE]);
    }

    public function test_show_grants_access_when_session_has_verified_share(): void
    {
        // Acceptance criterion 7.7: a session with the
        // SharePasswordGate flag set passes through the room gate too.
        $room = Room::create([
            'code'          => 'CDEFGH',
            'password_hash' => 'irrelevant-hash',
            'expires_at'    => Carbon::now()->addHour(),
        ]);
        $share = Share::create([
            'owner_type' => Share::OWNER_TYPE_ROOM,
            'owner_id'   => (string) $room->id,
            'password_hash' => 'irrelevant-hash',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $response = $this->withSession([
            SharePasswordGate::SESSION_KEY => [$share->id => true],
        ])->get('/r/CDEFGH');

        $response->assertStatus(302);
        $response->assertRedirect('/s/' . $share->uuid);
    }

    // -- show: rate limit ----------------------------------------------------

    public function test_show_records_invalid_attempts_against_per_ip_bucket(): void
    {
        // Acceptance criterion 7.8: 10 invalid submissions inside 60s
        // flip the per-IP block flag.
        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/r/ZZZZZZ', ['REMOTE_ADDR' => '203.0.113.10'])
                ->assertStatus(404);
        }

        $request = \Illuminate\Http\Request::create('/r/ZZZZZZ', 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $this->assertTrue(RoomCodeRateLimit::isIpBlocked($request));
    }

    public function test_show_returns_429_after_threshold_without_running_lookup(): void
    {
        // Acceptance criterion 7.8: while blocked, requests return 429
        // and do NOT hit the lookup (so even a *valid* code returns
        // 429 from the same IP). A valid Room exists, so if the
        // controller had run the lookup the response would be 200 / 302.
        $room = $this->makeRoom();

        // Saturate the bucket from this IP using bad codes.
        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/r/ZZZZZZ', ['REMOTE_ADDR' => '203.0.113.10'])
                ->assertStatus(404);
        }

        // Now the SAME IP attempts the valid code: must still return
        // 429 because the middleware short-circuits before the lookup.
        $response = $this->getJson('/r/' . $room->code, ['REMOTE_ADDR' => '203.0.113.10']);

        $response->assertStatus(429);
        $response->assertJson(['message' => RoomCodeRateLimit::ERROR_MESSAGE]);
    }

    public function test_show_clears_bucket_after_successful_match(): void
    {
        // Successful matches release prior typo penalties so the
        // recipient can keep using the room without artificial delay.
        $room = $this->makeRoom();

        // 9 typos, then the right code.
        for ($i = 0; $i < 9; $i++) {
            $this->getJson('/r/ZZZZZZ', ['REMOTE_ADDR' => '203.0.113.10'])
                ->assertStatus(404);
        }

        $this->getJson('/r/' . $room->code, ['REMOTE_ADDR' => '203.0.113.10'])
            ->assertStatus(200);

        $request = \Illuminate\Http\Request::create('/r/' . $room->code, 'GET', server: ['REMOTE_ADDR' => '203.0.113.10']);
        $this->assertFalse(RoomCodeRateLimit::isIpBlocked($request));

        // After the successful match, 10 fresh typos should still need
        // to accumulate from scratch before another block kicks in.
        for ($i = 0; $i < 9; $i++) {
            $this->getJson('/r/ZZZZZZ', ['REMOTE_ADDR' => '203.0.113.10'])
                ->assertStatus(404);
        }
        $this->assertFalse(RoomCodeRateLimit::isIpBlocked($request));
    }

    public function test_block_is_per_ip(): void
    {
        // Acceptance criterion 7.8 wording: "from the same IP". A
        // saturated attacker IP must not lock out other IPs.
        $room = $this->makeRoom();

        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/r/ZZZZZZ', ['REMOTE_ADDR' => '203.0.113.10'])
                ->assertStatus(404);
        }

        // Different IP, same valid code → 200.
        $response = $this->getJson('/r/' . $room->code, ['REMOTE_ADDR' => '198.51.100.42']);
        $response->assertStatus(200);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeRoom(array $attributes = []): Room
    {
        return Room::create(array_merge([
            'code'       => 'ABCDEF',
            'expires_at' => Carbon::now()->addHour(),
        ], $attributes));
    }
}
