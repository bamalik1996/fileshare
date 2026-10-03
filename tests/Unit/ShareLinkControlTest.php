<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Share;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Tests for the Share link-control helpers (SaaS features, Phase 2):
 * revocation, download ceilings / burn-after-read, and the combined
 * access gate. These read model attributes only, so no database is needed.
 */
class ShareLinkControlTest extends TestCase
{
    public function test_fresh_share_is_not_revoked_and_has_no_limit(): void
    {
        $share = new Share();

        $this->assertFalse($share->isRevoked());
        $this->assertFalse($share->downloadLimitReached());
    }

    public function test_revoked_at_marks_share_revoked(): void
    {
        $share = new Share();
        $share->revoked_at = Carbon::now();

        $this->assertTrue($share->isRevoked());
        $this->assertTrue($share->isAccessBlocked());
    }

    public function test_null_max_downloads_is_unlimited(): void
    {
        $share = new Share(['max_downloads' => null, 'download_count' => 9999]);

        $this->assertFalse($share->downloadLimitReached());
    }

    public function test_limit_reached_when_count_meets_max(): void
    {
        $share = new Share(['max_downloads' => 3, 'download_count' => 3]);

        $this->assertTrue($share->downloadLimitReached());
        $this->assertTrue($share->isAccessBlocked());
    }

    public function test_limit_not_reached_below_max(): void
    {
        $share = new Share(['max_downloads' => 3, 'download_count' => 2]);

        $this->assertFalse($share->downloadLimitReached());
    }

    public function test_burn_after_read_blocks_second_download(): void
    {
        $share = new Share(['max_downloads' => 1, 'download_count' => 0]);
        $this->assertFalse($share->downloadLimitReached(), 'first download allowed');

        $share->download_count = 1;
        $this->assertTrue($share->downloadLimitReached(), 'second download blocked');
    }

    public function test_expired_share_is_access_blocked(): void
    {
        $share = new Share();
        $share->expires_at = Carbon::now()->subMinute();

        $this->assertTrue($share->isExpired());
        $this->assertTrue($share->isAccessBlocked());
    }
}
