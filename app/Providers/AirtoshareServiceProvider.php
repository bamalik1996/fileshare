<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Loads the airtoshare.* configuration namespace and acts as the
 * registration point for AirToShareA enhancement services.
 *
 * Wiring this provider in bootstrap/providers.php (the Laravel 12
 * equivalent of the config/app.php providers array) guarantees that
 * config('airtoshare.*') keys are always populated, even before the
 * compiled config cache is written.
 */
class AirtoshareServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/airtoshare.php',
            'airtoshare'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
