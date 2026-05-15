<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sped_configs')) { return; }

        $this->ensureBaseTenantColumns('sped_configs');

        Schema::table('sped_configs', function (Blueprint $table) {

            if (!Schema::hasColumn('sped_configs', 'filial_id')) { $table->unsignedInteger('filial_id')->nullable()->after('empresa_id'); }
            if (!Schema::hasColumn('sped_configs', 'perfil')) { $table->enum('perfil', ['A', 'B', 'C'])->default('B')->after('codigo_receita'); }
            if (!Schema::hasColumn('sped_configs', 'ind_ativ')) { $table->integer('ind_ativ')->default(1)->after('perfil'); }

        });

    }

    public function down()
    {
        if (!Schema::hasTable('sped_configs')) { return; }
        Schema::table('sped_configs', function (Blueprint $table) {
                if (Schema::hasColumn('sped_configs', 'ind_ativ')) { $table->dropColumn('ind_ativ'); }
                if (Schema::hasColumn('sped_configs', 'perfil')) { $table->dropColumn('perfil'); }
                if (Schema::hasColumn('sped_configs', 'filial_id')) { $table->dropColumn('filial_id'); }
        });
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
