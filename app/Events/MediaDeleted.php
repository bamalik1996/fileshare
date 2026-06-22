<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Share;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MediaDeleted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Share $share,
        public string $mediaUuid,
    ) {
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('share.' . $this->share->id)];
    }

    public function broadcastAs(): string
    {
        return 'media.deleted';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return ['uuid' => $this->mediaUuid];
    }
}
