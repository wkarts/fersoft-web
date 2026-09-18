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

        if ($defaultConnection === 'sqlite') {
            $sqliteDatabase = config('database.connections.sqlite.database');

            if ($sqliteDatabase !== ':memory:') {
                throw new RuntimeException(
                    sprintf(
                        'Execução abortada: testes SQLite devem usar banco em memória (atual: %s).',
                        $sqliteDatabase
                    )
                );
            }

            return;
        }

        if ($defaultConnection === 'mysql') {
            $mysqlDatabase = (string) config('database.connections.mysql.database');

            if ($mysqlDatabase !== 'fersoft_test') {
                throw new RuntimeException(
                    sprintf(
                        'Execução abortada: testes MySQL só podem usar o banco isolado fersoft_test (atual: %s).',
                        $mysqlDatabase
                    )
                );
            }

            return;
        }

        throw new RuntimeException(
            sprintf(
                'Execução abortada: conexão de testes não autorizada (%s).',
                $defaultConnection
            )
        );
    }
}
