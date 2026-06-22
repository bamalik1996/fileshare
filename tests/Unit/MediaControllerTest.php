<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\MediaScan;
use App\Models\Share;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests for {@see \App\Http\Controllers\MediaController} (task 5.3).
 *
 * Covers acceptance criteria:
 *   13.3 - per-owner active-file limit honoured by the legacy upload
 *          endpoint via {@see ShareService::canAddFile()}.
 *   13.4 - upload exceeding the limit is rejected without modifying any
 *          existing files.
 *   20.2 - missing scan row OR `pending` scan returns HTTP 425 on download.
 *   20.3 - `clean` scan allows the file to be served.
 *   20.4 - `infected` scan returns HTTP 451.
 *   20.9 - `error` scan returns HTTP 503.
 *  (20.11/15.7 implicit) - `skipped_e2ee` is served identically to clean
 *          here; the unscanned-media notice is rendered by the share view,
 *          not this endpoint.
 *
 * The suite uses an in-memory SQLite database and creates the minimum
 * subset of the schema each test needs (shares, media, media_scans,
 * media_files). Spatie's media row is inserted via raw SQL because we
 * never need the on-disk path generator to actually resolve a file —
 * the gate is asserted before file content is read.
 */
class MediaControllerTest extends TestCase
{
    protected function setUp(): void
    {
        // In-memory SQLite isolates the test from the dev DB so seeded
        // shares/media/scans rows never leak. Mirrors the pattern used
        // by the existing ShareServiceTest / ShareCleanupExpiredTest.
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

        // Pin the limits the canAddFile gate consults so the tests do
        // not depend on default values drifting in config/airtoshare.php.
        config()->set('airtoshare.active_files_limit_ip', 50);
        config()->set('airtoshare.active_files_limit_account', 100);
        config()->set('airtoshare.account_storage_limit_bytes', 1024 * 1024 * 1024);

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // -- download: scan-status mapping (Requirement 20) ---------------------

    public function test_download_returns_425_when_no_scan_row_exists(): void
    {
        // Acceptance criterion 20.2: a media that has not yet been
        // scanned (or whose row was lost) must surface 425 Too Early.
        $uuid = $this->seedSpatieMedia();

        $response = $this->get('/download/' . $uuid);

        $response->assertStatus(425);
    }

    public function test_download_returns_425_when_scan_pending(): void
    {
        // Acceptance criterion 20.2: an explicit `pending` row also
        // returns 425 - the gate treats absence and `pending` the
        // same way.
        $uuid = $this->seedSpatieMedia();
        $this->seedMediaScan($uuid, MediaScan::STATUS_PENDING);

        $response = $this->get('/download/' . $uuid);

        $response->assertStatus(425);
    }

    public function test_download_returns_451_when_scan_infected(): void
    {
        // Acceptance criterion 20.4: an infected file is blocked with
        // 451 Unavailable For Legal Reasons.
        $uuid = $this->seedSpatieMedia();
        $this->seedMediaScan($uuid, MediaScan::STATUS_INFECTED);

        $response = $this->get('/download/' . $uuid);

        $response->assertStatus(451);
    }

    public function test_download_returns_503_when_scan_error(): void
    {
        // Acceptance criterion 20.9: an unresolved scan is treated as
        // service-unavailable until manual review.
        $uuid = $this->seedSpatieMedia();
        $this->seedMediaScan($uuid, MediaScan::STATUS_ERROR);

        $response = $this->get('/download/' . $uuid);

        $response->assertStatus(503);
    }

    public function test_download_passes_gate_when_scan_clean(): void
    {
        // Acceptance criterion 20.3: a clean scan allows the download.
        // We do not seed a file on disk, so the controller continues
        // past the gate and lands on its existing "missing-file 404"
        // branch. The gate-relevant assertion is that the response is
        // NOT 425/451/503.
        $uuid = $this->seedSpatieMedia();
        $this->seedMediaScan($uuid, MediaScan::STATUS_CLEAN);

        $response = $this->get('/download/' . $uuid);

        $this->assertNotContains($response->status(), [425, 451, 503], 'clean scan should not be gated');
    }

    public function test_download_passes_gate_when_scan_skipped_e2ee(): void
    {
        // Design.md > Component 20: skipped_e2ee is served identically
        // to clean by this endpoint - the unscanned-media notice is
        // rendered by the surrounding share view.
        $uuid = $this->seedSpatieMedia();
        $this->seedMediaScan($uuid, MediaScan::STATUS_SKIPPED_E2EE);

        $response = $this->get('/download/' . $uuid);

        $this->assertNotContains($response->status(), [425, 451, 503], 'skipped_e2ee should not be gated');
    }

    public function test_download_returns_404_when_media_uuid_missing(): void
    {
        // Pre-existing behaviour preserved: an unknown UUID still 404s
        // without consulting the scan gate.
        $response = $this->get('/download/' . (string) Str::uuid());

        $response->assertStatus(404);
    }

    // -- store: ShareService::canAddFile gate (Requirements 13.3, 13.4) -----

    public function test_store_rejects_upload_when_active_files_cap_reached(): void
    {
        // Acceptance criteria 13.3 + 13.4: an upload that would exceed
        // the per-owner active-files cap is rejected with 422 and does
        // not modify any existing files. We cap the limit at 1 so the
        // test is fast and order-independent: the gate trips on the
        // first additional file regardless of which test IP Laravel
        // assigns.
        config()->set('airtoshare.active_files_limit_ip', 1);

        // The default test IP for HTTP calls is 127.0.0.1; seed a
        // single existing active file owned by that IP so canAddFile
        // returns false on the next upload attempt.
        $share = Share::create([
            'owner_type' => Share::OWNER_TYPE_IP,
            'owner_id'   => '127.0.0.1',
            'expires_at' => Carbon::now()->addDay(),
        ]);
        $this->seedShareMedia($share, 1);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->create('hello.txt', 1, 'text/plain'),
        ]);

