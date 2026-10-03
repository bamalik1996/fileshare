<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Share;
use App\Models\ShareEvent;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests for {@see \App\Services\AnalyticsService} (SaaS features, Phase 1).
 *
 * Covers:
 *  - view / download events are persisted against the owning Share;
 *  - the raw client IP is NEVER stored - only a salted, truncated hash;
 *  - user-agent strings are classified into device / browser / os;
 *  - per-Share and bulk aggregation return correct view/download counts.
 *
 * Uses an in-memory SQLite schema (same pattern as ShareServiceTest) so the
 * aggregation queries run against a real query plan.
 */
class AnalyticsServiceTest extends TestCase
{
    private AnalyticsService $service;

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

        Schema::create('share_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('share_id');
            $table->char('media_uuid', 36)->nullable();
            $table->string('event_type', 16);
            $table->string('ip_hash', 64)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('device', 32)->nullable();
            $table->string('browser', 48)->nullable();
            $table->string('os', 48)->nullable();
            $table->string('referrer', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['share_id', 'created_at']);
        });

        $this->service = new AnalyticsService();
    }

    private function makeShare(): Share
    {
        return Share::query()->create([
            'owner_type' => Share::OWNER_TYPE_ACCOUNT,
            'owner_id'   => '1',
            'expires_at' => Carbon::now()->addDay(),
        ]);
    }

    private function request(string $ip, string $userAgent, array $headers = []): Request
    {
        $server = ['REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => $userAgent];
        foreach ($headers as $k => $v) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $k))] = $v;
        }

        return Request::create('/p/example', 'GET', [], [], [], $server);
    }

    public function test_record_view_persists_event_without_raw_ip(): void
    {
        $share = $this->makeShare();
        $ip = '203.0.113.45';

        $this->service->recordView($share, $this->request(
            $ip,
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36'
        ));

        $event = ShareEvent::query()->firstOrFail();

        $this->assertSame($share->id, $event->share_id);
        $this->assertSame(ShareEvent::EVENT_VIEW, $event->event_type);
        $this->assertSame('Desktop', $event->device);
        $this->assertSame('Chrome', $event->browser);
        $this->assertSame('Windows', $event->os);

        // Privacy: the raw IP must never appear in the row.
        $this->assertNotNull($event->ip_hash);
        $this->assertNotSame($ip, $event->ip_hash);
        $this->assertStringNotContainsString($ip, (string) $event->ip_hash);
        $this->assertSame(32, strlen((string) $event->ip_hash));
    }

    public function test_same_ip_hashes_consistently_for_unique_visitor_counts(): void
    {
        $share = $this->makeShare();
        $ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1 Mobile/15E148 Safari/604.1';

        $this->service->recordView($share, $this->request('198.51.100.7', $ua));
        $this->service->recordView($share, $this->request('198.51.100.7', $ua));
        $this->service->recordView($share, $this->request('198.51.100.99', $ua));

        $summary = $this->service->summaryFor($share);

        $this->assertSame(3, $summary['views']);
        $this->assertSame(2, $summary['unique_visitors']);
    }

    public function test_mobile_user_agent_is_classified(): void
    {
        $share = $this->makeShare();

        $this->service->recordView($share, $this->request(
            '198.51.100.1',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1 Mobile/15E148 Safari/604.1'
        ));

        $event = ShareEvent::query()->firstOrFail();
        $this->assertSame('Mobile', $event->device);
        $this->assertSame('iOS', $event->os);
        $this->assertSame('Safari', $event->browser);
    }

    public function test_country_header_is_captured_when_present(): void
    {
        $share = $this->makeShare();

        $this->service->recordView($share, $this->request(
            '198.51.100.1',
            'Mozilla/5.0',
            ['CF-IPCountry' => 'in']
        ));

        $event = ShareEvent::query()->firstOrFail();
        $this->assertSame('IN', $event->country);
    }

    public function test_summary_for_share_ids_returns_view_and_download_counts(): void
    {
        $share = $this->makeShare();

        $this->service->record($share, ShareEvent::EVENT_VIEW, $this->request('198.51.100.1', 'UA'));
        $this->service->record($share, ShareEvent::EVENT_VIEW, $this->request('198.51.100.2', 'UA'));
        $this->service->record($share, ShareEvent::EVENT_DOWNLOAD, $this->request('198.51.100.3', 'UA'), 'media-uuid-1');

        $map = $this->service->summaryForShareIds([$share->id]);

        $this->assertSame(2, $map[$share->id]['views']);
        $this->assertSame(1, $map[$share->id]['downloads']);
    }

    public function test_detail_for_includes_breakdowns_and_recent(): void
    {
        $share = $this->makeShare();

        $this->service->record($share, ShareEvent::EVENT_DOWNLOAD, $this->request(
            '198.51.100.1',
            'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0',
            ['CF-IPCountry' => 'US']
        ), 'file-1');

        $detail = $this->service->detailFor($share);

        $this->assertSame(1, $detail['summary']['downloads']);
        $this->assertNotEmpty($detail['countries']);
        $this->assertSame('US', $detail['countries'][0]['label']);
        $this->assertNotEmpty($detail['recent']);
        $this->assertSame('download', $detail['recent'][0]['event_type']);
    }
}
