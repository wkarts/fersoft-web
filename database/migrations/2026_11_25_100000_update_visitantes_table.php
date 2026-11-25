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

        if (!Schema::hasTable('visitantes')
            || !Schema::hasColumn('visitantes', 'empresa_id')
            || !Schema::hasColumn('visitantes', 'cpf')) {
            throw new \RuntimeException('Estrutura esperada ausente: visitantes.empresa_id / visitantes.cpf.');
        }

        $indexes = $this->indexDefinitions('visitantes');
        $global = ['columns' => ['cpf'], 'unique' => true, 'full_columns' => true];
        $company = ['columns' => ['empresa_id', 'cpf'], 'unique' => true, 'full_columns' => true];

        // Só remove o índice global identificado no dump, sem atingir outros índices.
        if (isset($indexes['cpf']) && $indexes['cpf'] !== $global) {
            throw new \RuntimeException('visitantes: o índice cpf tem definição diferente da esperada; preservado.');
        }

        if (isset($indexes['visitantes_empresa_cpf_unique'])
            && $indexes['visitantes_empresa_cpf_unique'] !== $company) {
            throw new \RuntimeException('visitantes: visitantes_empresa_cpf_unique tem definição incompatível; preservado.');
        }

        if (isset($indexes['cpf'])) {
            $dependency = DB::selectOne(
                'SELECT 1 AS found FROM information_schema.KEY_COLUMN_USAGE '
                . 'WHERE (REFERENCED_TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME = ? AND REFERENCED_COLUMN_NAME = ?) '
                . 'OR (TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL) LIMIT 1',
                [DB::getDatabaseName(), 'visitantes', 'cpf', DB::getDatabaseName(), 'visitantes', 'cpf'],
                false
            );
            if ($dependency !== null) {
                throw new \RuntimeException('visitantes.cpf participa de FK; o índice global foi preservado para revisão do relacionamento.');
            }
        }

        // Garante a proteção por empresa ANTES de remover a proteção global.
        if (!in_array($company, $indexes, true)) {
            $duplicate = DB::selectOne(
                'SELECT 1 AS found FROM `visitantes` WHERE `empresa_id` IS NOT NULL AND `cpf` IS NOT NULL '
                . 'GROUP BY `empresa_id`, `cpf` HAVING COUNT(*) > 1 LIMIT 1',
                [],
                false
            );
            if ($duplicate !== null) {
                throw new \RuntimeException('visitantes: CPF duplicado dentro da mesma empresa; nenhum registro foi alterado.');
            }

            Schema::table('visitantes', function (Blueprint $table) {
                $table->unique(['empresa_id', 'cpf'], 'visitantes_empresa_cpf_unique');
            });
        }

        if (isset($indexes['cpf'])) {
            Schema::table('visitantes', function (Blueprint $table) {
                $table->dropUnique('cpf');
            });
        }
    }

    public function down()
    {
        // Migration somente evolutiva. Não restabelece unicidade global:
        // após o up(), empresas diferentes podem ter o mesmo CPF legitimamente.
        return;
    }

    private function indexDefinitions(string $tableName): array
    {
        $rows = DB::select(
            'SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE, SUB_PART FROM information_schema.STATISTICS '
            . 'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY INDEX_NAME, SEQ_IN_INDEX',
            [DB::getDatabaseName(), $tableName],
            false
        );

        $indexes = [];
        foreach ($rows as $row) {
            $indexes[$row->INDEX_NAME]['columns'][] = $row->COLUMN_NAME;
            $indexes[$row->INDEX_NAME]['unique'] = (int) $row->NON_UNIQUE === 0;
            $indexes[$row->INDEX_NAME]['full_columns'] = ($indexes[$row->INDEX_NAME]['full_columns'] ?? true)
                && $row->SUB_PART === null && $row->COLUMN_NAME !== null;
        }
        return $indexes;
    }
};
