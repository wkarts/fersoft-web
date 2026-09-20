<?php

return [
    'base_url' => env('CONNECT_API_BASE_URL', ''),
    'bootstrap_key' => env('CONNECT_API_BOOTSTRAP_KEY', ''),
    'timeout' => (int) env('CONNECT_API_TIMEOUT', 30),
    'connect_timeout' => (int) env('CONNECT_API_CONNECT_TIMEOUT', 10),
    'ddi' => env('CONNECT_API_DDI', '55'),
    'ddd' => env('CONNECT_API_DDD', '75'),
    'instance_prefix' => env('CONNECT_API_INSTANCE_PREFIX', ''),
    'webhook_secret' => env('CONNECT_API_WEBHOOK_SECRET', ''),
    'webhook_url' => env('CONNECT_API_WEBHOOK_URL', ''),
    'webhook_events' => [
        'MESSAGES_UPSERT',
        'MESSAGES_UPDATE',
        'MESSAGES_DELETE',
        'CONNECTION_UPDATE',
        'QRCODE_UPDATED',
        'CONTACTS_UPSERT',
        'LOGOUT_INSTANCE',
        'REMOVE_INSTANCE',
    ],
];
