<?php

namespace Tests\Unit;

use App\Http\Middleware\SeoIndexing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SeoIndexingTest extends TestCase
{
    public function test_share_route_is_marked_noindex(): void
    {
        $request = Request::create('/s/test-uuid', 'GET');
        $request->setRouteResolver(function () use ($request) {
            return (new \Illuminate\Routing\Route('GET', '/s/{share}', []))->bind($request)->name('share.show');
        });

        $middleware = new SeoIndexing();
        $response = $middleware->handle($request, fn () => new Response('ok', 200));

        $this->assertSame('noindex, nofollow', $response->headers->get('X-Robots-Tag'));
        $this->assertTrue(View::shared('seoNoindex'));
    }

    public function test_homepage_is_indexable(): void
    {
        $request = Request::create('/', 'GET');
        $request->setRouteResolver(function () use ($request) {
            return (new \Illuminate\Routing\Route('GET', '/', []))->bind($request);
        });

        View::share('seoNoindex', null);

        $middleware = new SeoIndexing();
        $response = $middleware->handle($request, fn () => new Response('ok', 200));

        $this->assertNull($response->headers->get('X-Robots-Tag'));
        $this->assertFalse(View::shared('seoNoindex'));
    }
}
