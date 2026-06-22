<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Room;
use App\Models\Share;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests for the `shares:cleanup-expired` artisan command (tasks 3.4 and
 * 12.3).
 *
 * Covers:
 *   - Requirements 3.6, 3.7: cleanup runs hourly, deletes shares more
 *     than 1 hour past expiry.
 *   - Requirement 16.7: favourited shares are exempt from auto-expiry.
 *   - Requirement 7.6: rooms whose `expires_at` is at or before now AND
 *     whose `last_activity_at` is at or before `now - 60s` (or null) are
 *     deleted along with any owned shares.
 */
class ShareCleanupExpiredTest extends TestCase
{
    protected function setUp(): void
    {
        // In-memory SQLite isolates the test from the dev DB and lets us
        // exercise the cleanup command's deletion side-effect without
        // touching the real database.
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

        // The command joins against `account_favourites` to exempt
        // pivot-favourited shares (Requirement 16.7).
        Schema::create('account_favourites', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('share_id');
            $table->timestamp('created_at')->nullable();
            $table->primary(['account_id', 'share_id']);
        });

        // Spatie's HasMedia trait queries a `media` table on
        // clearMediaCollection.
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

        // The room cleanup branch (task 12.3) operates on the rooms
        // table introduced in section 2.4. Mirrors the production
        // migration columns we exercise: `expires_at` and
        // `last_activity_at`.
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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_deletes_shares_expired_more_than_one_hour_ago(): void
    {
        // Acceptance criterion 3.7: deletes shares whose expiry timestamp
        // is more than 1 hour before the current time.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.1',
            // 2 hours past expiry, well outside the 1-hour grace window.
            'expires_at' => '2030-01-01 10:00:00',
        ]);

        Artisan::call('shares:cleanup-expired');

        $this->assertNull(Share::find($share->id));
    }

    public function test_keeps_share_inside_one_hour_grace_window(): void
    {
        // Acceptance criterion 3.7 boundary: a share whose expiry is
        // exactly 30 minutes ago is still inside the grace window and
        // must be left for the next hourly pass (the on-read fallback
        // in ExpiryManager already serves 404 for it).
        Carbon::setTestNow('2030-01-01 12:00:00');

        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.1',
            'expires_at' => '2030-01-01 11:30:00',
        ]);

        Artisan::call('shares:cleanup-expired');

        $this->assertNotNull(Share::find($share->id));
    }

    public function test_keeps_active_shares(): void
    {
        Carbon::setTestNow('2030-01-01 12:00:00');

        $share = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.1',
            'expires_at' => '2030-01-01 13:00:00',
        ]);

        Artisan::call('shares:cleanup-expired');

        $this->assertNotNull(Share::find($share->id));
    }

    public function test_skips_owner_favourited_shares(): void
    {
        // Acceptance criterion 16.7: favourited shares are exempt from
        // auto-expiry while the favourite mark remains.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $share = Share::create([
            'owner_type' => 'account',
            'owner_id' => '42',
            'expires_at' => '2030-01-01 09:00:00', // 3 hours past expiry
            'is_favourite' => true,
        ]);

        Artisan::call('shares:cleanup-expired');

        $this->assertNotNull(Share::find($share->id));
    }

    public function test_skips_pivot_favourited_shares(): void
    {
        // Acceptance criterion 16.7 via the account_favourites pivot:
        // a share favourited by any account must survive cleanup.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $share = Share::create([
            'owner_type' => 'account',
            'owner_id' => '99',
            'expires_at' => '2030-01-01 09:00:00',
        ]);

        \DB::table('account_favourites')->insert([
            'account_id' => 7,
            'share_id' => $share->id,
            'created_at' => Carbon::now(),
        ]);

        Artisan::call('shares:cleanup-expired');

        $this->assertNotNull(Share::find($share->id));
    }

    public function test_runs_with_zero_shares_without_error(): void
    {
        $exitCode = Artisan::call('shares:cleanup-expired');

        $this->assertSame(0, $exitCode);
    }

    public function test_deletes_only_eligible_rows_in_a_mixed_dataset(): void
    {
        Carbon::setTestNow('2030-01-01 12:00:00');

        // Eligible: outside grace window, not favourited.
        $expired = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.1',
            'expires_at' => '2030-01-01 09:00:00',
        ]);

        // Survives: still active.
        $active = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.2',
            'expires_at' => '2030-01-01 13:00:00',
        ]);

        // Survives: inside grace window.
        $grace = Share::create([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.3',
            'expires_at' => '2030-01-01 11:45:00',
        ]);

        // Survives: owner-favourited.
        $favourited = Share::create([
            'owner_type' => 'account',
            'owner_id' => '5',
            'expires_at' => '2030-01-01 09:00:00',
            'is_favourite' => true,
        ]);

        Artisan::call('shares:cleanup-expired');

        $this->assertNull(Share::find($expired->id));
        $this->assertNotNull(Share::find($active->id));
        $this->assertNotNull(Share::find($grace->id));
        $this->assertNotNull(Share::find($favourited->id));
    }

    // ------------------------------------------------------------------
    // Room cleanup branch (task 12.3, Requirement 7.6).
    //
    // A Room is deleted only when BOTH:
    //   - expires_at <= now()
    //   - last_activity_at IS NULL OR last_activity_at <= now() - 60s
    // ------------------------------------------------------------------

    public function test_deletes_expired_room_with_stale_activity(): void
    {
        // Both conditions met: expired AND last activity > 60s ago.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $roomId = \DB::table('rooms')->insertGetId([
            'code' => 'ABCDEF',
            'expires_at' => '2030-01-01 11:55:00',          // 5m past expiry
            'last_activity_at' => '2030-01-01 11:58:30',    // 90s ago
            'created_at' => '2030-01-01 10:00:00',
            'updated_at' => '2030-01-01 11:58:30',
        ]);

        Artisan::call('shares:cleanup-expired');

        $this->assertNull(\DB::table('rooms')->find($roomId));
    }

    public function test_deletes_expired_room_with_null_activity(): void
    {
        // NULL last_activity_at means no device ever registered presence,
        // so there is nothing keeping the expired room alive.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $roomId = \DB::table('rooms')->insertGetId([
            'code' => 'GHJKLM',
            'expires_at' => '2030-01-01 11:00:00',
            'last_activity_at' => null,
            'created_at' => '2030-01-01 10:00:00',
            'updated_at' => '2030-01-01 10:00:00',
        ]);

        Artisan::call('shares:cleanup-expired');

        $this->assertNull(\DB::table('rooms')->find($roomId));
    }

    public function test_deletes_room_at_exact_60s_inactivity_boundary(): void
    {
        // Boundary: last activity exactly 60 seconds ago counts as
        // inactive for >= 60 seconds (Requirement 7.6).
        Carbon::setTestNow('2030-01-01 12:00:00');

        $roomId = \DB::table('rooms')->insertGetId([
            'code' => 'NPQRST',
            'expires_at' => '2030-01-01 11:55:00',
            'last_activity_at' => '2030-01-01 11:59:00', // exactly 60s ago
            'created_at' => '2030-01-01 10:00:00',
            'updated_at' => '2030-01-01 11:59:00',
        ]);

        Artisan::call('shares:cleanup-expired');

        $this->assertNull(\DB::table('rooms')->find($roomId));
    }

    public function test_deletes_room_at_exact_expiry_boundary(): void
    {
        // Boundary: `expires_at` exactly at now counts as expired
        // (Requirement 7.6 uses "at or before").
        Carbon::setTestNow('2030-01-01 12:00:00');

        $roomId = \DB::table('rooms')->insertGetId([
            'code' => 'UVWXYZ',
            'expires_at' => '2030-01-01 12:00:00',          // exactly now
            'last_activity_at' => '2030-01-01 11:58:30',    // 90s ago
            'created_at' => '2030-01-01 10:00:00',
            'updated_at' => '2030-01-01 11:58:30',
        ]);

        Artisan::call('shares:cleanup-expired');

        $this->assertNull(\DB::table('rooms')->find($roomId));
    }

    public function test_keeps_active_room_with_stale_activity(): void
    {
        // expires_at in the future ⇒ both conditions are NOT met, so
        // the room must survive even if no device has been active.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $roomId = \DB::table('rooms')->insertGetId([
            'code' => 'AB2CD3',
            'expires_at' => '2030-01-01 13:00:00',       // 1h ahead
            'last_activity_at' => '2030-01-01 11:30:00', // long since
            'created_at' => '2030-01-01 10:00:00',
            'updated_at' => '2030-01-01 11:30:00',
        ]);

        Artisan::call('shares:cleanup-expired');

        $this->assertNotNull(\DB::table('rooms')->find($roomId));
    }

    public function test_keeps_expired_room_with_recent_activity(): void
    {
        // expires_at in the past but a device has been active within the
        // last 60s ⇒ at least one device is still considered present.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $roomId = \DB::table('rooms')->insertGetId([
            'code' => 'EF4GH5',
            'expires_at' => '2030-01-01 11:55:00',       // 5m past
            'last_activity_at' => '2030-01-01 11:59:30', // 30s ago
            'created_at' => '2030-01-01 10:00:00',
            'updated_at' => '2030-01-01 11:59:30',
        ]);

        Artisan::call('shares:cleanup-expired');

        $this->assertNotNull(\DB::table('rooms')->find($roomId));
    }

    public function test_room_deletion_cascades_owned_shares(): void
    {
        // When a Room is deleted, any Share with
        // (owner_type='room', owner_id=room.id) must be deleted too so
        // its media is cleared in the same operation.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $roomId = \DB::table('rooms')->insertGetId([
            'code' => 'JKLMNP',
            'expires_at' => '2030-01-01 11:00:00',
            'last_activity_at' => null,
            'created_at' => '2030-01-01 10:00:00',
            'updated_at' => '2030-01-01 10:00:00',
        ]);

        $share = Share::create([
            'owner_type' => 'room',
            'owner_id' => (string) $roomId,
            // Active expiry on its own would normally protect the share
            // from the share-cleanup branch; this asserts the room
            // branch deletes it regardless.
            'expires_at' => '2030-01-02 12:00:00',
        ]);

        Artisan::call('shares:cleanup-expired');

        $this->assertNull(\DB::table('rooms')->find($roomId));
        $this->assertNull(Share::find($share->id));
    }
}
