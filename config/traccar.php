<?php
/*
 * Author: WOLF
 * Name: config.php
 * Modified : mar., 20 févr. 2024 14:25
 * Description: ...
 *
 * Copyright 2024 -[MR.WOLF]-[WS]-
 */

return [
    'base_url' => env('TRACCAR_BASE_URL'),
    'websocket_url' => env('TRACCAR_SOCKET_URL'),

    'auth' => [
        'username' => env('TRACCAR_USERNAME'),
        'password' => env('TRACCAR_PASSWORD'),
        'token' => env('TRACCAR_TOKEN'),
    ],
    'database' => [
        'connection' => env('TRACCAR_DB_CONNECTION', 'mysql'),
        'chunk' => 1000,
    ],
    'devices' => [
        'store_in_database' => env('TRACCAR_AUTO_SAVE_DEVICES', true)
    ],

    'webhook' => [
        'secret' => env('TRACCAR_WEBHOOK_SECRET'),
        'header' => env('TRACCAR_WEBHOOK_HEADER', 'X-Traccar-Webhook-Secret'),

        // Inbox persistente / idempotência do Event Forwarding.
        'max_attempts' => env('TRACCAR_WEBHOOK_MAX_ATTEMPTS', 20),
        'no_movement_max_attempts' => env('TRACCAR_WEBHOOK_NO_MOVEMENT_MAX_ATTEMPTS', 5),
        'retry_seconds' => env('TRACCAR_WEBHOOK_RETRY_SECONDS', 60),
        'processing_lock_seconds' => env('TRACCAR_WEBHOOK_PROCESSING_LOCK_SECONDS', 120),
        'retention_days' => env('TRACCAR_WEBHOOK_RETENTION_DAYS', 30),
        'reconcile_hours' => env('TRACCAR_WEBHOOK_RECONCILE_HOURS', 48),
    ],
];
