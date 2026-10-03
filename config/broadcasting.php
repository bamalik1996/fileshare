<?php

return [

    'default' => env('BROADCAST_CONNECTION', 'log'),

    /*
    | Frontend Echo / Pusher-js settings (public). These must survive
    | `php artisan config:cache` — never read via env() in Blade.
    */
    'reverb_frontend' => [
        'key'    => env('REVERB_APP_KEY'),
        'host'   => env('REVERB_FRONTEND_HOST', env('REVERB_HOST', 'localhost')),
        'port'   => (int) env('REVERB_PORT', 6001),
        'scheme' => env('REVERB_SCHEME', 'http'),
    ],

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                // Server-side broadcaster talks to the local Reverb process.
                // Public websocket host/port stay in reverb_frontend above.
                'host' => env('REVERB_SERVER_HOST', '127.0.0.1'),
                'port' => (int) env('REVERB_SERVER_PORT', env('REVERB_PORT', 8080)),
                'scheme' => env('REVERB_SERVER_SCHEME', 'http'),
                'useTLS' => env('REVERB_SERVER_SCHEME', 'http') === 'https',
            ],
            'client_options' => [],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
