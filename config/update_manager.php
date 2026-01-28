<?php

return [
    'default_mode' => env('UPDATE_MODE', 'full_release'),

    'backup_path' => storage_path('app/updates/backups'),

    'release_path' => storage_path('app/updates/releases'),

    'lock_key' => 'system-update-global-lock',

    'healthcheck_url' => env('APP_HEALTHCHECK_URL', null),

    'backup' => [
        'database' => [
            'binary' => env('MYSQLDUMP_BINARY', 'mysqldump'),
            'compress' => true,
        ],
    ],
];
