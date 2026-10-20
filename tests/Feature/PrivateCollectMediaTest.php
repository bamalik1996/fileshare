<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\MediaScan;
use App\Models\Share;
use App\Support\MediaUrlGenerator;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * File-request uploads live on the non-public `private` disk:
 *
 *  - their URL points at /download/{uuid}, never at /storage;
 *  - only the share's owner can download them (strangers get 404);
 *  - the owner opening them doesn't count as a share download;
 *  - a share the owner published as a public gallery stays downloadable.
 */
class PrivateCollectMediaTest extends TestCase
{
    protected function setUp(): void
    {
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
        DB::purge('sqlite');

        Storage::fake(MediaUrlGenerator::PRIVATE_DISK);

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_private_media_url_points_at_download_route(): void
    {
        $share = $this->makeCollect($this->makeAccount());
        $uuid = $this->seedPrivateMedia($share);

        $media = Media::query()->where('uuid', $uuid)->firstOrFail();

        $this->assertSame(route('media.download.file', ['uuid' => $uuid]), $media->getUrl());
        $this->assertStringNotContainsString('/storage/', $media->getFullUrl());
    }

    public function test_stranger_gets_404_for_private_media(): void
    {
        $share = $this->makeCollect($this->makeAccount());
        $uuid = $this->seedPrivateMedia($share);
        $this->seedMediaScan($uuid);

        $this->get('/download/' . $uuid)->assertStatus(404);
    }

    public function test_other_account_gets_404_for_private_media(): void
    {
        $share = $this->makeCollect($this->makeAccount());
        $uuid = $this->seedPrivateMedia($share);
        $this->seedMediaScan($uuid);

        $other = $this->makeAccount('other@example.com');

        $this->actingAs($other, 'account')
            ->get('/download/' . $uuid)
            ->assertStatus(404);
    }

    public function test_owner_can_download_without_counting_a_download(): void
    {
        $owner = $this->makeAccount();
        $share = $this->makeCollect($owner);
        $uuid = $this->seedPrivateMedia($share);
        $this->seedMediaScan($uuid);

        $this->actingAs($owner, 'account')
            ->get('/download/' . $uuid)
            ->assertOk();

        $this->assertSame(0, (int) $share->fresh()->download_count);
    }

    public function test_published_collect_share_stays_downloadable(): void
    {
        $share = $this->makeCollect($this->makeAccount(), ['public_slug' => 'abcdef123456']);
        $uuid = $this->seedPrivateMedia($share);
        $this->seedMediaScan($uuid);

        $this->get('/download/' . $uuid)->assertOk();
    }

    // -- helpers ------------------------------------------------------------

    private function makeAccount(string $email = 'owner@example.com'): Account
    {
        return Account::query()->forceCreate([
            'email' => $email,
            'password_hash' => bcrypt('secret-password'),
            'email_verified_at' => now(),
        ]);
    }

    private function makeCollect(Account $owner, array $attributes = []): Share
    {
        return Share::query()->create(array_merge([
            'owner_type'    => Share::OWNER_TYPE_ACCOUNT,
            'owner_id'      => (string) $owner->getKey(),
            'expires_at'    => Carbon::now()->addDays(30),
            'is_collect'    => true,
            'collect_slug'  => Str::lower(Str::random(12)),
            'collect_title' => 'Send me files',
        ], $attributes));
    }

    private function seedPrivateMedia(Share $share): string
    {
        $uuid = (string) Str::uuid();

        $id = DB::table('media')->insertGetId([
            'model_type' => $share->getMorphClass(),
            'model_id' => $share->id,
            'uuid' => $uuid,
            'collection_name' => 'shared_files',
            'name' => 'receipt.txt',
            'file_name' => 'receipt.txt',
            'mime_type' => 'text/plain',
            'disk' => MediaUrlGenerator::PRIVATE_DISK,
            'conversions_disk' => MediaUrlGenerator::PRIVATE_DISK,
            'size' => 5,
            'manipulations' => '{}',
            'custom_properties' => '{}',
            'generated_conversions' => '{}',
            'responsive_images' => '{}',
            'order_column' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Storage::disk(MediaUrlGenerator::PRIVATE_DISK)->put("{$id}/{$uuid}/receipt.txt", 'hello');

        return $uuid;
    }

    private function seedMediaScan(string $mediaUuid): void
    {
        DB::table('media_scans')->insert([
            'media_uuid' => $mediaUuid,
            'status' => MediaScan::STATUS_CLEAN,
            'backend' => MediaScan::BACKEND_CLAMAV,
            'retry_count' => 0,
            'result_payload' => null,
            'queued_at' => now(),
            'scanned_at' => now(),
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('brand_logo_path')->nullable();
            $table->string('brand_color', 7)->nullable();
            $table->string('brand_message', 160)->nullable();
            $table->timestamp('deletion_scheduled_at')->nullable();
            $table->rememberToken();
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
            $table->unsignedInteger('max_downloads')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('revoked_at')->nullable();
            $table->boolean('is_collect')->default(false);
            $table->string('collect_slug', 16)->nullable()->unique();
            $table->string('collect_title', 120)->nullable();
            $table->string('collect_instructions', 500)->nullable();
            $table->boolean('is_e2ee')->default(false);
            $table->boolean('is_favourite')->default(false);
            $table->boolean('notify_browser')->default(false);
            $table->boolean('notify_email')->default(false);
            $table->string('notify_email_address')->nullable();
            $table->timestamps();
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

        Schema::create('media_scans', function (Blueprint $table) {
            $table->id();
            $table->char('media_uuid', 36)->unique();
            $table->string('status');
            $table->string('backend');
            $table->unsignedInteger('retry_count')->default(0);
            $table->json('result_payload')->nullable();
            $table->timestamp('queued_at');
            $table->timestamp('scanned_at')->nullable();
        });
    }
}
