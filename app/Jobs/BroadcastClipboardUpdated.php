<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\ClipboardUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Broadcast clipboard updates with retry (Requirement 10.8).
 */
class BroadcastClipboardUpdated implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [1, 1, 1];

    public function __construct(
        public int $roomId,
        public string $text,
        public string $updatedAt,
    ) {
    }

    public function handle(): void
    {
        broadcast(new ClipboardUpdated($this->roomId, $this->text, $this->updatedAt));
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('BroadcastClipboardUpdated: delivery exhausted retries', [
            'room_id' => $this->roomId,
            'error'   => $exception->getMessage(),
        ]);
    }
}
