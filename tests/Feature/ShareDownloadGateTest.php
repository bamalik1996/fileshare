<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MediaScan;
use App\Models\Share;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Feature tests for the owner link controls on the public download path
 * (SaaS features, Phase 2):
 *
 *  - a revoked Share returns HTTP 410 for /download/{uuid};
 *  - a Share whose download ceiling is spent returns HTTP 410
 *    (this is what enforces burn-after-read on the next request);
 *  - a normal Share is NOT blocked by the link-control gate;
 *  - a revoked public Share returns 404 at /p/{slug}.
 *
 * Mirrors the in-memory SQLite setup of MediaControllerTest. The gate runs
 * before the virus-scan gate and before any disk access, so no file on disk
 * is required to assert the 410 responses.
 */
class ShareDownloadGateTest extends TestCase
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

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_revoked_share_download_returns_410(): void
    {
        $share = $this->makeShare(['revoked_at' => Carbon::now()]);
        $uuid = $this->seedShareMedia($share);
        $this->seedMediaScan($uuid, MediaScan::STATUS_CLEAN);

        $this->get('/download/' . $uuid)->assertStatus(410);
    }

    public function test_download_limit_reached_returns_410(): void
    {
        $share = $this->makeShare(['max_downloads' => 1, 'download_count' => 1]);
        $uuid = $this->seedShareMedia($share);
        $this->seedMediaScan($uuid, MediaScan::STATUS_CLEAN);

        $this->get('/download/' . $uuid)->assertStatus(410);
    }

    public function test_normal_share_is_not_blocked_by_link_control(): void
    {
        // No revoke, no limit → the link-control gate must let the request
        // through. Without a scan row the controller then returns 425, which
        // proves the 410 gate did not fire.
        $share = $this->makeShare([]);
        $uuid = $this->seedShareMedia($share);

        $response = $this->get('/download/' . $uuid);

        $this->assertNotSame(410, $response->status(), 'normal link must not be gated with 410');
    }

    public function test_revoked_public_share_returns_404(): void
    {
        $share = $this->makeShare([
            'public_slug' => 'abcdef123456',
            'revoked_at'  => Carbon::now(),
        ]);
        $this->seedShareMedia($share);

        $this->get('/p/abcdef123456')->assertStatus(404);
    }

    // -- helpers ------------------------------------------------------------

    private function makeShare(array $attributes): Share
    {
        return Share::query()->create(array_merge([
            'owner_type' => Share::OWNER_TYPE_ACCOUNT,
            'owner_id'   => '999',
            'expires_at' => Carbon::now()->addDay(),
        ], $attributes));
    }

    private function createSchema(): void
    {
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

    private function seedShareMedia(Share $share): string
    {
        $uuid = (string) Str::uuid();

        DB::table('media')->insert([
            'model_type' => $share->getMorphClass(),
            'model_id' => $share->id,
            'uuid' => $uuid,
            'collection_name' => 'shared_files',
            'name' => 'sample.txt',
            'file_name' => 'sample.txt',
            'mime_type' => 'text/plain',
            'disk' => 'public',
            'conversions_disk' => null,
            'size' => 1024,
            'manipulations' => '{}',
            'custom_properties' => '{}',
            'generated_conversions' => '{}',
            'responsive_images' => '{}',
            'order_column' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $uuid;
    }

    private function seedMediaScan(string $mediaUuid, string $status): void
    {
        DB::table('media_scans')->insert([
            'media_uuid' => $mediaUuid,
            'status' => $status,
            'backend' => MediaScan::BACKEND_CLAMAV,
            'retry_count' => 0,
            'result_payload' => null,
            'queued_at' => now(),
            'scanned_at' => now(),
        ]);
    }
}