        $response->assertStatus(422);
        $this->assertSame('Active files limit reached for this owner.', $response->json('message'));

        // Acceptance criterion 13.4: the existing file is untouched.
        $this->assertSame(1, DB::table('media')->count());
    }

    public function test_store_does_not_create_media_files_row_when_gate_trips(): void
    {
        // Defence-in-depth: the gate runs BEFORE the legacy
        // MediaFile::firstOrNew/save call, so a rejected upload must
        // not leave a stray media_files row behind either.
        config()->set('airtoshare.active_files_limit_ip', 1);

        $share = Share::create([
            'owner_type' => Share::OWNER_TYPE_IP,
            'owner_id'   => '127.0.0.1',
            'expires_at' => Carbon::now()->addDay(),
        ]);
        $this->seedShareMedia($share, 1);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->create('hello.txt', 1, 'text/plain'),
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, DB::table('media_files')->count());
    }

    // -- helpers ------------------------------------------------------------

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
            $table->boolean('is_e2ee')->default(false);
            $table->boolean('is_favourite')->default(false);
            $table->timestamps();

            $table->index(['owner_type', 'owner_id', 'expires_at']);
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

        // Legacy table the existing IP flow still writes to.
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->string('file_name')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('ip_address');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_accessed')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Insert a Spatie media row attached to a placeholder model so the
     * download endpoint can locate it by UUID. We do not create a file
     * on disk because the scan-status gate is asserted before content
     * is read.
     */
    private function seedSpatieMedia(): string
    {
        $uuid = (string) Str::uuid();

        DB::table('media')->insert([
            'model_type' => (new Share())->getMorphClass(),
            'model_id' => 0,
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
            'scanned_at' => $status === MediaScan::STATUS_PENDING ? null : now(),
        ]);
    }

    private function seedShareMedia(Share $share, int $count, int $bytesEach = 1024): void
    {
        for ($i = 0; $i < $count; $i++) {
            DB::table('media')->insert([
                'model_type' => $share->getMorphClass(),
                'model_id' => $share->id,
                'uuid' => (string) Str::uuid(),
                'collection_name' => 'shared_files',
                'name' => 'file-' . $i,
                'file_name' => 'file-' . $i . '.bin',
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
