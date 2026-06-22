<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Principal\AccountPrincipal;
use App\Domain\Principal\IpPrincipal;
use App\Domain\Principal\RoomPrincipal;
use App\Exceptions\ShareExpiredException;
use App\Models\Share;
use App\Services\ExpiryManager;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\In;
use Tests\TestCase;

/**
 * Tests for {@see \App\Services\ExpiryManager} (task 3.1).
 *
 * Covers acceptance criteria:
 *   3.1 - allowed expiry option set parses to absolute UTC timestamps.
 *   3.2 - default option is 24 hours when none is supplied.
 *   3.3 - timestamps stored in UTC at second precision.
 *   3.4 - read-time enforcement deletes share + raises 404.
 *   3.5 - invalid options are rejected (Form Request rule helper + parseOption guard).
 *   3.8 - read-time deletion runs before the exception escapes.
 *  16.9 - "30d" is allowed only for Account principals.
 *   7.5 - Room principals use the same set as IP principals.
 */
class ExpiryManagerTest extends TestCase
{
    private ExpiryManager $manager;

    protected function setUp(): void
    {
        // In-memory SQLite isolates the test from the dev DB and lets us
        // exercise enforceOnRead's deletion side-effect without touching
        // the real database.
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

        // Spatie's HasMedia trait queries a `media` table on clearMediaCollection.
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->uuid()->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable();
            $table->timestamps();
        });

        $this->manager = $this->app->make(ExpiryManager::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // -- parseOption ---------------------------------------------------------

    public function test_parse_option_defaults_to_24_hours_when_null(): void
    {
        // Acceptance criterion 3.2.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $result = $this->manager->parseOption(null, new IpPrincipal('203.0.113.1'));

        $this->assertSame('2030-01-02 12:00:00', $result->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $result->timezoneName);
    }

    public function test_parse_option_handles_each_allowed_token_for_ip_principal(): void
    {
        // Acceptance criterion 3.1.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $cases = [
            '1h'  => '2030-01-01 13:00:00',
            '6h'  => '2030-01-01 18:00:00',
            '24h' => '2030-01-02 12:00:00',
            '7d'  => '2030-01-08 12:00:00',
        ];

        foreach ($cases as $option => $expected) {
            $result = $this->manager->parseOption($option, new IpPrincipal('203.0.113.1'));
            $this->assertSame(
                $expected,
                $result->format('Y-m-d H:i:s'),
                "Option {$option} should resolve to {$expected}"
            );
        }
    }

    public function test_parse_option_returns_utc_timestamps(): void
    {
        // Acceptance criterion 3.3 - timestamps must be UTC with at least
        // second precision. We pin the test clock in a non-UTC zone to make
        // sure parseOption renormalises to UTC and does not just inherit.
        Carbon::setTestNow(Carbon::create(2030, 1, 1, 12, 0, 0, 'America/New_York'));

        $result = $this->manager->parseOption('1h', new IpPrincipal('203.0.113.1'));

        $this->assertSame('UTC', $result->timezoneName);
        // 12:00 New York == 17:00 UTC, +1h => 18:00 UTC.
        $this->assertSame('2030-01-01 18:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_parse_option_allows_30d_for_account_principal(): void
    {
        // Acceptance criterion 16.9.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $result = $this->manager->parseOption('30d', new AccountPrincipal(42));

        $this->assertSame('2030-01-31 12:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_parse_option_rejects_30d_for_ip_principal(): void
    {
        // Acceptance criterion 16.9 - guests cannot use the 30d window.
        $this->expectException(\InvalidArgumentException::class);

        $this->manager->parseOption('30d', new IpPrincipal('203.0.113.1'));
    }

    public function test_parse_option_rejects_30d_for_room_principal(): void
    {
        // Acceptance criterion 7.5: rooms use the same option set as IP.
        $this->expectException(\InvalidArgumentException::class);

        $this->manager->parseOption('30d', new RoomPrincipal(7));
    }

    public function test_parse_option_rejects_unknown_token(): void
    {
        // Acceptance criterion 3.5 - service-layer guard mirrors the
        // Form Request rejection at the controller boundary.
        $this->expectException(\InvalidArgumentException::class);

        $this->manager->parseOption('1y', new IpPrincipal('203.0.113.1'));
    }

    public function test_parse_option_rejects_empty_string_token(): void
    {
        // An empty string is not the same as null and must not silently
        // resolve to the 24h default; it is plainly invalid input.
        $this->expectException(\InvalidArgumentException::class);

        $this->manager->parseOption('', new IpPrincipal('203.0.113.1'));
    }

    // -- enforceOnRead -------------------------------------------------------

    public function test_enforce_on_read_is_a_no_op_for_active_share(): void
    {
        Carbon::setTestNow('2030-01-01 12:00:00');

        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.1',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $this->manager->enforceOnRead($share);

        // Share row should still be present.
        $this->assertNotNull(Share::find($share->id));
    }

    public function test_enforce_on_read_deletes_expired_share_and_throws(): void
    {
        // Acceptance criteria 3.4 + 3.8.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.1',
            'expires_at' => Carbon::now()->subSecond(),
        ]);

        $thrown = null;
        try {
            $this->manager->enforceOnRead($share);
        } catch (ShareExpiredException $e) {
            $thrown = $e;
        }

        // The exception must escape so the controller can map it to 404.
        $this->assertInstanceOf(ShareExpiredException::class, $thrown);
        $this->assertSame(404, $thrown->getStatusCode());

        // And the row must no longer exist (deletion is the read-time
        // fallback to the scheduled cleanup).
        $this->assertNull(Share::find($share->id));
    }

    public function test_enforce_on_read_treats_share_at_exact_expiry_instant_as_expired(): void
    {
        // Acceptance criterion 3.4: "<= now()" is expired, including the
        // boundary instant.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.1',
            'expires_at' => '2030-01-01 12:00:00',
        ]);

        $this->expectException(ShareExpiredException::class);

        $this->manager->enforceOnRead($share);
    }

    // -- optionRule ----------------------------------------------------------

    public function test_option_rule_returns_in_rule_for_ip_principal_without_30d(): void
    {
        $rule = $this->manager->optionRule(new IpPrincipal('203.0.113.1'));

        $this->assertInstanceOf(In::class, $rule);
        $rendered = (string) $rule;
        // Rule string format is: in:1h,6h,24h,7d
        $this->assertStringContainsString('1h', $rendered);
        $this->assertStringContainsString('6h', $rendered);
        $this->assertStringContainsString('24h', $rendered);
        $this->assertStringContainsString('7d', $rendered);
        $this->assertStringNotContainsString('30d', $rendered);
    }

    public function test_option_rule_includes_30d_for_account_principal(): void
    {
        $rule = $this->manager->optionRule(new AccountPrincipal(42));

        $this->assertInstanceOf(In::class, $rule);
        $this->assertStringContainsString('30d', (string) $rule);
    }

    public function test_option_rule_does_not_include_30d_for_room_principal(): void
    {
        $rule = $this->manager->optionRule(new RoomPrincipal(7));

        $this->assertStringNotContainsString('30d', (string) $rule);
    }

    public function test_allowed_options_for_ip_principal_returns_common_set(): void
    {
        $this->assertSame(
            ['1h', '6h', '24h', '7d'],
            $this->manager->allowedOptionsFor(new IpPrincipal('203.0.113.1')),
        );
    }

    public function test_allowed_options_for_account_principal_includes_30d(): void
    {
        $this->assertSame(
            ['1h', '6h', '24h', '7d', '30d'],
            $this->manager->allowedOptionsFor(new AccountPrincipal(42)),
        );
    }
}
