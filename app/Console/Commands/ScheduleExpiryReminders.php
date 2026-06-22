<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendExpiryReminder;
use App\Models\ShareNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Dispatch expiry reminder jobs for the next 60-second window (Req 11.1).
 */
class ScheduleExpiryReminders extends Command
{
    protected $signature = 'notifications:schedule-expiry-reminders';

    protected $description = 'Dispatch pre-expiry reminder jobs due in the next minute.';

    public function handle(): int
    {
        $windowEnd = Carbon::now()->addMinute();

        ShareNotification::query()
            ->whereNull('sent_at')
            ->whereBetween('send_at', [Carbon::now(), $windowEnd])
            ->orderBy('id')
            ->each(function (ShareNotification $row): void {
                SendExpiryReminder::dispatch($row);
            });

        return self::SUCCESS;
    }
}
