<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('apuracao_difal_itens')) {
            Schema::create('apuracao_difal_itens', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->autoIncrement();
                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('filial_id')->nullable();
                $table->unsignedInteger('usuario_id')->nullable();
                $table->unsignedBigInteger('apuracao_difal_id')->nullable();
                $table->unsignedInteger('nota_fiscal_id')->nullable();
                $table->string('numero_nota', 20)->nullable();
                $table->string('emitente_nome', 150)->nullable();
                $table->decimal('valor_operacao', 15, 4)->nullable()->default(0);
                $table->decimal('aliquota_origem', 5, 2)->nullable()->default(0);
                $table->decimal('aliquota_destino', 5, 2)->nullable()->default(0);
                $table->decimal('base_calculo_dupla', 15, 4)->nullable()->default(0);
                $table->decimal('valor_difal', 15, 4)->nullable()->default(0);
                $table->timestamps();

                $table->index('empresa_id', 'idx_apuracao_difal_itens_empresa_id');
                $table->index('filial_id', 'idx_apuracao_difal_itens_filial_id');
                $table->index('usuario_id', 'idx_apuracao_difal_itens_usuario_id');
                $table->index('apuracao_difal_id', 'idx_apuracao_difal_itens_apuracao_id');
                $table->index('nota_fiscal_id', 'idx_apuracao_difal_itens_nota_fiscal_id');
            });
        } else {
            $this->ensureBaseTenantColumns('apuracao_difal_itens');
        }

        $this->addForeignIfMissing('apuracao_difal_itens', 'apuracao_difal_itens_empresa_id_foreign', 'empresa_id', 'empresas', 'cascade');
        $this->addForeignIfMissing('apuracao_difal_itens', 'apuracao_difal_itens_filial_id_foreign', 'filial_id', 'filials', 'set null');
        $this->addForeignIfMissing('apuracao_difal_itens', 'apuracao_difal_itens_usuario_id_foreign', 'usuario_id', 'usuarios', 'set null');
        $this->addForeignIfMissing('apuracao_difal_itens', 'apuracao_difal_itens_apuracao_id_foreign', 'apuracao_difal_id', 'apuracoes_difal', 'cascade');
    }

    public function down()
    {
        if (!Schema::hasTable('apuracao_difal_itens')) { return; }
        $this->dropForeignIfExists('apuracao_difal_itens', 'apuracao_difal_itens_apuracao_id_foreign');
        $this->dropForeignIfExists('apuracao_difal_itens', 'apuracao_difal_itens_usuario_id_foreign');
        $this->dropForeignIfExists('apuracao_difal_itens', 'apuracao_difal_itens_filial_id_foreign');
        $this->dropForeignIfExists('apuracao_difal_itens', 'apuracao_difal_itens_empresa_id_foreign');
        Schema::dropIfExists('apuracao_difal_itens');
    }


    private function ensureBaseTenantColumns(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (!Schema::hasColumn($tableName, 'empresa_id')) {
                $table->unsignedInteger('empresa_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn($tableName, 'filial_id')) {
                $table->unsignedInteger('filial_id')->nullable()->after('empresa_id');
            }
            if (!Schema::hasColumn($tableName, 'usuario_id')) {
                $table->unsignedInteger('usuario_id')->nullable()->after('filial_id');
            }
        });

        $this->addIndexIfMissing($tableName, 'idx_' . $tableName . '_empresa_id', ['empresa_id']);
        $this->addIndexIfMissing($tableName, 'idx_' . $tableName . '_filial_id', ['filial_id']);
        $this->addIndexIfMissing($tableName, 'idx_' . $tableName . '_usuario_id', ['usuario_id']);
    }

    private function addIndexIfMissing(string $tableName, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($tableName) || $this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName, $columns) {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (!Schema::hasTable($tableName) || !$this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    private function addForeignIfMissing(string $tableName, string $foreignName, string $column, string $referenceTable, string $onDelete = 'cascade'): void
    {
        if ($this->isSqlite() || !Schema::hasTable($tableName) || !Schema::hasTable($referenceTable) || !Schema::hasColumn($tableName, $column) || $this->foreignExists($tableName, $foreignName)) {
            return;
        }

        $this->normalizeForeignIntegerColumn($tableName, $column, $referenceTable);

        Schema::table($tableName, function (Blueprint $table) use ($foreignName, $column, $referenceTable, $onDelete) {
            $table->foreign($column, $foreignName)
                ->references('id')
                ->on($referenceTable)
                ->onDelete($onDelete);
        });
    }

    private function normalizeForeignIntegerColumn(string $tableName, string $column, string $referenceTable): void
    {
        if (!$this->isMysql() || !Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $column) || !Schema::hasTable($referenceTable)) {
            return;
        }

        $databaseName = DB::getDatabaseName();

        $localColumn = DB::selectOne(
            'SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$databaseName, $tableName, $column]
        );

        $referencedColumn = DB::selectOne(
            'SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$databaseName, $referenceTable, 'id']
        );

        if (!$localColumn || !$referencedColumn) {
            return;
        }

        $localType = strtolower((string) ($localColumn->COLUMN_TYPE ?? $localColumn->column_type ?? ''));
        $referencedType = strtolower((string) ($referencedColumn->COLUMN_TYPE ?? $referencedColumn->column_type ?? ''));

        if ($referencedType === '' || $localType === $referencedType) {
            return;
        }

        $allowedReferenceTypes = [
            'tinyint unsigned',
            'smallint unsigned',
            'mediumint unsigned',
            'int unsigned',
            'bigint unsigned',
        ];

        if (!in_array($referencedType, $allowedReferenceTypes, true)) {
            return;
        }

        $nullable = strtoupper((string) ($localColumn->IS_NULLABLE ?? $localColumn->is_nullable ?? 'NO')) === 'YES'
            ? 'NULL'
            : 'NOT NULL';

        DB::statement(sprintf(
            'ALTER TABLE %s MODIFY %s %s %s',
            $this->quoteIdentifier($tableName),
            $this->quoteIdentifier($column),
            strtoupper($referencedType),
            $nullable
        ));
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function dropForeignIfExists(string $tableName, string $foreignName): void
    {
        if ($this->isSqlite() || !Schema::hasTable($tableName) || !$this->foreignExists($tableName, $foreignName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($foreignName) {
            $table->dropForeign($foreignName);
        });
    }

    private function dropColumnIfExists(string $tableName, string $columnName): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columnName) {
            $table->dropColumn($columnName);
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        if (!Schema::hasTable($tableName)) {
            return false;
        }

        if ($this->isSqlite()) {
            $safeTableName = str_replace("'", "''", $tableName);
            $indexes = DB::select("PRAGMA index_list('" . $safeTableName . "')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }

    private function foreignExists(string $tableName, string $foreignName): bool
    {
        if ($this->isSqlite() || !Schema::hasTable($tableName)) {
            return false;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('CONSTRAINT_NAME', $foreignName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function isMysql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

};
