<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'broadcast.guest', 'broadcasting.guard']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('auth.login'));
        $middleware->redirectUsersTo(fn () => route('account.shares'));

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\SeoIndexing::class);

        // ResolvePrincipal runs on every request so $request->principal()
        // is always populated for controllers, services, gates, and
        // broadcast authorisation closures (design.md > Architecture >
        // Authentication and Authorisation Layers; Requirements 16.4,
        // 16.13, 18.5).
        $middleware->append(\App\Http\Middleware\ResolvePrincipal::class);

        // Route-level aliases for password-protected Share routes
        // (Requirements 2.3, 2.4, 2.6, 2.7). These are NOT global —
        // they run only on routes that opt in via
        // ->middleware(['share.password.throttle','share.password']).
        // The throttle alias intentionally precedes the gate alias in
        // expected route declarations so a saturated bucket short-
        // circuits before the bcrypt path is invoked.
        $middleware->alias([
            'share.password'          => \App\Http\Middleware\SharePasswordGate::class,
            'share.password.throttle' => \App\Http\Middleware\PasswordVerifyRateLimit::class,
            'room.code.throttle'      => \App\Http\Middleware\RoomCodeRateLimit::class,
            'broadcast.guest'         => \App\Http\Middleware\BroadcastGuestAuth::class,
            'broadcasting.guard'      => \App\Http\Middleware\BroadcastingMiddleware::class,
            'api.key.auth'            => \App\Http\Middleware\ApiKeyAuth::class,
            'reject.e2ee.keys'        => \App\Http\Middleware\RejectE2eeKeyFields::class,
            'account.verified'        => \App\Http\Middleware\EnsureAccountEmailIsVerified::class,
        ]);

        // Resend delivery webhooks (POST /resend/webhook)
        $middleware->validateCsrfTokens(except: [
            'resend/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
