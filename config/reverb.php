<?php

return [
    'default' => env('REVERB_APP', 'default'),

    'apps' => [
        'default' => [
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'host' => env('REVERB_HOST', '127.0.0.1'),
            'port' => env('REVERB_PORT', 8080),
            'scheme' => env('REVERB_SCHEME', 'http'),
            'allowed_origins' => ['*'],
            'max_message_size' => 10_000,
            'ping_interval' => 30,
            'ping_timeout' => 30,
        ],
    ],
];
