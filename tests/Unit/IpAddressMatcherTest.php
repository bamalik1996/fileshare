<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\IpAddressMatcher;
use PHPUnit\Framework\TestCase;

class IpAddressMatcherTest extends TestCase
{
    public function test_loopback_addresses_are_equivalent(): void
    {
        $this->assertTrue(IpAddressMatcher::sameHost('127.0.0.1', '::1'));
        $this->assertTrue(IpAddressMatcher::sameHost('::1', '0:0:0:0:0:0:0:1'));
    }

    public function test_unrelated_addresses_do_not_match(): void
    {
        $this->assertFalse(IpAddressMatcher::sameHost('192.168.1.10', '127.0.0.1'));
    }
}
