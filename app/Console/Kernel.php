<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
       // Commands\CleanupExpiredContent::class,
       // Commands\FixFilePermissions::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Run cleanup every 30 minutes
     //   $schedule->command('cleanup:expired-content')
       //          ->everyThirtyMinutes()
         //        ->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}