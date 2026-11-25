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

        if (!Schema::hasTable('apontamentos')) {
            throw new \RuntimeException('Tabela esperada ausente: apontamentos');
        }

        $this->ensureColumn('apontamentos', 'producao_lote_id', function (Blueprint $table) {
            $table->integer('producao_lote_id')->nullable();
        });

        $this->ensureColumn('apontamentos', 'data_apontamento', function (Blueprint $table) {
            $table->dateTime('data_apontamento')->nullable();
        });

        $this->addForeignIfMissing('apontamentos', 'fk_apontamentos_filial', ['filial_id'], 'filials', ['id'], 'set null', null);
    }

    public function down()
    {
        // Migration somente evolutiva.
        return;
    }

    protected function ensureColumn(string $tableName, string $columnName, \Closure $definition): void
    {
        if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($definition) {
            $definition($table);
        });
    }

    private function addForeignIfMissing(string $tableName, string $foreignName, array $columns, string $referenceTable, array $referenceColumns, ?string $onDelete = null, ?string $onUpdate = null): void
    {
        $column = $columns[0];
        $referenceColumn = $referenceColumns[0];
        $onDelete = strtoupper($onDelete ?? 'RESTRICT');
        $onUpdate = strtoupper($onUpdate ?? 'RESTRICT');
        $rows = DB::select(
            'SELECT k.CONSTRAINT_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME, r.DELETE_RULE, r.UPDATE_RULE '
            . 'FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r '
            . 'ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.TABLE_NAME = k.TABLE_NAME AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME '
            . 'WHERE k.TABLE_SCHEMA = ? AND k.TABLE_NAME = ? AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY k.CONSTRAINT_NAME, k.ORDINAL_POSITION',
            [DB::getDatabaseName(), $tableName]
        );
        $foreigns = [];
        foreach ($rows as $row) {
            $foreigns[$row->CONSTRAINT_NAME][] = [
                $row->COLUMN_NAME, $row->REFERENCED_TABLE_NAME, $row->REFERENCED_COLUMN_NAME,
                $row->DELETE_RULE === 'NO ACTION' ? 'RESTRICT' : $row->DELETE_RULE,
                $row->UPDATE_RULE === 'NO ACTION' ? 'RESTRICT' : $row->UPDATE_RULE,
            ];
        }
        $expected = [[$column, $referenceTable, $referenceColumn, $onDelete, $onUpdate]];
        if (isset($foreigns[$foreignName])) {
            if ($foreigns[$foreignName] !== $expected) {
                throw new \RuntimeException($tableName . ": FK {$foreignName} existente com outra definição; preservada.");
            }
            return;
        }
        if (in_array($expected, $foreigns, true)) {
            return;
        }
        if (!Schema::hasTable($referenceTable)) {
            throw new \RuntimeException("Tabela referenciada ausente: {$referenceTable}");
        }
        $metadata = static fn ($table, $field) => DB::selectOne(
            'SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT, EXTRA, COLUMN_KEY FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [DB::getDatabaseName(), $table, $field],
            false
        );
        $local = $metadata($tableName, $column);
        $parent = $metadata($referenceTable, $referenceColumn);
        $integerType = static fn ($type) => preg_replace('/\(\d+\)/', '', strtolower(trim($type)));
        if ($local === null || $parent === null || $integerType($local->COLUMN_TYPE) !== $integerType($parent->COLUMN_TYPE)) {
            throw new \RuntimeException($tableName . ": tipos incompatíveis para FK {$foreignName}; nenhuma conversão implícita.");
        }
        if (DB::selectOne('SELECT 1 AS found FROM `' . $tableName . '` c LEFT JOIN `' . $referenceTable
            . '` p ON p.`' . $referenceColumn . '` = c.`' . $column . '` WHERE c.`' . $column
            . '` IS NOT NULL AND p.`' . $referenceColumn . '` IS NULL LIMIT 1') !== null) {
            throw new \RuntimeException($tableName . ": registros órfãos impedem a FK {$foreignName}; nenhum registro foi alterado.");
        }
        // SET NULL exige coluna nullable, inclusive em tabelas já existentes.
        if (($onDelete === 'SET NULL' || $onUpdate === 'SET NULL') && $local->IS_NULLABLE !== 'YES') {
            if (!preg_match('/^(?:tinyint|smallint|mediumint|int|bigint)(?:\(\d+\))?(?: unsigned)?$/i', trim($local->COLUMN_TYPE))
                || trim((string) $local->EXTRA) !== '' || $local->COLUMN_KEY === 'PRI') {
                throw new \RuntimeException($tableName . ": coluna {$column} exige revisão antes de permitir NULL.");
            }
            $quote = static function ($value): string {
                $quoted = DB::connection()->getPdo()->quote((string) $value);
                if ($quoted === false) {
                    throw new \RuntimeException('Não foi possível preparar atributo da coluna da FK.');
                }
                return $quoted;
            };
            $default = $local->COLUMN_DEFAULT === null ? 'NULL' : $quote($local->COLUMN_DEFAULT);
            DB::statement('ALTER TABLE `' . $tableName . '` MODIFY COLUMN `' . $column . '` '
                . $local->COLUMN_TYPE . ' NULL DEFAULT ' . $default
                . ' COMMENT ' . $quote($local->COLUMN_COMMENT));
        }
        Schema::table($tableName, function (Blueprint $table) use ($foreignName, $columns, $referenceTable, $referenceColumns, $onDelete, $onUpdate) {
            $table->foreign($columns, $foreignName)
                ->references($referenceColumns)->on($referenceTable)
                ->onDelete(strtolower($onDelete))->onUpdate(strtolower($onUpdate));
        });
    }

};
