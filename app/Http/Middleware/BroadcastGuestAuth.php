<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Supplies a session-scoped guest identity for /broadcasting/auth.
 *
 * Laravel's Pusher/Reverb broadcaster rejects private-channel auth when
 * `$request->user()` is null, before `routes/channels.php` callbacks run.
 * AirToShare's default flow is IP-guest (no Account login), so we bind a
 * lightweight GenericUser derived from the session id.
 */
class BroadcastGuestAuth
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() === null && $request->hasSession()) {
            $sessionId = $request->session()->getId();

            $request->setUserResolver(static fn () => new GenericUser([
                'id' => $sessionId,
            ]));
        }

        return $next($request);
    }
}
