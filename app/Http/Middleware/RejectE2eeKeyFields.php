<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects request bodies that attempt to send E2EE key material (Req 15.2).
 */
class RejectE2eeKeyFields
{
    private const BLOCKED = ['key', 'e2ee_key', 'encryption_key', 'aes_key', 'secret_key'];

    public function handle(Request $request, Closure $next): Response
    {
        foreach (array_keys($request->all()) as $field) {
            if (in_array(strtolower($field), self::BLOCKED, true)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Invalid request field.',
                ], 422);
            }
        }

        return $next($request);
    }
}
