<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MergeContaPagarsTable extends Migration
{
    public function up()
    {
        $this->ensureMergeAuditTable();

        if (!Schema::hasTable('conta_pagars')) {
            return;
        }

        $addVeiculoId = !Schema::hasColumn('conta_pagars', 'veiculo_id');
        $addUsuarioId = !Schema::hasColumn('conta_pagars', 'usuario_id');
        $addUsuarioBaixaId = !Schema::hasColumn('conta_pagars', 'usuario_baixa_id');
        $addUsuarioEdicaoId = !Schema::hasColumn('conta_pagars', 'usuario_edicao_id');
        $addDesconto = !Schema::hasColumn('conta_pagars', 'desconto');

        Schema::table('conta_pagars', function (Blueprint $table) use (
            $addVeiculoId,
            $addUsuarioId,
            $addUsuarioBaixaId,
            $addUsuarioEdicaoId,
            $addDesconto
        ) {
            if ($addVeiculoId) {
                $table->unsignedInteger('veiculo_id')->nullable();
            }

            if ($addUsuarioId) {
                $table->integer('usuario_id')->nullable();
            }

            if ($addUsuarioBaixaId) {
                $table->integer('usuario_baixa_id')->nullable();
            }

            if ($addUsuarioEdicaoId) {
                $table->integer('usuario_edicao_id')->nullable();
            }

            if ($addDesconto) {
                $table->decimal('desconto', 10, 2)->nullable()->default(0.00);
            }
        });

        if ($addVeiculoId) {
            $this->markCreated('column', 'conta_pagars.veiculo_id');
        }

        if ($addUsuarioId) {
            $this->markCreated('column', 'conta_pagars.usuario_id');
        }

        if ($addUsuarioBaixaId) {
            $this->markCreated('column', 'conta_pagars.usuario_baixa_id');
        }

        if ($addUsuarioEdicaoId) {
            $this->markCreated('column', 'conta_pagars.usuario_edicao_id');
        }

        if ($addDesconto) {
            $this->markCreated('column', 'conta_pagars.desconto');
        }

        if (
            $addVeiculoId &&
            Schema::hasColumn('conta_pagars', 'veiculo_id') &&
            Schema::hasTable('veiculos') &&
            Schema::hasColumn('veiculos', 'id')
        ) {
            if ($this->addForeignIfNotExists(
                'conta_pagars',
                'conta_pagars_veiculo_id_foreign',
                'veiculo_id',
                'veiculos',
                'id',
                'set null'
            )) {
                $this->markCreated('foreign', 'conta_pagars.conta_pagars_veiculo_id_foreign');
            }
        }

        if (
            Schema::hasColumn('conta_pagars', 'compra_id') &&
            Schema::hasTable('compras') &&
            Schema::hasColumn('compras', 'id')
        ) {
            if ($this->addForeignIfNotExists(
                'conta_pagars',
                'conta_pagars_compra_id_foreign',
                'compra_id',
                'compras',
                'id',
                'cascade'
            )) {
                $this->markCreated('foreign', 'conta_pagars.conta_pagars_compra_id_foreign');
            }
        }
    }

    public function down()
    {
        $this->ensureMergeAuditTable();

        if (!Schema::hasTable('conta_pagars')) {
            return;
        }

        if ($this->wasCreated('foreign', 'conta_pagars.conta_pagars_compra_id_foreign')) {
            $this->dropForeignIfExists('conta_pagars', 'conta_pagars_compra_id_foreign');
            $this->forgetCreated('foreign', 'conta_pagars.conta_pagars_compra_id_foreign');
        }

        if ($this->wasCreated('foreign', 'conta_pagars.conta_pagars_veiculo_id_foreign')) {
            $this->dropForeignIfExists('conta_pagars', 'conta_pagars_veiculo_id_foreign');
            $this->forgetCreated('foreign', 'conta_pagars.conta_pagars_veiculo_id_foreign');
        }

        $columnsToDrop = [];

        if (
            $this->wasCreated('column', 'conta_pagars.desconto') &&
            Schema::hasColumn('conta_pagars', 'desconto')
        ) {
            $columnsToDrop[] = 'desconto';
        }

        if (
            $this->wasCreated('column', 'conta_pagars.usuario_edicao_id') &&
            Schema::hasColumn('conta_pagars', 'usuario_edicao_id')
        ) {
            $columnsToDrop[] = 'usuario_edicao_id';
        }

        if (
            $this->wasCreated('column', 'conta_pagars.usuario_baixa_id') &&
            Schema::hasColumn('conta_pagars', 'usuario_baixa_id')
        ) {
            $columnsToDrop[] = 'usuario_baixa_id';
        }

        if (
            $this->wasCreated('column', 'conta_pagars.usuario_id') &&
            Schema::hasColumn('conta_pagars', 'usuario_id')
        ) {
            $columnsToDrop[] = 'usuario_id';
        }

        if (
            $this->wasCreated('column', 'conta_pagars.veiculo_id') &&
            Schema::hasColumn('conta_pagars', 'veiculo_id')
        ) {
            $columnsToDrop[] = 'veiculo_id';
        }

        if (!empty($columnsToDrop)) {
            Schema::table('conta_pagars', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }

        foreach ([
                     'conta_pagars.desconto',
                     'conta_pagars.usuario_edicao_id',
                     'conta_pagars.usuario_baixa_id',
                     'conta_pagars.usuario_id',
                     'conta_pagars.veiculo_id',
                 ] as $item) {
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
        if ($this->isSqlite()) {
            return false;
        }

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
        if ($this->isSqlite()) {
            return;
        }

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

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

}
