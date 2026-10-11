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
        if (!Schema::hasTable('documento_fiscal_logs') || !Schema::hasColumn('documento_fiscal_logs', 'empresa_id')) {
            throw new \RuntimeException('Coluna ARGWS esperada ausente: documento_fiscal_logs.empresa_id');
        }

        // Introspecção antes de MODIFY: não altere tipos/DEFAULT/COMMENT existentes.
        $column = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'documento_fiscal_logs')
            ->where('COLUMN_NAME', 'empresa_id')
            ->first(['COLUMN_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT', 'COLUMN_COMMENT', 'EXTRA']);

        if ($column === null || !preg_match('/^int(?:\(\d+\))?\s+unsigned$/i', trim((string) $column->COLUMN_TYPE))) {
            throw new \RuntimeException('Tipo divergente em documento_fiscal_logs.empresa_id. Esperado: INT UNSIGNED. Nenhuma alteração realizada.');
        }
        if (strtoupper((string) $column->IS_NULLABLE) === 'YES') {
            return; // Já atende ao requisito de nullable.
        }
        if ($column->COLUMN_DEFAULT !== null || trim((string) $column->COLUMN_COMMENT) !== '' || trim((string) $column->EXTRA) !== '') {
            throw new \RuntimeException('Metadados divergentes em documento_fiscal_logs.empresa_id; revisão manual necessária antes de MODIFY.');
        }

        // Altera somente a nulidade; não remove FK, índice ou dados.
        // MySQL pode exigir bloqueio de metadados durante este ALTER TABLE.
        DB::statement('ALTER TABLE `documento_fiscal_logs` MODIFY COLUMN `empresa_id` INT UNSIGNED NULL DEFAULT NULL');
    }

    public function down(): void
    {
        // Conservador: NÃO restabelecer NOT NULL. Valores NULL podem já existir,
        // e uma reversão poderia inviabilizar a gravação de registros legados.
    }
};
