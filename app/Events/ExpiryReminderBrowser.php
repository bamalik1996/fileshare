<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Share;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Browser-channel expiry reminder for IP-only owners (Requirement 11.1).
 */
class ExpiryReminderBrowser implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Share $share,
        public string $ownerIp,
        public string $message,
    ) {
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('ip.' . $this->ownerIp)];
    }

    public function broadcastAs(): string
    {
        return 'share.expiry_reminder';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'share_uuid' => $this->share->uuid,
            'expires_at' => $this->share->expires_at?->toIso8601String(),
            'message'    => $this->message,
        ];
    }
}
