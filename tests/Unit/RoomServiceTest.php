<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\RoomAllocationException;
use App\Models\Room;
use App\Services\RoomService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Tests for {@see \App\Services\RoomService} (task 12.1).
 *
 * Covers acceptance criteria:
 *   7.1 - 6-character codes drawn from `ABCDEFGHJKLMNPQRSTUVWXYZ23456789`.
 *   7.2 - up to 5 collision retries before raising RoomAllocationException.
 *   7.3 - case-insensitive lookup that excludes expired rooms.
 *   7.4 - lookup with bad / unknown / expired code returns null.
 *   7.5 - expiry options match the IP set (no 30d).
 *   7.7 - optional password is hashed and stored.
 *
 * Uses an in-memory SQLite database (mirrors ExpiryManagerTest's setup) so
 * the unique-index branch and case-insensitive lookup can be exercised
 * without touching the dev database.
 */
class RoomServiceTest extends TestCase
{
    private RoomService $service;

    protected function setUp(): void
    {
        // In-memory SQLite isolates the test from the dev DB. Matches the
        // pattern used by ExpiryManagerTest so the same setUp idiom is
        // recognisable across the suite.
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

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->char('code', 6)->unique();
            $table->string('password_hash')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('last_activity_at')->nullable();
            $table->mediumText('clipboard_text')->nullable();
            $table->timestamp('clipboard_updated_at')->nullable();
            $table->timestamps();

            $table->index('expires_at');
            $table->index('last_activity_at');
        });

        $this->service = $this->app->make(RoomService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // -- create --------------------------------------------------------------

    public function test_create_persists_a_room_with_a_six_character_code_from_the_alphabet(): void
    {
        // Acceptance criterion 7.1.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $room = $this->service->create('1h', null);

        $this->assertSame(6, strlen($room->code));
        // Every glyph is in the configured alphabet.
        $this->assertSame(
            1,
            preg_match('/^[A-HJ-NP-Z2-9]{6}$/', $room->code),
            "Code {$room->code} must contain only alphabet characters.",
        );
        // Persisted to the database (not just held in memory).
        $this->assertDatabaseHas('rooms', ['code' => $room->code]);
    }

    public function test_create_uses_default_24h_expiry_when_no_option_is_supplied(): void
    {
        // Acceptance criterion 7.5 + 3.2.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $room = $this->service->create(null, null);

        $this->assertSame(
            '2030-01-02 12:00:00',
            $room->expires_at->format('Y-m-d H:i:s'),
        );
    }

    public function test_create_accepts_each_ip_compatible_expiry_option(): void
    {
        // Acceptance criterion 7.5: rooms use the IP option set.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $cases = [
            '1h'  => '2030-01-01 13:00:00',
            '6h'  => '2030-01-01 18:00:00',
            '24h' => '2030-01-02 12:00:00',
            '7d'  => '2030-01-08 12:00:00',
        ];

        foreach ($cases as $option => $expected) {
            $room = $this->service->create($option, null);
            $this->assertSame(
                $expected,
                $room->expires_at->format('Y-m-d H:i:s'),
                "Option {$option} should resolve to {$expected}",
            );
        }
    }

    public function test_create_rejects_30d_expiry_for_a_room(): void
    {
        // Acceptance criterion 7.5: rooms do not get the 30d window.
        $this->expectException(\InvalidArgumentException::class);

        $this->service->create('30d', null);
    }

    public function test_create_without_password_leaves_password_hash_null(): void
    {
        $room = $this->service->create('1h', null);
        $this->assertNull($room->password_hash);
    }

    public function test_create_with_empty_password_string_leaves_password_hash_null(): void
    {
        // An empty string is documented to disable the password (so the
        // controller can pass `?? null` directly without distinguishing).
        $room = $this->service->create('1h', '');
        $this->assertNull($room->password_hash);
    }

    public function test_create_with_password_hashes_via_password_manager(): void
    {
        // Acceptance criterion 7.7.
        $room = $this->service->create('1h', 'hunter22');

        $this->assertNotNull($room->password_hash);
        // Bcrypt prefix.
        $this->assertStringStartsWith('$2', $room->password_hash);
        // Plaintext is never stored verbatim.
        $this->assertStringNotContainsString('hunter22', $room->password_hash);
    }

    public function test_create_rejects_password_below_minimum_length(): void
    {
        // PasswordManager enforces the 6..128 length window.
        $this->expectException(ValidationException::class);

        $this->service->create('1h', 'abc');
    }

    public function test_create_throws_room_allocation_exception_after_five_collisions(): void
    {
        // Acceptance criterion 7.2: after 5 collisions, raise the
        // dedicated exception. We exhaust the alphabet by stubbing
        // `Room::query()->where('code', ...)` via a real partial: insert
        // every code the deterministic generator would emit.
        //
        // Rather than mock `random_int` (impossible without runkit), we
        // pre-populate the table with a single row whose `code` matches
        // a specific value, then reach for a service with a stubbed
        // `generateCode` that always returns that value.
        $service = new class($this->app->make(\App\Services\ExpiryManager::class), $this->app->make(\App\Services\PasswordManager::class)) extends RoomService {
            protected function generateCode(): string
            {
                return 'AAAAAA';
            }
        };

        // Seed a row that always wins the existence check.
        Room::create([
            'code' => 'AAAAAA',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $this->expectException(RoomAllocationException::class);

        $service->create('1h', null);
    }

    public function test_create_succeeds_after_a_few_collisions_within_the_retry_budget(): void
    {
        // Verifies the retry mechanism actually retries instead of just
        // failing: a stubbed generator hands out three colliding codes
        // followed by a unique one, and the service must recover.
        $sequence = ['AAAAAA', 'AAAAAA', 'BBBBBB', 'CCCCCC'];
        $cursor = 0;

        $service = new class($this->app->make(\App\Services\ExpiryManager::class), $this->app->make(\App\Services\PasswordManager::class), $sequence, $cursor) extends RoomService {
            /** @var list<string> */
            private array $sequence;
            private int $cursor;

            public function __construct(\App\Services\ExpiryManager $em, \App\Services\PasswordManager $pm, array $sequence, int $cursor)
            {
                parent::__construct($em, $pm);
                $this->sequence = $sequence;
                $this->cursor = $cursor;
            }

            protected function generateCode(): string
            {
                $code = $this->sequence[$this->cursor] ?? 'ZZZZZZ';
                $this->cursor++;
                return $code;
            }
        };

        // Seed two existing rows: the first two outputs of the stub will
        // collide, so the service must reach the third (unique) entry.
        Room::create(['code' => 'AAAAAA', 'expires_at' => Carbon::now()->addHour()]);

        $room = $service->create('1h', null);

        $this->assertSame('BBBBBB', $room->code);
    }

    // -- findByCode ----------------------------------------------------------

    public function test_find_by_code_returns_room_when_active_and_format_valid(): void
    {
        // Acceptance criterion 7.3.
        $room = $this->service->create('1h', null);

        $found = $this->service->findByCode($room->code);

        $this->assertNotNull($found);
        $this->assertSame($room->id, $found->id);
    }

    public function test_find_by_code_is_case_insensitive(): void
    {
        // Acceptance criterion 7.3 explicitly mandates case-insensitive
        // matching - users frequently retype the code in their own case.
        $room = $this->service->create('1h', null);

        $found = $this->service->findByCode(strtolower($room->code));

        $this->assertNotNull($found);
        $this->assertSame($room->id, $found->id);
    }

    public function test_find_by_code_returns_null_for_unknown_code(): void
    {
        // Acceptance criterion 7.4: unknown code yields null, no state change.
        $beforeCount = Room::count();

        $this->assertNull($this->service->findByCode('ZZZZZZ'));
        $this->assertSame($beforeCount, Room::count());
    }

    public function test_find_by_code_returns_null_for_expired_room(): void
    {
        // Acceptance criterion 7.4 + 7.3: expired rooms behave like
        // unknown rooms to the lookup.
        Carbon::setTestNow('2030-01-01 12:00:00');
        $room = Room::create([
            'code' => 'ABCDEF',
            'expires_at' => Carbon::now()->subSecond(),
        ]);

        $this->assertNull($this->service->findByCode('ABCDEF'));
        // Lookup must not have modified the row (deletion is the
        // scheduled cleanup's job, conditioned on inactivity too).
        $this->assertNotNull(Room::find($room->id));
    }

    public function test_find_by_code_returns_null_for_invalid_format(): void
    {
        // Acceptance criterion 7.4: bad format short-circuits before the
        // database is consulted at all.
        $this->assertNull($this->service->findByCode('not-a-code'));
        $this->assertNull($this->service->findByCode('ABCDE'));   // too short
        $this->assertNull($this->service->findByCode('ABCDEFG')); // too long
        $this->assertNull($this->service->findByCode('ABCDE0'));  // contains "0"
        $this->assertNull($this->service->findByCode('ABCDE1'));  // contains "1"
        $this->assertNull($this->service->findByCode('ABCDEO'));  // contains "O"
        $this->assertNull($this->service->findByCode('ABCDEI'));  // contains "I"
    }

    public function test_find_by_code_at_exact_expiry_instant_treats_room_as_expired(): void
    {
        // Acceptance criterion 7.4 + 3.4 boundary: `expires_at <= now()`
        // is expired, including the exact instant.
        Carbon::setTestNow('2030-01-01 12:00:00');
        Room::create([
            'code' => 'BCDEFG',
            'expires_at' => '2030-01-01 12:00:00',
        ]);

        $this->assertNull($this->service->findByCode('BCDEFG'));
    }

    // -- validateFormat ------------------------------------------------------

    public function test_validate_format_accepts_canonical_uppercase_code(): void
    {
        $this->assertTrue($this->service->validateFormat('ABCDEF'));
    }

    public function test_validate_format_is_case_insensitive(): void
    {
        $this->assertTrue($this->service->validateFormat('abcdef'));
        $this->assertTrue($this->service->validateFormat('AbCdEf'));
    }

    public function test_validate_format_accepts_digits_2_through_9(): void
    {
        $this->assertTrue($this->service->validateFormat('234567'));
        $this->assertTrue($this->service->validateFormat('A2B3C4'));
    }

    public function test_validate_format_rejects_disallowed_glyphs(): void
    {
        // Acceptance criterion 7.1: O, I, 0, 1 are excluded from the
        // alphabet to avoid visual ambiguity.
        $this->assertFalse($this->service->validateFormat('O23456')); // O
        $this->assertFalse($this->service->validateFormat('I23456')); // I
        $this->assertFalse($this->service->validateFormat('023456')); // 0
        $this->assertFalse($this->service->validateFormat('123456')); // 1
    }

    public function test_validate_format_rejects_wrong_length(): void
    {
        $this->assertFalse($this->service->validateFormat(''));
        $this->assertFalse($this->service->validateFormat('ABCDE'));
        $this->assertFalse($this->service->validateFormat('ABCDEFG'));
    }

    public function test_validate_format_rejects_whitespace_and_punctuation(): void
    {
        $this->assertFalse($this->service->validateFormat('ABCDE '));
        $this->assertFalse($this->service->validateFormat(' BCDEF'));
        $this->assertFalse($this->service->validateFormat('ABC-EF'));
        $this->assertFalse($this->service->validateFormat("ABCDE\n"));
    }

    public function test_validate_format_rejects_multibyte_lookalikes(): void
    {
        // An attacker might try to slip in Cyrillic А (U+0410) instead of
        // Latin A. The format regex anchors to ASCII so these fail.
        $this->assertFalse($this->service->validateFormat('АBCDEF')); // Cyrillic А
    }

    // -- alphabet self-check -------------------------------------------------

    public function test_alphabet_constant_excludes_visually_confusable_characters(): void
    {
        // Defensive structural check on the alphabet itself: even if a
        // future refactor edits the constant, the disallowed glyphs from
        // Requirement 7.1 must remain absent.
        $this->assertSame(32, strlen(RoomService::ALPHABET));
        $this->assertStringNotContainsString('O', RoomService::ALPHABET);
        $this->assertStringNotContainsString('I', RoomService::ALPHABET);
        $this->assertStringNotContainsString('0', RoomService::ALPHABET);
        $this->assertStringNotContainsString('1', RoomService::ALPHABET);
    }
}
