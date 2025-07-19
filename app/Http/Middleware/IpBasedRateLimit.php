<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IpBasedRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $key = 'rate_limit_' . $ip;
        
        // Allow 200 requests per hour per IP
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= 200) {
            return response()->json([
                'error' => 'Too many requests. Please try again later.'
            ], 429);
        }
        
        Cache::put($key, $attempts + 1, 3600); // 1 hour
        
        return $next($request);
    }
}