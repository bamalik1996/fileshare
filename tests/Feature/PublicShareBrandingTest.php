<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Share;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Feature test for owner branding on the public share page (SaaS features,
 * Phase 4): an Account-owned public Share renders the owner's brand message.
 */
class PublicShareBrandingTest extends TestCase
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

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_public_share_shows_owner_brand_message(): void
    {
        $account = Account::query()->create([
            'email'         => 'owner@example.com',
            'password_hash' => 'x',
            'brand_message' => 'Delivered by Acme Studio',
            'brand_color'   => '#ff0066',
        ]);

        $share = Share::query()->create([
            'owner_type'  => Share::OWNER_TYPE_ACCOUNT,
            'owner_id'    => (string) $account->id,
            'expires_at'  => Carbon::now()->addDay(),
            'public_slug' => 'brandslug123',
            'text_content' => 'hello world',
        ]);

        $this->get('/p/brandslug123')
            ->assertStatus(200)
            ->assertSee('Delivered by Acme Studio');
    }

    public function test_public_share_without_branding_renders_normally(): void
    {
        $account = Account::query()->create([
            'email'         => 'plain@example.com',
            'password_hash' => 'x',
        ]);

        Share::query()->create([
            'owner_type'  => Share::OWNER_TYPE_ACCOUNT,
            'owner_id'    => (string) $account->id,
            'expires_at'  => Carbon::now()->addDay(),
            'public_slug' => 'plainslug123',
            'text_content' => 'hello world',
        ]);

        $this->get('/p/plainslug123')
            ->assertStatus(200)
            ->assertSee('Shared content');
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
    }
}
