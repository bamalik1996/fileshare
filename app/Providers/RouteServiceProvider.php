<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\RateLimiter as FacadesRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        FacadesRateLimiter::for('save-text', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());  // 5 requests per minute per IP
        });
    }
}
