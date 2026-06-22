<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks private / transactional routes as noindex and sets X-Robots-Tag.
 */
class SeoIndexing
{
    /** @var list<string> */
    private const NOINDEX_ROUTE_NAMES = [
        'share.show',
        'room.show',
        'public.share.show',
        'qr.show',
        'media.download.file',
        'auth.login',
        'auth.register',
        'auth.forgot',
        'password.reset',
        'password.update',
        'verification.notice',
        'verification.verify',
        'account.shares',
        'account.destroy',
        'account.shares.favourite',
        'account.shares.public.enable',
        'account.shares.public.disable',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $noindex = $this->shouldNoIndex($request);

        View::share('seoNoindex', $noindex);

        $response = $next($request);

        if ($noindex) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow', false);
        }

        return $response;
    }

    private function shouldNoIndex(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        return $routeName !== null
            && in_array($routeName, self::NOINDEX_ROUTE_NAMES, true);
    }
}
