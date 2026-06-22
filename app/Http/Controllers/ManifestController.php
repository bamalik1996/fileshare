<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ManifestController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'name'             => 'AirToShare',
            'short_name'       => 'AirToShare',
            'description'      => 'Instant file and text sharing across devices',
            'start_url'        => '/',
            'display'          => 'standalone',
            'theme_color'      => '#1A73E8',
            'background_color' => '#ffffff',
            'icons'            => [
                [
                    'src'   => '/android-chrome-192x192.png',
                    'sizes' => '192x192',
                    'type'  => 'image/png',
                ],
                [
                    'src'   => '/android-chrome-512x512.png',
                    'sizes' => '512x512',
                    'type'  => 'image/png',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
        ]);
    }
}
