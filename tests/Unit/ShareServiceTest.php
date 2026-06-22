<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Principal\AccountPrincipal;
use App\Domain\Principal\IpPrincipal;
use App\Domain\Principal\RoomPrincipal;
use App\Exceptions\ShareExpiredException;
use App\Models\Share;
use App\Services\ShareService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Tests for {@see \App\Services\ShareService} (task 5.1).
 *
 * Covers acceptance criteria:
 *   3.4  - read of expired share returns 404 (via ShareExpiredException).
 *   3.8  - read of expired share deletes it before responding.
 *  13.3  - per-IP active-files limit (default 50) enforced by canAddFile.
 *  13.4  - upload exceeding the limit is rejected; existing files unchanged.
 *  13.8  - logged-in Account uses per-Account limits (100 files, 1 GB).
 *  16.4  - Account principal owns shares created during its session.
 *
 * The suite uses an in-memory SQLite database with the same schema as
 * the production migration so the canAddFile() aggregate counts execute
 * against a real query plan (not a mock). Spatie's `media` table is
 * created inline because Share `implements HasMedia` and the canAddFile
 * implementation queries it directly via `Media::query()`.
 */
class ShareServiceTest extends TestCase
{
    private ShareService $service;

    protected function setUp(): void
    {
        // In-memory SQLite isolates the test from the dev DB so deletions
        // and inserts performed by ShareService and Spatie's media model
        // do not leak across tests.
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

        // Pin every config key the service consults so the test does not
        // depend on default values drifting in config/airtoshare.php.
        config()->set('airtoshare.active_files_limit_ip', 50);
        config()->set('airtoshare.active_files_limit_account', 100);
        config()->set('airtoshare.account_storage_limit_bytes', 1024 * 1024 * 1024);
        config()->set('airtoshare.account_max_expiry_option', '30d');

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

        $this->service = $this->app->make(ShareService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // -- createForPrincipal --------------------------------------------------

    public function test_create_for_ip_principal_persists_owner_and_default_expiry(): void
    {
        // Acceptance criterion 3.2 (default 24h) + 16.4 (owner pair set
        // from the principal).
        Carbon::setTestNow('2030-01-01 12:00:00');

        $share = $this->service->createForPrincipal(
            new IpPrincipal('203.0.113.7'),
            ['text_content' => 'hello'],
        );

        $this->assertSame('ip', $share->owner_type);
        $this->assertSame('203.0.113.7', $share->owner_id);
        $this->assertSame('2030-01-02 12:00:00', $share->expires_at->format('Y-m-d H:i:s'));
        $this->assertSame('hello', $share->text_content);
        $this->assertNull($share->password_hash);
        $this->assertFalse($share->is_e2ee);
        $this->assertNotEmpty($share->uuid);
    }

    public function test_create_for_account_principal_records_account_owner(): void
    {
        // Acceptance criterion 16.4: shares created during an Account
        // session are owned by the Account.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $share = $this->service->createForPrincipal(
            new AccountPrincipal(42),
            ['expiry' => '30d'],
        );

        $this->assertSame('account', $share->owner_type);
        $this->assertSame('42', $share->owner_id);
        // 16.9: the 30d option is unlocked for accounts.
        $this->assertSame('2030-01-31 12:00:00', $share->expires_at->format('Y-m-d H:i:s'));
    }

    public function test_create_hashes_password_and_never_stores_plaintext(): void
    {
        $share = $this->service->createForPrincipal(
            new IpPrincipal('203.0.113.7'),
            ['password' => 'sup3rSecret'],
        );

        $this->assertNotNull($share->password_hash);
        $this->assertNotSame('sup3rSecret', $share->password_hash);
        $this->assertTrue(Hash::check('sup3rSecret', $share->password_hash));
    }

    public function test_create_rejects_short_password_via_password_manager(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->createForPrincipal(
            new IpPrincipal('203.0.113.7'),
            ['password' => 'abc'], // 3 chars < min 6
        );
    }

    public function test_create_rejects_30d_expiry_for_ip_principal(): void
    {
        // Acceptance criterion 3.5 / 16.9: 30d is account-only.
        $this->expectException(\InvalidArgumentException::class);

        $this->service->createForPrincipal(
            new IpPrincipal('203.0.113.7'),
            ['expiry' => '30d'],
        );
    }

    public function test_create_persists_markdown_source_and_e2ee_flag(): void
    {
        $share = $this->service->createForPrincipal(
            new IpPrincipal('203.0.113.7'),
            [
                'markdown_source' => '# heading',
                'is_e2ee' => true,
            ],
        );

        $this->assertSame('# heading', $share->markdown_source);
        $this->assertTrue($share->is_e2ee);
    }

    // -- update -------------------------------------------------------------

    public function test_update_clears_password_when_passed_null(): void
    {
        // Acceptance criterion 2.8: removing the password is part of the
        // same save and the next request sees the share unguarded.
        $share = $this->makeShare(['password_hash' => Hash::make('original')]);

        $updated = $this->service->update($share, ['password' => null]);

        $this->assertNull($updated->fresh()->password_hash);
    }

    public function test_update_clears_password_when_passed_empty_string(): void
    {
        $share = $this->makeShare(['password_hash' => Hash::make('original')]);

        $updated = $this->service->update($share, ['password' => '']);

        $this->assertNull($updated->fresh()->password_hash);
    }

    public function test_update_replaces_password_when_passed_new_value(): void
    {
        $share = $this->makeShare(['password_hash' => Hash::make('original')]);

        $updated = $this->service->update($share, ['password' => 'new-pass-1']);

        $this->assertNotNull($updated->password_hash);
        $this->assertTrue(Hash::check('new-pass-1', $updated->password_hash));
        $this->assertFalse(Hash::check('original', $updated->password_hash));
    }

    public function test_update_leaves_password_untouched_when_key_absent(): void
    {
        $share = $this->makeShare(['password_hash' => Hash::make('original')]);
        $beforeHash = $share->password_hash;

        $updated = $this->service->update($share, ['text_content' => 'edited']);

        $this->assertSame($beforeHash, $updated->fresh()->password_hash);
        $this->assertSame('edited', $updated->text_content);
    }

    public function test_update_recomputes_expiry_against_share_owner_principal(): void
    {
        // Acceptance criterion 16.9 (account allowed 30d) + 3.3 (UTC).
        Carbon::setTestNow('2030-01-01 12:00:00');

        $share = $this->makeShare([
            'owner_type' => 'account',
            'owner_id' => '42',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $updated = $this->service->update($share, ['expiry' => '30d']);

        $this->assertSame('2030-01-31 12:00:00', $updated->expires_at->format('Y-m-d H:i:s'));
    }

    public function test_update_rejects_30d_for_ip_owned_share(): void
    {
        $share = $this->makeShare([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.7',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->update($share, ['expiry' => '30d']);
    }

    public function test_update_writes_markdown_source(): void
    {
        $share = $this->makeShare();

        $updated = $this->service->update($share, ['markdown_source' => '## sub']);

        $this->assertSame('## sub', $updated->fresh()->markdown_source);
    }

    public function test_update_toggles_is_favourite(): void
    {
        $share = $this->makeShare();

        $updated = $this->service->update($share, ['is_favourite' => true]);

        $this->assertTrue($updated->fresh()->is_favourite);
    }

    // -- loadShare ----------------------------------------------------------

    public function test_load_share_finds_by_uuid(): void
    {
        $share = $this->makeShare();

        $loaded = $this->service->loadShare($share->uuid);

        $this->assertSame($share->id, $loaded->id);
    }

    public function test_load_share_finds_by_public_slug(): void
    {
        $share = $this->makeShare(['public_slug' => 'abcDEF123_-']);

        $loaded = $this->service->loadShare('abcDEF123_-');

        $this->assertSame($share->id, $loaded->id);
    }

    public function test_load_share_throws_when_share_missing(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->loadShare('00000000-0000-0000-0000-000000000000');
    }

    public function test_load_share_deletes_expired_share_and_throws(): void
    {
        // Acceptance criteria 3.4 + 3.8: read of an expired share deletes
        // it before responding and surfaces 404.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $share = $this->makeShare([
            'expires_at' => Carbon::now()->subSecond(),
        ]);

        $thrown = null;
        try {
            $this->service->loadShare($share->uuid);
        } catch (ShareExpiredException $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(ShareExpiredException::class, $thrown);
        $this->assertSame(404, $thrown->getStatusCode());
        $this->assertNull(Share::find($share->id));
    }

    // -- canAddFile ----------------------------------------------------------

    public function test_can_add_file_returns_true_when_no_existing_files(): void
    {
        $share = $this->makeShare(['owner_type' => 'ip', 'owner_id' => '203.0.113.1']);

        $this->assertTrue($this->service->canAddFile($share, 1024));
    }

    public function test_can_add_file_returns_false_when_size_is_negative(): void
    {
        $share = $this->makeShare(['owner_type' => 'ip', 'owner_id' => '203.0.113.1']);

        $this->assertFalse($this->service->canAddFile($share, -1));
    }

    public function test_can_add_file_enforces_per_ip_limit_at_boundary(): void
    {
        // Acceptance criterion 13.3: 50-file cap for IP owners. Insert
        // exactly 50 active files across two shares for the same IP and
        // confirm that the 51st is rejected and the 50th is allowed.
        Carbon::setTestNow('2030-01-01 12:00:00');

        $shareA = $this->makeShare(['owner_type' => 'ip', 'owner_id' => '203.0.113.1']);
        $shareB = $this->makeShare(['owner_type' => 'ip', 'owner_id' => '203.0.113.1']);

        $this->seedMedia($shareA, 30);
        $this->seedMedia($shareB, 19);

        // 49 existing + 1 new = 50, allowed.
        $this->assertTrue($this->service->canAddFile($shareA, 100));

        $this->seedMedia($shareB, 1); // bring total to 50.

        // 50 existing + 1 new = 51, rejected without touching existing.
        $this->assertFalse($this->service->canAddFile($shareA, 100));

        // Acceptance criterion 13.4: the existing files must remain.
        $this->assertSame(50, \Spatie\MediaLibrary\MediaCollections\Models\Media::count());
    }

    public function test_can_add_file_only_counts_files_for_same_owner(): void
    {
        // A different IP filling its quota must not influence ours.
        $mine = $this->makeShare(['owner_type' => 'ip', 'owner_id' => '203.0.113.1']);
        $theirs = $this->makeShare(['owner_type' => 'ip', 'owner_id' => '203.0.113.2']);

        $this->seedMedia($theirs, 50);

        $this->assertTrue($this->service->canAddFile($mine, 1024));
    }

    public function test_can_add_file_does_not_count_files_on_expired_shares(): void
    {
        Carbon::setTestNow('2030-01-01 12:00:00');

        $expired = $this->makeShare([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.1',
            'expires_at' => Carbon::now()->subDay(),
        ]);
        $live = $this->makeShare([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.1',
            'expires_at' => Carbon::now()->addDay(),
        ]);

        $this->seedMedia($expired, 50);

        // The 50 files on the expired share should not block a new upload.
        $this->assertTrue($this->service->canAddFile($live, 1024));
    }

    public function test_can_add_file_uses_account_caps_for_account_owner(): void
    {
        // Acceptance criterion 13.8 + 16.9: accounts use 100-file cap.
        $share = $this->makeShare(['owner_type' => 'account', 'owner_id' => '42']);

        $this->seedMedia($share, 99);
        $this->assertTrue($this->service->canAddFile($share, 1024));

        $this->seedMedia($share, 1); // now 100.
        $this->assertFalse($this->service->canAddFile($share, 1024));
    }

    public function test_can_add_file_enforces_account_storage_cap(): void
    {
        // Acceptance criterion 16.10: 1 GB total storage cap.
        $share = $this->makeShare(['owner_type' => 'account', 'owner_id' => '42']);

        $oneGb = 1024 * 1024 * 1024;
        // Existing usage = 1 GB - 100 bytes.
        $this->seedMedia($share, 1, $oneGb - 100);

        // A 200-byte upload would push total over 1 GB → rejected.
        $this->assertFalse($this->service->canAddFile($share, 200));

        // A 50-byte upload still fits → allowed.
        $this->assertTrue($this->service->canAddFile($share, 50));
    }

    public function test_can_add_file_distinguishes_account_from_ip_with_same_id(): void
    {
        // owner_id is a string column shared across owner kinds; canAddFile
        // must filter by owner_type as well.
        $accountShare = $this->makeShare(['owner_type' => 'account', 'owner_id' => '7']);
        $ipShare = $this->makeShare(['owner_type' => 'ip', 'owner_id' => '7']);

        $this->seedMedia($accountShare, 50);

        // IP owner with same identifier sees only its own (zero) files.
        $this->assertTrue($this->service->canAddFile($ipShare, 1024));
    }

    // -- helpers ------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeShare(array $attributes = []): Share
    {
        return Share::create(array_merge([
            'owner_type' => 'ip',
            'owner_id' => '203.0.113.1',
            'expires_at' => Carbon::now()->addDay(),
        ], $attributes));
    }

    /**
     * Insert `$count` Spatie Media rows attached to the given Share.
     * Side-steps the file-system bits of Spatie's add-media flow because
     * canAddFile only consults `Media::count()` and `Media::sum('size')`.
     */
    private function seedMedia(Share $share, int $count, int $bytesEach = 1024): void
    {
        for ($i = 0; $i < $count; $i++) {
            \Illuminate\Support\Facades\DB::table('media')->insert([
                'model_type' => $share->getMorphClass(),
                'model_id' => $share->id,
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'collection_name' => 'default',
                'name' => 'file-'.$i,
                'file_name' => 'file-'.$i.'.bin',
                'mime_type' => 'application/octet-stream',
                'disk' => 'public',
                'conversions_disk' => null,
                'size' => $bytesEach,
                'manipulations' => '{}',
                'custom_properties' => '{}',
                'generated_conversions' => '{}',
                'responsive_images' => '{}',
                'order_column' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
