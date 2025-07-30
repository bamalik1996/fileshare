<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
                "script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://connect.facebook.net https://www.google.com https://www.gstatic.com; " .
                "connect-src 'self' https://www.google.com https://www.google-analytics.com; " .
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
                "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
                "img-src 'self' data: blob:; " .
                "frame-src https://www.google.com https://www.gstatic.com;"
        );



        return $response;
    }
}
