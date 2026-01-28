<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        if (!app()->environment('testing')) {
            throw new RuntimeException('As suites de teste só podem rodar com APP_ENV=testing.');
        }

        $defaultConnection = config('database.default');
        $sqliteDatabase = config('database.connections.sqlite.database');

        if ($defaultConnection !== 'sqlite' || $sqliteDatabase !== ':memory:') {
            throw new RuntimeException(
                sprintf(
                    'Execução abortada: testes devem usar conexão sqlite em memória (atual: %s / %s).',
                    $defaultConnection,
                    $sqliteDatabase
                )
            );
        }
    }
}
