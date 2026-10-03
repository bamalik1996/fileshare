<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\CollectFileReceived;
use App\Models\Share;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Email the collect-inbox owner when a guest uploads a file.
 */
class SendCollectFileReceived implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public function __construct(
        public Share $share,
        public string $fileName,
        public int $fileSize,
    ) {
    }

    public function handle(NotificationService $notificationService): void
    {
        $share = Share::query()->find($this->share->id);

        if ($share === null || ! $share->isCollect()) {
            return;
        }

        $email = $notificationService->resolveNotifyEmail($share);

        if ($email === null) {
            Log::info('SendCollectFileReceived: no owner email', [
                'share_id' => $share->id,
            ]);

            return;
        }

        try {
            Mail::to($email)->send(new CollectFileReceived(
                $share,
                $this->fileName,
                $this->fileSize,
            ));
        } catch (\Throwable $e) {
            Log::warning('SendCollectFileReceived: delivery failed', [
                'share_id' => $share->id,
                'email'    => $email,
                'error'    => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
