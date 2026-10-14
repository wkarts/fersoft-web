<?php

use Database\Seeders\MtrResiduosSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permite catálogo MTR global (empresa_id NULL) e aplica o seeder automaticamente
 * durante php artisan migrate --force, usado pela atualização do FERSOFT WEB.
 *
 * Não altera a migration histórica e não remove registros existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mtr_residuos')) {
            throw new RuntimeException('Tabela mtr_residuos ausente. Execute as migrations históricas primeiro.');
        }

        if (!Schema::hasColumn('mtr_residuos', 'empresa_id')) {
            throw new RuntimeException('Coluna mtr_residuos.empresa_id ausente.');
        }

        if (DB::getDriverName() === 'mysql') {
            $this->enableGlobalCatalogInMySql();
        } else {
            // Compatibilidade com o SQLite de testes e drivers suportados
            // pelo Schema Builder do Laravel.
            Schema::table('mtr_residuos', function (Blueprint $table): void {
                $table->unsignedInteger('empresa_id')->nullable()->change();
            });
        }

        app(MtrResiduosSeeder::class)->run();
    }

    public function down(): void
    {
        // Conservador: não excluir o catálogo, nem tornar empresa_id NOT NULL,
        // pois isso descartaria registros e bloquearia entradas globais.
    }

    private function enableGlobalCatalogInMySql(): void
    {
        $schemaName = DB::getDatabaseName();
        $column = DB::selectOne(
            'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$schemaName, 'mtr_residuos', 'empresa_id']
        );

        if ($column === null) {
            throw new RuntimeException('Não foi possível ler a definição de mtr_residuos.empresa_id.');
        }

        if (strtoupper((string) $column->IS_NULLABLE) === 'YES') {
            return;
        }

        $type = (string) $column->COLUMN_TYPE;
        if (!preg_match('/^(?:int|bigint)(?:\(\d+\))? unsigned$/iD', $type)
            || trim((string) $column->EXTRA) !== '') {
            throw new RuntimeException('Tipo ou atributos inesperados de mtr_residuos.empresa_id. Nenhuma alteração aplicada.');
        }

        $references = DB::select(
            'SELECT k.CONSTRAINT_NAME, k.REFERENCED_TABLE_NAME,
                    k.REFERENCED_COLUMN_NAME, r.UPDATE_RULE, r.DELETE_RULE
             FROM information_schema.KEY_COLUMN_USAGE k
             JOIN information_schema.REFERENTIAL_CONSTRAINTS r
               ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
              AND r.TABLE_NAME = k.TABLE_NAME
              AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
             WHERE k.TABLE_SCHEMA = ? AND k.TABLE_NAME = ?
               AND k.COLUMN_NAME = ? AND k.REFERENCED_TABLE_NAME IS NOT NULL',
            [$schemaName, 'mtr_residuos', 'empresa_id']
        );

        if (count($references) > 1) {
            throw new RuntimeException('Múltiplas chaves estrangeiras em mtr_residuos.empresa_id; requer conferência.');
        }

        $parts = [];
        foreach ($references as $reference) {
            $number = DB::selectOne(
                'SELECT COUNT(*) AS total FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
                [$schemaName, 'mtr_residuos', $reference->CONSTRAINT_NAME]
            );
            if ((int) $number->total !== 1) {
                throw new RuntimeException('Não é seguro modificar uma FK composta em mtr_residuos.empresa_id.');
            }

            $parts[] = 'DROP FOREIGN KEY ' . $this->identifier((string) $reference->CONSTRAINT_NAME);
        }

        $sqlColumn = 'MODIFY COLUMN `empresa_id` ' . $type . ' NULL';
        if ($column->COLUMN_DEFAULT !== null) {
            $sqlColumn .= ' DEFAULT ' . DB::getPdo()->quote((string) $column->COLUMN_DEFAULT);
        } else {
            $sqlColumn .= ' DEFAULT NULL';
        }
        if ((string) $column->COLUMN_COMMENT !== '') {
            $sqlColumn .= ' COMMENT ' . DB::getPdo()->quote((string) $column->COLUMN_COMMENT);
        }
        $parts[] = $sqlColumn;

        foreach ($references as $reference) {
            $onDelete = $this->foreignAction((string) $reference->DELETE_RULE);
            $onUpdate = $this->foreignAction((string) $reference->UPDATE_RULE);
            $parts[] = sprintf(
                'ADD CONSTRAINT %s FOREIGN KEY (`empresa_id`) REFERENCES %s (%s) ON DELETE %s ON UPDATE %s',
                $this->identifier((string) $reference->CONSTRAINT_NAME),
                $this->identifier((string) $reference->REFERENCED_TABLE_NAME),
                $this->identifier((string) $reference->REFERENCED_COLUMN_NAME),
                $onDelete,
                $onUpdate
            );
        }

        // Mesmo ALTER mantém o vínculo FK após alterar apenas a nulidade.
        DB::statement('ALTER TABLE `mtr_residuos` ' . implode(', ', $parts));
    }

    private function identifier(string $value): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/D', $value)) {
            throw new RuntimeException('Identificador de esquema inesperado no catálogo MTR.');
        }

        return '`' . $value . '`';
    }

    private function foreignAction(string $value): string
    {
        $action = strtoupper(trim($value));
        if (!in_array($action, ['CASCADE', 'RESTRICT', 'NO ACTION', 'SET NULL'], true)) {
            throw new RuntimeException('Ação de chave estrangeira não prevista no catálogo MTR.');
        }

        return $action;
    }
};