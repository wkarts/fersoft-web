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

        if (!Schema::hasTable('producao_lotes') || !Schema::hasColumn('producao_lotes', 'id')) {
            throw new \RuntimeException('Tabela ou coluna esperada ausente: producao_lotes.id');
        }

        // Garante o índice antes da 090042, que adiciona FK para esta tabela.
        // Reutiliza PRIMARY/UNIQUE sobre id, inclusive quando possui outro nome.
        $rows = DB::select(
            'SELECT * FROM information_schema.STATISTICS '
            . 'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY INDEX_NAME, SEQ_IN_INDEX',
            [DB::getDatabaseName(), 'producao_lotes'],
            false
        );

        $indexes = [];
        foreach ($rows as $row) {
            $indexes[$row->INDEX_NAME][] = $row;
        }

        foreach ($indexes as $columns) {
            if (count($columns) !== 1) {
                continue;
            }

            $index = $columns[0];
            if ($index->COLUMN_NAME === 'id'
                && (int) $index->NON_UNIQUE === 0
                && $index->SUB_PART === null
                && strtoupper((string) $index->INDEX_TYPE) === 'BTREE'
                && strtoupper((string) ($index->IS_VISIBLE ?? 'YES')) === 'YES'
                && strtoupper((string) ($index->IGNORED ?? 'NO')) === 'NO') {
                return;
            }
        }

        $indexName = 'producao_lotes_id_unique';
        if (isset($indexes[$indexName])) {
            throw new \RuntimeException('producao_lotes: índice ' . $indexName
                . ' existente com outra definição ou indisponível para a FK; preservado.');
        }

        // Não inventa IDs nem remove registros para satisfazer a integridade.
        if (DB::selectOne(
            'SELECT 1 AS found FROM `producao_lotes` WHERE `id` IS NULL LIMIT 1',
            [],
            false
        ) !== null) {
            throw new \RuntimeException('producao_lotes.id: há identificadores nulos; nenhum registro foi alterado.');
        }

        if (DB::selectOne(
            'SELECT 1 AS found FROM `producao_lotes` GROUP BY `id` HAVING COUNT(*) > 1 LIMIT 1',
            [],
            false
        ) !== null) {
            throw new \RuntimeException('producao_lotes.id: há identificadores duplicados; nenhum registro foi alterado.');
        }

        // UNIQUE(id) atende a FK sem substituir a chave primária existente.
        Schema::table('producao_lotes', function (Blueprint $table) use ($indexName) {
            $table->unique(['id'], $indexName);
        });
    }

    public function down()
    {
        // Migration somente evolutiva: o índice pode estar em uso pela FK dos itens.
        return;
    }
};
