<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Principal\AccountPrincipal;
use App\Domain\Principal\IpPrincipal;
use App\Domain\Principal\RoomPrincipal;
use App\Models\Share;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests for the Share Eloquent model (task 2.3).
 *
 * Uses a real Laravel application boot so model events, casts, the morph
 * map, and database connection all behave as they will in production.
 * The shares table is created inline with the same schema as the migration
 * shipped under database/migrations/2026_05_31_165455_create_shares_table.php.
 */
class ShareModelTest extends TestCase
{
    protected function setUp(): void
    {
        // Use an in-memory SQLite connection so unit-level tests are
        // isolated from the dev DB. The connection is configured via env
        // vars before parent::setUp() boots the framework.
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';

        parent::setUp();

        // Override the resolved sqlite connection in case the .env file
        // already pinned a non-default disk path.
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

            $table->index(['owner_type', 'owner_id', 'expires_at']);
            $table->index('expires_at');
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_expires_at_is_cast_to_datetime(): void
    {
        $share = new Share();
        $share->expires_at = '2030-01-15 12:00:00';

        $this->assertInstanceOf(Carbon::class, $share->expires_at);
        $this->assertSame('2030-01-15 12:00:00', $share->expires_at->format('Y-m-d H:i:s'));
    }

    public function test_is_e2ee_and_is_favourite_are_cast_to_bool(): void
    {
        $share = new Share();
        $share->is_e2ee = 1;
        $share->is_favourite = 0;

        $this->assertTrue($share->is_e2ee);
        $this->assertFalse($share->is_favourite);
        $this->assertIsBool($share->is_e2ee);
        $this->assertIsBool($share->is_favourite);
    }

    public function test_is_expired_returns_true_when_expires_at_is_in_the_past(): void
    {
        $share = new Share();
        $share->expires_at = Carbon::now()->subSecond();

        $this->assertTrue($share->isExpired());
    }

    public function test_is_expired_returns_true_when_expires_at_equals_now(): void
    {
        // Acceptance criterion 3.4: "<= now()" is expired.
        Carbon::setTestNow('2030-01-01 00:00:00');
        $share = new Share();
        $share->expires_at = '2030-01-01 00:00:00';

        $this->assertTrue($share->isExpired());
    }

    public function test_is_expired_returns_false_when_expires_at_is_in_the_future(): void
    {
        $share = new Share();
        $share->expires_at = Carbon::now()->addHour();

        $this->assertFalse($share->isExpired());
    }

    public function test_uuid_is_auto_assigned_on_create(): void
    {
        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.7',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $this->assertNotEmpty($share->uuid);
        $this->assertSame(36, strlen($share->uuid));
    }

    public function test_scope_active_excludes_expired_rows(): void
    {
        Carbon::setTestNow('2030-01-01 12:00:00');

        Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.1',
            'expires_at' => '2030-01-01 11:00:00', // expired
        ]);
        $live = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.2',
            'expires_at' => '2030-01-01 13:00:00', // not expired
        ]);

        $ids = Share::active()->pluck('id')->all();

        $this->assertSame([$live->id], $ids);
    }

    public function test_scope_owned_by_filters_by_principal_type_and_identifier(): void
    {
        Carbon::setTestNow('2030-01-01 12:00:00');

        $ipShare = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.10',
            'expires_at' => '2030-01-02 12:00:00',
        ]);
        $accountShare = Share::create([
            'owner_type' => 'account',
            'owner_id' => '42',
            'expires_at' => '2030-01-02 12:00:00',
        ]);
        $roomShare = Share::create([
            'owner_type' => 'room',
            'owner_id' => '7',
            'expires_at' => '2030-01-02 12:00:00',
        ]);

        $ipResults = Share::ownedBy(new IpPrincipal('203.0.113.10'))->pluck('id')->all();
        $this->assertSame([$ipShare->id], $ipResults);

        $accountResults = Share::ownedBy(new AccountPrincipal(42))->pluck('id')->all();
        $this->assertSame([$accountShare->id], $accountResults);

        $roomResults = Share::ownedBy(new RoomPrincipal(7))->pluck('id')->all();
        $this->assertSame([$roomShare->id], $roomResults);
    }

    public function test_scope_owned_by_distinguishes_between_owner_types_with_same_id(): void
    {
        Carbon::setTestNow('2030-01-01 12:00:00');

        // Same numeric identifier under two different owner types.
        Share::create([
            'owner_type' => 'account',
            'owner_id' => '5',
            'expires_at' => '2030-01-02 12:00:00',
        ]);
        $room = Share::create([
            'owner_type' => 'room',
            'owner_id' => '5',
            'expires_at' => '2030-01-02 12:00:00',
        ]);

        $roomResults = Share::ownedBy(new RoomPrincipal(5))->pluck('id')->all();

        $this->assertSame([$room->id], $roomResults);
    }

    public function test_owner_relation_is_a_morph_to(): void
    {
        $share = new Share();
        $relation = $share->owner();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $relation);
    }

    public function test_scope_active_can_be_chained_with_owned_by(): void
    {
        Carbon::setTestNow('2030-01-01 12:00:00');

        Share::create([
            'owner_type' => 'account',
            'owner_id' => '99',
            'expires_at' => '2030-01-01 11:00:00', // expired
        ]);
        $live = Share::create([
            'owner_type' => 'account',
            'owner_id' => '99',
            'expires_at' => '2030-01-01 13:00:00',
        ]);
        Share::create([
            'owner_type' => 'account',
            'owner_id' => '100',
            'expires_at' => '2030-01-01 13:00:00',
        ]);

        $ids = Share::active()->ownedBy(new AccountPrincipal(99))->pluck('id')->all();

        $this->assertSame([$live->id], $ids);
    }
}
