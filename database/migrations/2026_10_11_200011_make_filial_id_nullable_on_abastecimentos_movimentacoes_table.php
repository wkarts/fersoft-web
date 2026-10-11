<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new \RuntimeException('Esta migration de nulidade requer MySQL.');
        }
        if (!Schema::hasTable('abastecimentos_movimentacoes') || !Schema::hasColumn('abastecimentos_movimentacoes', 'filial_id')) {
            throw new \RuntimeException('Coluna ARGWS esperada ausente: abastecimentos_movimentacoes.filial_id');
        }

        // Introspecção antes de MODIFY: não altere tipos/DEFAULT/COMMENT existentes.
        $column = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'abastecimentos_movimentacoes')
            ->where('COLUMN_NAME', 'filial_id')
            ->first(['COLUMN_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT', 'COLUMN_COMMENT', 'EXTRA']);

        if ($column === null || !preg_match('/^int(?:\(\d+\))?\s+unsigned$/i', trim((string) $column->COLUMN_TYPE))) {
            throw new \RuntimeException('Tipo divergente em abastecimentos_movimentacoes.filial_id. Esperado: INT UNSIGNED. Nenhuma alteração realizada.');
        }
        if (strtoupper((string) $column->IS_NULLABLE) === 'YES') {
            return; // Já atende ao requisito de nullable.
        }
        if ($column->COLUMN_DEFAULT !== null || trim((string) $column->COLUMN_COMMENT) !== '' || trim((string) $column->EXTRA) !== '') {
            throw new \RuntimeException('Metadados divergentes em abastecimentos_movimentacoes.filial_id; revisão manual necessária antes de MODIFY.');
        }

        // Altera somente a nulidade; não remove FK, índice ou dados.
        // MySQL pode exigir bloqueio de metadados durante este ALTER TABLE.
        DB::statement('ALTER TABLE `abastecimentos_movimentacoes` MODIFY COLUMN `filial_id` INT UNSIGNED NULL DEFAULT NULL');
    }

    public function down(): void
    {
        // Conservador: NÃO restabelecer NOT NULL. Valores NULL podem já existir,
        // e uma reversão poderia inviabilizar a gravação de registros legados.
    }
};
