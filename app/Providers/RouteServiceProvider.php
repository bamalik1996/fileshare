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

        // Share-password verification bucket (Requirement 2.7).
        // Both the {@see \App\Http\Middleware\PasswordVerifyRateLimit}
        // pre-check and the downstream verify controller share this
        // limiter; Limit::perMinutes() is computed from the configured
        // decay window so they agree on max-attempts and decay
        // automatically.
        FacadesRateLimiter::for(
            \App\Http\Middleware\PasswordVerifyRateLimit::LIMITER,
            function (Request $request) {
                $maxAttempts = (int) config('airtoshare.password_verify_rate_limit.max_attempts', 5);
                $decaySeconds = (int) config('airtoshare.password_verify_rate_limit.decay_seconds', 15 * 60);
                $shareId = $request->route('share');
                $shareKey = is_object($shareId) && method_exists($shareId, 'getKey')
                    ? (string) $shareId->getKey()
                    : (is_scalar($shareId) ? (string) $shareId : 'unknown');

                return Limit::perMinutes(
                    max(1, (int) ceil($decaySeconds / 60)),
                    max(1, $maxAttempts)
                )->by($request->ip() . '|' . $shareKey);
            }
        );

        FacadesRateLimiter::for('api-v2', function (Request $request) {
            $keyId = (string) ($request->attributes->get('api_key_id') ?? $request->ip());

            return Limit::perMinute(60)->by('apikey:' . $keyId);
        });
    }
}
