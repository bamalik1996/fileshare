<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Share;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards broadcasting authorisation for password-protected shares (Req 14.7).
 */
class BroadcastingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('broadcasting/auth') && $request->isMethod('POST')) {
            $channel = (string) $request->input('channel_name', '');

            if (preg_match('/^private-share\.(\d+)$/', $channel, $matches) === 1) {
                $share = Share::query()->find((int) $matches[1]);

                if ($share !== null && ! $share->allowsBroadcastSubscription($request)) {
                    return response('Forbidden', 403);
                }
            }
        }

        return $next($request);
    }
}
