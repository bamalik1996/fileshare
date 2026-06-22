<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Share;
use App\Support\IpAddressMatcher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class BroadcastingAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_channel_auth_allows_open_share_for_guest(): void
    {
        $share = Share::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'owner_type' => 'ip',
            'owner_id' => '127.0.0.1',
            'expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->post('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-share.' . $share->id,
        ]);

        $response->assertOk();
    }

    public function test_ip_channel_auth_allows_matching_loopback_ip(): void
    {
        $token = IpAddressMatcher::toChannelToken('::1');

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-ip.' . $token,
            ]);

        $response->assertOk();
    }

    public function test_share_channel_auth_denies_missing_share(): void
    {
        $response = $this->post('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-share.99999',
        ]);

        $response->assertForbidden();
    }
}
