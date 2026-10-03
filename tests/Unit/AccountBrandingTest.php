<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Account;
use Tests\TestCase;

/**
 * Tests for Account branding helpers (SaaS features, Phase 4). These read
 * model attributes only, so no database is required.
 */
class AccountBrandingTest extends TestCase
{
    public function test_no_branding_returns_null_payload(): void
    {
        $account = new Account();

        $this->assertFalse($account->hasBranding());
        $this->assertNull($account->brandingPayload());
    }

    public function test_message_only_branding_is_detected(): void
    {
        $account = new Account(['brand_message' => 'Thanks for working with us!']);

        $this->assertTrue($account->hasBranding());

        $payload = $account->brandingPayload();
        $this->assertNotNull($payload);
        $this->assertSame('Thanks for working with us!', $payload['message']);
        $this->assertNull($payload['logo_url']);
        $this->assertNull($payload['color']);
    }

    public function test_color_only_branding_is_detected(): void
    {
        $account = new Account(['brand_color' => '#ff0066']);

        $this->assertTrue($account->hasBranding());
        $this->assertSame('#ff0066', $account->brandingPayload()['color']);
    }
}
