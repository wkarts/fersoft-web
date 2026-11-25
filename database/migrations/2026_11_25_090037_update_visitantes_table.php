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

        if (!Schema::hasTable('visitantes')) {
            throw new \RuntimeException('Tabela esperada ausente: visitantes');
        }

        $this->addIndexIfMissing('visitantes', 'visitantes_empresa_cpf_unique', ['empresa_id', 'cpf'], true);
    }

    public function down()
    {
        // Migration somente evolutiva.
        return;
    }

    private function addIndexIfMissing(string $tableName, string $indexName, array $columns, bool $unique = false): void
    {
        $rows = DB::select(
            'SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE, SUB_PART, COLLATION, INDEX_TYPE '
            . 'FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY INDEX_NAME, SEQ_IN_INDEX',
            [DB::getDatabaseName(), $tableName]
        );
        $indexes = [];
        foreach ($rows as $row) {
            $indexes[$row->INDEX_NAME]['columns'][] = $row->COLUMN_NAME;
            $indexes[$row->INDEX_NAME]['unique'] = (int) $row->NON_UNIQUE === 0;
            $indexes[$row->INDEX_NAME]['simple'] = ($indexes[$row->INDEX_NAME]['simple'] ?? true)
                && $row->SUB_PART === null && $row->COLLATION === 'A' && strtoupper($row->INDEX_TYPE) === 'BTREE';
        }
        $expected = ['columns' => $columns, 'unique' => $unique, 'simple' => true];
        if (isset($indexes[$indexName])) {
            if ($indexes[$indexName] !== $expected) {
                throw new \RuntimeException($tableName . ": índice {$indexName} existente com outra definição; preservado.");
            }
            return;
        }
        foreach ($indexes as $existingName => $index) {
            if ($index === $expected && ($indexName !== 'PRIMARY' || $existingName === 'PRIMARY')) {
                return; // Índice equivalente já presente, mesmo com outro nome.
            }
        }
        $quoted = implode(', ', array_map(static fn ($column) => "`{$column}`", $columns));
        $notNull = implode(' AND ', array_map(static fn ($column) => "`{$column}` IS NOT NULL", $columns));
        if ($unique && DB::selectOne('SELECT 1 AS found FROM `' . $tableName . '` WHERE ' . $notNull
            . ' GROUP BY ' . $quoted . ' HAVING COUNT(*) > 1 LIMIT 1') !== null) {
            throw new \RuntimeException($tableName . ": duplicidades impedem o índice {$indexName}; nenhum registro foi alterado.");
        }
        if ($indexName === 'PRIMARY') {
            $nulls = implode(' OR ', array_map(static fn ($column) => "`{$column}` IS NULL", $columns));
            if (DB::selectOne('SELECT 1 AS found FROM `' . $tableName . '` WHERE ' . $nulls . ' LIMIT 1') !== null) {
                throw new \RuntimeException($tableName . ': valores nulos impedem a chave primária.');
            }
        }
        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName, $unique) {
            if ($unique) {
                $table->unique($columns, $indexName);
            } else {
                $table->index($columns, $indexName);
            }
        });
    }

};
