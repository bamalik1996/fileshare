<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Soft-fail wrapper around Laravel's broadcast() helper.
 *
 * Realtime delivery must never fail the primary HTTP request (e.g. saving
 * text). When Reverb/Pusher is down or misconfigured, we log and continue.
 */
final class SafeBroadcast
{
    public static function event(mixed $event): void
    {
        try {
            broadcast($event);
        } catch (BroadcastException $e) {
            Log::warning('Broadcast failed (soft-fail)', [
                'event' => is_object($event) ? $event::class : gettype($event),
                'error' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Broadcast failed unexpectedly (soft-fail)', [
                'event' => is_object($event) ? $event::class : gettype($event),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
