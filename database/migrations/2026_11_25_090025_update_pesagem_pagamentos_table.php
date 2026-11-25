<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new \RuntimeException('Esta migration requer MySQL.');
        }

        if (!Schema::hasTable('pesagem_pagamentos')) {
            throw new \RuntimeException('Tabela esperada ausente: pesagem_pagamentos');
        }

        $this->ensureTableCollation('pesagem_pagamentos', 'utf8mb4_unicode_ci');
    }

    public function down()
    {
        // Migration somente evolutiva.
        return;
    }

    private function ensureTableCollation(string $tableName, string $collation): void
    {
        $current = DB::selectOne(
            'SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [DB::getDatabaseName(), $tableName]
        );
        if ($current !== null && $current->TABLE_COLLATION === $collation) {
            return;
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $collation)) {
            throw new \RuntimeException('Collation inválida.');
        }
        DB::statement('ALTER TABLE `' . $tableName . '` DEFAULT CHARACTER SET utf8mb4 COLLATE ' . $collation);
    }

};
