<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Principal\AccountPrincipal;
use App\Domain\Principal\IpPrincipal;
use App\Services\PlanService;
use Tests\TestCase;

/**
 * Tests for {@see \App\Services\PlanService} (SaaS features, Phase 6).
 *
 * Confirms the single free plan resolves the configured ceilings per tier
 * and that all current features are enabled. This is the seam a future
 * paid plan would override.
 */
class PlanServiceTest extends TestCase
{
    private PlanService $plans;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('airtoshare.active_files_limit_ip', 100);
        config()->set('airtoshare.active_files_limit_account', 500);
        config()->set('airtoshare.account_storage_limit_bytes', 10 * 1024 * 1024 * 1024);
        config()->set('airtoshare.legacy_upload_max_bytes', 25 * 1024 * 1024);
        config()->set('airtoshare.chunked_upload_max_bytes', 500 * 1024 * 1024);

        $this->plans = new PlanService();
    }

    public function test_plan_is_free(): void
    {
        $this->assertSame('free', $this->plans->planFor(null));
    }

    public function test_all_known_features_enabled_and_unknown_disabled(): void
    {
        $this->assertTrue($this->plans->can('analytics'));
        $this->assertTrue($this->plans->can('file_request'));
        $this->assertTrue($this->plans->can('branding'));
        $this->assertFalse($this->plans->can('nonexistent_feature'));
    }

    public function test_account_tier_detection(): void
    {
        $this->assertTrue($this->plans->isAccountTier(new AccountPrincipal(42)));
        $this->assertFalse($this->plans->isAccountTier(new IpPrincipal('203.0.113.7')));
    }

    public function test_active_files_limit_by_tier(): void
    {
        $this->assertSame(500, $this->plans->activeFilesLimit(new AccountPrincipal(42)));
        $this->assertSame(100, $this->plans->activeFilesLimit(new IpPrincipal('203.0.113.7')));
    }

    public function test_storage_limit_only_capped_for_accounts(): void
    {
        $this->assertSame(10 * 1024 * 1024 * 1024, $this->plans->storageLimitBytes(new AccountPrincipal(42)));
        $this->assertNull($this->plans->storageLimitBytes(new IpPrincipal('203.0.113.7')));
    }

    public function test_limits_payload_shape(): void
    {
        $payload = $this->plans->limitsPayload(new AccountPrincipal(42));

        $this->assertSame('free', $payload['plan']);
        $this->assertSame(500, $payload['active_files_limit']);
        $this->assertSame(10 * 1024 * 1024 * 1024, $payload['account_storage_limit_bytes']);
        $this->assertSame(25 * 1024 * 1024, $payload['legacy_upload_max_bytes']);
        $this->assertSame(500 * 1024 * 1024, $payload['chunked_upload_max_bytes']);
    }
}
