<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $isTestingEnv = (($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV')) === 'testing');

        if ($isTestingEnv) {
            putenv('BROADCAST_CONNECTION=log');
            putenv('BROADCAST_DRIVER=log');
            putenv('PUSHER_APP_KEY='.(getenv('PUSHER_APP_KEY') ?: 'testing-key'));
            putenv('PUSHER_APP_SECRET='.(getenv('PUSHER_APP_SECRET') ?: 'testing-secret'));
            putenv('PUSHER_APP_ID='.(getenv('PUSHER_APP_ID') ?: 'testing-app'));
            $_ENV['BROADCAST_CONNECTION'] = getenv('BROADCAST_CONNECTION') ?: 'log';
            $_ENV['BROADCAST_DRIVER'] = getenv('BROADCAST_DRIVER') ?: 'log';
            $_SERVER['BROADCAST_CONNECTION'] = $_ENV['BROADCAST_CONNECTION'];
            $_SERVER['BROADCAST_DRIVER'] = $_ENV['BROADCAST_DRIVER'];
        }

        if (empty($_SERVER['APP_KEY'] ?? null) && empty($_ENV['APP_KEY'] ?? null) && empty(getenv('APP_KEY') ?: null)) {
            $generatedAppKey = 'base64:'.base64_encode(random_bytes(32));
            putenv('APP_KEY='.$generatedAppKey);
            $_ENV['APP_KEY'] = $generatedAppKey;
            $_SERVER['APP_KEY'] = $generatedAppKey;
        }

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        if ($app->environment('testing')) {
            $testDbConnection = getenv('TEST_DB_CONNECTION') ?: 'sqlite';

            if ($testDbConnection === 'mysql') {
                $app['config']->set('database.default', 'mysql');
                $app['config']->set('database.connections.mysql.host', getenv('TEST_DB_HOST') ?: '127.0.0.1');
                $app['config']->set('database.connections.mysql.port', getenv('TEST_DB_PORT') ?: '3306');
                $app['config']->set('database.connections.mysql.database', getenv('TEST_DB_DATABASE') ?: 'fersoft_test');
                $app['config']->set('database.connections.mysql.username', getenv('TEST_DB_USERNAME') ?: 'root');
                $app['config']->set('database.connections.mysql.password', getenv('TEST_DB_PASSWORD') ?: 'root');
            } else {
                $app['config']->set('database.default', 'sqlite');
                $app['config']->set('database.connections.sqlite.database', ':memory:');
            }

            $app['config']->set('broadcasting.default', 'log');
            $app['config']->set('broadcasting.connections.pusher.key', 'testing-key');
            $app['config']->set('broadcasting.connections.pusher.secret', 'testing-secret');
            $app['config']->set('broadcasting.connections.pusher.app_id', 'testing-app');

            if (empty(config('app.key'))) {
                $app['config']->set('app.key', $_ENV['APP_KEY'] ?? getenv('APP_KEY'));
            }
        }

        return $app;
    }
}
