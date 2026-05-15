<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pesagem_pagamentos')) {
            Schema::create('pesagem_pagamentos', function (Blueprint $table) {

                $table->unsignedBigInteger('id')->autoIncrement();
                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('filial_id')->nullable();
                $table->unsignedInteger('usuario_id')->nullable();
                $table->unsignedBigInteger('pesagem_id')->comment('ID da pesagem na tabela pesagens');
                $table->decimal('valor_pago', 15, 2)->nullable();
                $table->date('data_pagamento')->nullable();
                $table->string('forma_pagamento', 50)->nullable()->comment('PIX, Dinheiro, Transferência, etc.');
                $table->text('observacao')->nullable();
                $table->timestamps();
                $table->index('empresa_id', 'idx_pesagem_pagamentos_empresa_id');
                $table->index('filial_id', 'idx_pesagem_pagamentos_filial_id');
                $table->index('usuario_id', 'idx_pesagem_pagamentos_usuario_id');
                $table->index('pesagem_id', 'idx_pesagem_pagamentos_pesagem_id');
            });
        } else {
            $this->ensureBaseTenantColumns('pesagem_pagamentos');
        }

        $this->addForeignIfMissing('pesagem_pagamentos', 'pesagem_pagamentos_empresa_id_foreign', 'empresa_id', 'empresas', 'cascade');
        $this->addForeignIfMissing('pesagem_pagamentos', 'pesagem_pagamentos_filial_id_foreign', 'filial_id', 'filials', 'cascade');
        $this->addForeignIfMissing('pesagem_pagamentos', 'pesagem_pagamentos_usuario_id_foreign', 'usuario_id', 'usuarios', 'cascade');
    }

    public function down()
    {
        if (!Schema::hasTable('pesagem_pagamentos')) { return; }
        $this->dropForeignIfExists('pesagem_pagamentos', 'pesagem_pagamentos_usuario_id_foreign');
        $this->dropForeignIfExists('pesagem_pagamentos', 'pesagem_pagamentos_filial_id_foreign');
        $this->dropForeignIfExists('pesagem_pagamentos', 'pesagem_pagamentos_empresa_id_foreign');
        Schema::dropIfExists('pesagem_pagamentos');
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
