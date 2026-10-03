<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Share;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Feature tests for the public collect / file-request routes (SaaS
 * features, Phase 3):
 *
 *  - GET  /collect/{slug} renders the upload page for an open inbox and
 *    404s for unknown / revoked inboxes;
 *  - POST /collect/{slug} 404s for an unknown slug and 422s when no file
 *    is supplied.
 *
 * The successful upload path (storage + virus scan + broadcast) reuses the
 * same pipeline already exercised by the owner upload flow and is not
 * re-tested here to avoid depending on external storage/scanner side effects.
 */
class CollectUploadTest extends TestCase
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
        \DB::purge('sqlite');

        $this->createSharesSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeCollect(array $attributes = []): Share
    {
        return Share::query()->create(array_merge([
            'owner_type'   => Share::OWNER_TYPE_ACCOUNT,
            'owner_id'     => '42',
            'expires_at'   => Carbon::now()->addDays(30),
            'is_collect'   => true,
            'collect_slug' => 'inbox1234567',
            'collect_title' => 'Send me files',
        ], $attributes));
    }

    public function test_show_renders_open_inbox(): void
    {
        $this->makeCollect();

        $this->get('/collect/inbox1234567')
            ->assertStatus(200)
            ->assertSee('Send me files');
    }

    public function test_show_404_for_unknown_slug(): void
    {
        $this->get('/collect/nope00000000')->assertStatus(404);
    }

    public function test_show_404_for_revoked_inbox(): void
    {
        $this->makeCollect(['revoked_at' => Carbon::now()]);

        $this->get('/collect/inbox1234567')->assertStatus(404);
    }

    public function test_upload_404_for_unknown_slug(): void
    {
        $this->postJson('/collect/nope00000000', [])->assertStatus(404);
    }

    public function test_upload_422_when_no_file(): void
    {
        $this->makeCollect();

        $this->postJson('/collect/inbox1234567', [])->assertStatus(422);
    }

    private function createSharesSchema(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('brand_logo_path')->nullable();
            $table->string('brand_color', 7)->nullable();
            $table->string('brand_message', 160)->nullable();
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
    }
}
