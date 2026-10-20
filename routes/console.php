<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| The app boots through Application::configure() (Laravel 11+ structure),
| which never loads app/Console/Kernel.php, so its schedule() method was
| silently ignored. Tasks are registered here instead. Production still
| needs the usual cron entry: * * * * * php artisan schedule:run
|
*/

// Run media cleanup every 30 minutes
Schedule::command('media:cleanup-expired')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// Permanently remove accounts past the 48h deletion grace period.
Schedule::command('accounts:purge-scheduled-deletions')
    ->hourly()
    ->withoutOverlapping();

// Delete shares whose expiry is more than one hour in the past and which
// are not favourited; cascades their media via Spatie.
Schedule::command('shares:cleanup-expired')
    ->hourly()
    ->withoutOverlapping();

// Purge upload sessions whose first chunk is > 24 h old without completion.
Schedule::job(new \App\Jobs\CleanupExpiredUploadSessions())
    ->hourly()
    ->withoutOverlapping();

Schedule::command('notifications:schedule-expiry-reminders')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::job(new \App\Jobs\CleanupRoomPresence())
    ->everyThirtySeconds()
    ->withoutOverlapping();
