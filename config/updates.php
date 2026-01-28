<?php

return [

    'default_provider' => env('UPDATE_PROVIDER', 'github'),
    'mode'             => env('UPDATE_MODE', 'stable'),
    'dev_branch'       => env('UPDATE_DEV_BRANCH', 'develop'),

    /**
     * Chave usada no .env para armazenar a versão atual (ex.: VERSION=1.2.3).
     */
    'env_version_key' => env('UPDATE_ENV_VERSION_KEY', 'VERSION'),

    'providers' => [
        'github' => [
            'token'        => env('UPDATE_GITHUB_TOKEN'),
            'username'     => env('UPDATE_GITHUB_USERNAME'),
            'password'     => env('UPDATE_GITHUB_PASSWORD'),
            'repository'   => env('UPDATE_GITHUB_REPOSITORY'),
            'organization' => env('UPDATE_GITHUB_ORGANIZATION'),
            'branch'       => env('UPDATE_GITHUB_BRANCH', 'main'),
        ],

        'svn' => [
            'username'   => env('UPDATE_SVN_USERNAME'),
            'password'   => env('UPDATE_SVN_PASSWORD'),
            'repository' => env('UPDATE_SVN_REPOSITORY'),
            'branch'     => env('UPDATE_SVN_BRANCH', 'trunk'),
        ],

        'filesystem' => [
            'path' => env('UPDATE_FILESYSTEM_PATH'),
        ],
    ],

    'allow_downgrade' => env('UPDATE_ALLOW_DOWNGRADE', false),
    'auto_update'     => env('UPDATE_AUTO_UPDATE', false),

    /**
     * Disco usado para armazenar pacotes baixados e backups quando o caminho for relativo.
     */
    'storage_disk' => env('UPDATE_STORAGE_DISK', 'local'),

    /**
     * Configuração de backup antes de cada upgrade/downgrade.
     */
    'backup' => [
        'enabled' => env('UPDATE_BACKUP_ENABLED', true),
        'skip_database_backup' => env('UPDATE_SKIP_DB_BACKUP', false),
        // database | full | none
        'driver'  => env('UPDATE_BACKUP_DRIVER', 'database'),
        // Caminho absoluto OU relativo ao storage_disk (storage/app/...)
        'path'    => env('UPDATE_BACKUP_PATH', storage_path('app/updates/backups')),
        'binaries' => [
            'mysqldump' => env('UPDATE_BACKUP_MYSQLDUMP_PATH', 'mysqldump'),
            'pg_dump'   => env('UPDATE_BACKUP_PG_DUMP_PATH', 'pg_dump'),
        ],
    ],

    /**
     * Configuração de composer para o processo de update.
     */
    'composer' => [
        'enabled'        => env('UPDATE_COMPOSER_ENABLED', true),
        // none | install | update | dump-autoload
        'default_action' => env('UPDATE_COMPOSER_ACTION', 'none'),
        'timeout'        => env('UPDATE_COMPOSER_TIMEOUT', 600),
    ],

    /**
     * Catálogo de pacotes/arquivos de upgrade.
     */
    'archive' => [
        'keep_downloads' => env('UPDATE_KEEP_DOWNLOADS', true),
        'path'           => env('UPDATE_ARCHIVE_PATH', storage_path('app/updates/archives')),
    ],

    /**
     * Quais pastas/arquivos da aplicação são sincronizados a partir do pacote baixado.
     */
    'sync' => [
        'include' => [
            'app',
            'bootstrap',
            'config',
            'database',
            'public',
            'resources',
            'routes',
        ],
        'exclude' => [
            'storage',
            'vendor',
            'public/storage',
            'node_modules',
            '.env',
            '.git',
            '.github',
            'tests',
        ],
    ],

    // As migrations de infraestrutura rodam sempre na connection padrão.
];
