<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeComprasTable extends Migration
{
    public function up()
    {
        $this->ensureMergeAuditTable();

        if (!Schema::hasTable('compras')) {
            return;
        }

        $addVeiculoId = !Schema::hasColumn('compras', 'veiculo_id');
        $addTotalIbsCbs = !Schema::hasColumn('compras', 'total_ibs_cbs');

        Schema::table('compras', function (Blueprint $table) use ($addVeiculoId, $addTotalIbsCbs) {
            if ($addVeiculoId) {
                $table->unsignedInteger('veiculo_id')->nullable();
            }

            if ($addTotalIbsCbs) {
                $table->decimal('total_ibs_cbs', 15, 2)->nullable();
            }
        });

        if ($addVeiculoId) {
            $this->markCreated('column', 'compras.veiculo_id');
        }

        if ($addTotalIbsCbs) {
            $this->markCreated('column', 'compras.total_ibs_cbs');
        }

        if (
            $addVeiculoId &&
            Schema::hasColumn('compras', 'veiculo_id') &&
            Schema::hasTable('veiculos') &&
            Schema::hasColumn('veiculos', 'id')
        ) {
            if ($this->addForeignIfNotExists(
                'compras',
                'compras_veiculo_id_foreign',
                'veiculo_id',
                'veiculos',
                'id',
                'set null'
            )) {
                $this->markCreated('foreign', 'compras.compras_veiculo_id_foreign');
            }
        }
    }

    public function down()
    {
        $this->ensureMergeAuditTable();

        if (!Schema::hasTable('compras')) {
            return;
        }

        if ($this->wasCreated('foreign', 'compras.compras_veiculo_id_foreign')) {
            $this->dropForeignIfExists('compras', 'compras_veiculo_id_foreign');
            $this->forgetCreated('foreign', 'compras.compras_veiculo_id_foreign');
        }

        $columnsToDrop = [];

        if (
            $this->wasCreated('column', 'compras.total_ibs_cbs') &&
            Schema::hasColumn('compras', 'total_ibs_cbs')
        ) {
            $columnsToDrop[] = 'total_ibs_cbs';
        }

        if (
            $this->wasCreated('column', 'compras.veiculo_id') &&
            Schema::hasColumn('compras', 'veiculo_id')
        ) {
            $columnsToDrop[] = 'veiculo_id';
        }

        if (!empty($columnsToDrop)) {
            Schema::table('compras', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }

        foreach (['compras.total_ibs_cbs', 'compras.veiculo_id'] as $item) {
            $this->forgetCreated('column', $item);
        }
    }

    private function ensureMergeAuditTable(): void
    {
        if (!Schema::hasTable('migration_merge_audit')) {
            Schema::create('migration_merge_audit', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('migration_name', 191);
                $table->string('object_type', 50);
                $table->string('object_name', 191);
                $table->timestamps();
                $table->unique(['migration_name', 'object_type', 'object_name'], 'uq_migration_merge_audit');
            });
        }
    }

    private function markCreated(string $type, string $name): void
    {
        DB::table('migration_merge_audit')->updateOrInsert(
            [
                'migration_name' => static::class,
                'object_type' => $type,
                'object_name' => $name,
            ],
            [
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function wasCreated(string $type, string $name): bool
    {
        return DB::table('migration_merge_audit')
            ->where('migration_name', static::class)
            ->where('object_type', $type)
            ->where('object_name', $name)
            ->exists();
    }

    private function forgetCreated(string $type, string $name): void
    {
        DB::table('migration_merge_audit')
            ->where('migration_name', static::class)
            ->where('object_type', $type)
            ->where('object_name', $name)
            ->delete();
    }

    private function addForeignIfNotExists(
        string $table,
        string $foreignName,
        string $column,
        string $referencesTable,
        string $referencesColumn = 'id',
        string $onDelete = 'cascade'
    ): bool {
        $database = DB::getDatabaseName();

        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists || !Schema::hasColumn($table, $column)) {
            return false;
        }

        Schema::table($table, function (Blueprint $table) use (
            $foreignName,
            $column,
            $referencesTable,
            $referencesColumn,
            $onDelete
        ) {
            $table->foreign($column, $foreignName)
                ->references($referencesColumn)
                ->on($referencesTable)
                ->onDelete($onDelete);
        });

        return true;
    }

    private function dropForeignIfExists(string $table, string $foreignName): void
    {
        $database = DB::getDatabaseName();

        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if ($exists) {
            Schema::table($table, function (Blueprint $table) use ($foreignName) {
                $table->dropForeign($foreignName);
            });
        }
    }
}
