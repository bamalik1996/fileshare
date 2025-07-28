<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\CleanupExpiredMedia::class,
        \App\Console\Commands\FixFilePermissions::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Run media cleanup every 30 minutes
        $schedule->command('media:cleanup-expired')
                 ->everyThirtyMinutes()
                 ->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}