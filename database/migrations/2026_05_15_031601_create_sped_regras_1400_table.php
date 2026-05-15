<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sped_regras_1400')) {
            Schema::create('sped_regras_1400', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->autoIncrement();
                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('filial_id')->nullable();
                $table->unsignedInteger('usuario_id')->nullable();
                $table->string('cfop', 4);
                $table->string('codigo_ipm', 50);
                $table->string('descricao', 255)->nullable();
                $table->timestamps();

                $table->index('empresa_id', 'idx_sped_regras_1400_empresa_id');
                $table->index('filial_id', 'idx_sped_regras_1400_filial_id');
                $table->index('usuario_id', 'idx_sped_regras_1400_usuario_id');
                $table->index('cfop', 'sped_regras_1400_cfop_index');
            });
        } else {
            $this->ensureBaseTenantColumns('sped_regras_1400');
            if (!Schema::hasColumn('sped_regras_1400', 'cfop')) {
                Schema::table('sped_regras_1400', function (Blueprint $table) { $table->string('cfop', 4)->after('usuario_id'); });
            }
            if (!Schema::hasColumn('sped_regras_1400', 'codigo_ipm')) {
                Schema::table('sped_regras_1400', function (Blueprint $table) { $table->string('codigo_ipm', 50)->after('cfop'); });
            }
            if (!Schema::hasColumn('sped_regras_1400', 'descricao')) {
                Schema::table('sped_regras_1400', function (Blueprint $table) { $table->string('descricao', 255)->nullable()->after('codigo_ipm'); });
            }
            $this->addIndexIfMissing('sped_regras_1400', 'sped_regras_1400_cfop_index', ['cfop']);
        }

        $this->addForeignIfMissing('sped_regras_1400', 'sped_regras_1400_empresa_id_foreign', 'empresa_id', 'empresas', 'cascade');
        $this->addForeignIfMissing('sped_regras_1400', 'sped_regras_1400_filial_id_foreign', 'filial_id', 'filials', 'cascade');
        $this->addForeignIfMissing('sped_regras_1400', 'sped_regras_1400_usuario_id_foreign', 'usuario_id', 'usuarios', 'cascade');
    }

    public function down()
    {
        if (!Schema::hasTable('sped_regras_1400')) { return; }
        $this->dropForeignIfExists('sped_regras_1400', 'sped_regras_1400_usuario_id_foreign');
        $this->dropForeignIfExists('sped_regras_1400', 'sped_regras_1400_filial_id_foreign');
        $this->dropForeignIfExists('sped_regras_1400', 'sped_regras_1400_empresa_id_foreign');
        Schema::dropIfExists('sped_regras_1400');
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
        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName, $columns) {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (!$this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    private function addForeignIfMissing(string $tableName, string $foreignName, string $column, string $referenceTable, string $onDelete = 'cascade'): void
    {
        if ($this->isSqlite() || $this->foreignExists($tableName, $foreignName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($foreignName, $column, $referenceTable, $onDelete) {
            $table->foreign($column, $foreignName)
                ->references('id')
                ->on($referenceTable)
                ->onDelete($onDelete);
        });
    }

    private function dropForeignIfExists(string $tableName, string $foreignName): void
    {
        if ($this->isSqlite() || !$this->foreignExists($tableName, $foreignName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($foreignName) {
            $table->dropForeign($foreignName);
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
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
        if ($this->isSqlite()) {
            return false;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('CONSTRAINT_NAME', $foreignName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

};
