<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClipboardUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $roomId,
        public string $text,
        public string $updatedAt,
    ) {
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('room.' . $this->roomId . '.clipboard')];
    }

    public function broadcastAs(): string
    {
        return 'clipboard.updated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'text'       => $this->text,
            'updated_at' => $this->updatedAt,
        ];
    }
}
