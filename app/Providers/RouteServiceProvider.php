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
            return Limit::perMinute(10)->by($request->ip());  // 10 requests per minute per IP for write operations
        });
        
        FacadesRateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());  // 60 requests per minute per IP for general API
        });
    }
}
