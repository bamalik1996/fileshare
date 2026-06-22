<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountEmailIsVerified
{
    public function handle(Request $request, Closure $next, ?string $redirectRoute = null): Response
    {
        $account = $request->user('account');

        if (
            ! $account
            || ($account instanceof MustVerifyEmail && ! $account->hasVerifiedEmail())
        ) {
            return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : Redirect::guest(route($redirectRoute ?: 'verification.notice'));
        }

        return $next($request);
    }
}
