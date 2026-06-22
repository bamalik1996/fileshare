<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\CleanupExpiredMedia::class,
        \App\Console\Commands\FixFilePermissions::class,
        \App\Console\Commands\ShareCleanupExpired::class,
        \App\Console\Commands\ScheduleExpiryReminders::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Run media cleanup every 30 minutes
        $schedule->command('media:cleanup-expired')
                 ->everyThirtyMinutes()
                 ->withoutOverlapping();

        // Hourly cleanup of the new `shares` aggregate (task 3.4 in
        // .kiro/specs/fileshare-enhancements-bundle/tasks.md). Deletes
        // shares whose expiry is more than one hour in the past and
        // which are not favourited; cascades their media via Spatie.
        // Requirements 3.6, 3.7, 16.7.
        $schedule->command('shares:cleanup-expired')
                 ->hourly()
                 ->withoutOverlapping();

        // Purge upload sessions whose first chunk is > 24 h old without
        // completion (Requirement 9.8, task 14.3).
        $schedule->job(new \App\Jobs\CleanupExpiredUploadSessions())
                 ->hourly()
                 ->withoutOverlapping();

        $schedule->command('notifications:schedule-expiry-reminders')
                 ->everyMinute()
                 ->withoutOverlapping();

        $schedule->job(new \App\Jobs\CleanupRoomPresence())
                 ->everyThirtySeconds()
                 ->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}