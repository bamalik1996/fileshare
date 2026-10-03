<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Share;
use App\Services\CollectService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests for {@see \App\Services\CollectService} (SaaS features, Phase 3).
 *
 * Covers:
 *  - createForAccount() produces an Account-owned, is_collect Share with a
 *    unique slug and the optional title/instructions;
 *  - findOpenBySlug() returns the Share only while the inbox is open, and
 *    null for revoked / expired / non-collect / unknown slugs.
 */
class CollectServiceTest extends TestCase
{
    private CollectService $service;

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

        $this->service = $this->app->make(CollectService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function account(int $id = 7): Account
    {
        $account = new Account();
        $account->id = $id;

        return $account;
    }

    public function test_create_for_account_makes_collect_share(): void
    {
        $share = $this->service->createForAccount($this->account(7), 'Send me files', 'PDFs only please');

        $this->assertTrue($share->is_collect);
        $this->assertSame(Share::OWNER_TYPE_ACCOUNT, $share->owner_type);
        $this->assertSame('7', (string) $share->owner_id);
        $this->assertNotEmpty($share->collect_slug);
        $this->assertSame('Send me files', $share->collect_title);
        $this->assertSame('PDFs only please', $share->collect_instructions);
        $this->assertTrue($share->acceptsCollectUploads());
    }

    public function test_create_allows_null_title_and_instructions(): void
    {
        $share = $this->service->createForAccount($this->account(), null, null);

        $this->assertNull($share->collect_title);
        $this->assertNull($share->collect_instructions);
        $this->assertNotEmpty($share->collect_slug);
    }

    public function test_find_open_by_slug_returns_open_inbox(): void
    {
        $share = $this->service->createForAccount($this->account(), 'X', null);

        $found = $this->service->findOpenBySlug($share->collect_slug);

        $this->assertNotNull($found);
        $this->assertSame($share->id, $found->id);
    }

    public function test_find_open_by_slug_returns_null_for_unknown(): void
    {
        $this->assertNull($this->service->findOpenBySlug('does-not-ex'));
    }

    public function test_find_open_by_slug_returns_null_when_revoked(): void
    {
        $share = $this->service->createForAccount($this->account(), 'X', null);
        $share->revoked_at = Carbon::now();
        $share->save();

        $this->assertNull($this->service->findOpenBySlug($share->collect_slug));
    }

    public function test_find_open_by_slug_returns_null_when_expired(): void
    {
        $share = $this->service->createForAccount($this->account(), 'X', null);
        $share->expires_at = Carbon::now()->subMinute();
        $share->save();

        $this->assertNull($this->service->findOpenBySlug($share->collect_slug));
    }

    private function createSharesSchema(): void
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
    }
}
