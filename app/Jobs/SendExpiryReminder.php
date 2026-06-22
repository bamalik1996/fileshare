<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\ExpiryReminderBrowser;
use App\Mail\ShareExpiryReminder;
use App\Models\Share;
use App\Models\ShareNotification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Deliver a single pre-expiry reminder row (Requirement 11).
 */
class SendExpiryReminder implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(
        public ShareNotification $notification,
        public bool $isRetry = false,
    ) {
    }

    public function handle(NotificationService $notificationService): void
    {
        $row = ShareNotification::query()->find($this->notification->id);

        if ($row === null || $row->sent_at !== null) {
            return;
        }

        $share = Share::query()->find($row->share_id);

        if ($share === null || $share->isExpired()) {
            $row->forceFill(['sent_at' => Carbon::now()])->saveQuietly();

            return;
        }

        try {
            if ($row->channel === ShareNotification::CHANNEL_EMAIL) {
                $email = $notificationService->resolveNotifyEmail($share);

                if ($email === null) {
                    throw new \RuntimeException('No email address for notification.');
                }

                Mail::to($email)->send(new ShareExpiryReminder($share));
            } else {
                $ip = $notificationService->resolveOwnerIp($share);

                if ($ip === null) {
                    throw new \RuntimeException('Browser channel requires IP owner.');
                }

                broadcast(new ExpiryReminderBrowser(
                    $share,
                    $ip,
                    'Your share expires at ' . $share->expires_at?->toDateTimeString(),
                ));
            }

            $row->forceFill(['sent_at' => Carbon::now()])->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('SendExpiryReminder: delivery failed', [
                'notification_id' => $row->id,
                'channel'         => $row->channel,
                'retry'           => $this->isRetry,
                'error'           => $e->getMessage(),
            ]);

            $row->failure_count = ($row->failure_count ?? 0) + 1;
            $row->saveQuietly();

            if (! $this->isRetry && $row->failure_count === 1) {
                self::dispatch($row, true)
                    ->delay(Carbon::now()->addMinutes(5)->addSeconds(random_int(0, 60)));
            } else {
                // Terminal failure counts as sent for once-per-cycle rule (Req 11.7).
                $row->forceFill(['sent_at' => Carbon::now()])->saveQuietly();
            }
        }
    }
}
