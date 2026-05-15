<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tarefas')) {
            Schema::create('tarefas', function (Blueprint $table) {

                $table->unsignedBigInteger('id')->autoIncrement();
                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('filial_id')->nullable();
                $table->unsignedInteger('usuario_id')->nullable();
                $table->integer('user_id')->nullable();
                $table->integer('funcionario_id');
                $table->string('titulo', 255);
                $table->text('descricao')->nullable();
                $table->date('data');
                $table->time('hora_estimada')->nullable();
                $table->date('data_limite')->nullable();
                $table->time('hora_limite')->nullable();
                $table->string('prioridade', 20)->default('Normal');
                $table->string('status', 20)->default('pendente')->comment('pendente, em_andamento, concluida');
                $table->dateTime('iniciado_em')->nullable();
                $table->dateTime('finalizado_em')->nullable();
                $table->integer('tempo_gasto_minutos')->default(0);
                $table->text('justificativa_atraso')->nullable();
                $table->boolean('aviso_supervisor_enviado')->default(false);
                $table->boolean('is_recorrente')->default(false);
                $table->string('frequencia', 20)->nullable()->comment('diario, semanal, mensal');
                $table->timestamps();
                $table->index('empresa_id', 'idx_tarefas_empresa_id');
                $table->index('filial_id', 'idx_tarefas_filial_id');
                $table->index('usuario_id', 'idx_tarefas_usuario_id');
                $table->index('funcionario_id', 'idx_tarefas_funcionario_id');
                $table->index('status', 'idx_tarefas_status');
                $table->index('data', 'idx_tarefas_data');
            });
        } else {
            $this->ensureBaseTenantColumns('tarefas');
        }

        $this->addForeignIfMissing('tarefas', 'tarefas_empresa_id_foreign', 'empresa_id', 'empresas', 'cascade');
        $this->addForeignIfMissing('tarefas', 'tarefas_filial_id_foreign', 'filial_id', 'filials', 'cascade');
        $this->addForeignIfMissing('tarefas', 'tarefas_usuario_id_foreign', 'usuario_id', 'usuarios', 'cascade');
    }

    public function down()
    {
        if (!Schema::hasTable('tarefas')) { return; }
        $this->dropForeignIfExists('tarefas', 'tarefas_usuario_id_foreign');
        $this->dropForeignIfExists('tarefas', 'tarefas_filial_id_foreign');
        $this->dropForeignIfExists('tarefas', 'tarefas_empresa_id_foreign');
        Schema::dropIfExists('tarefas');
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
