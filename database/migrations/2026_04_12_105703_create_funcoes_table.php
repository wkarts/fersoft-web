<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateFuncoesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('funcoes')) {
            Schema::create('funcoes', function (Blueprint $table) {
                $table->increments('id');

                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('usuario_id');
                $table->unsignedInteger('filial_id');

                $table->string('nome', 100);

                $table->timestamps();

                $table->index('empresa_id', 'idx_funcoes_empresa_id');
                $table->index('usuario_id', 'idx_funcoes_usuario_id');
                $table->index('filial_id', 'idx_funcoes_filial_id');
                $table->unique(['empresa_id', 'filial_id', 'nome'], 'uq_funcoes_empresa_filial_nome');

                $table->foreign('empresa_id', 'funcoes_empresa_id_foreign')
                    ->references('id')->on('empresas')->onDelete('cascade');

                $table->foreign('usuario_id', 'funcoes_usuario_id_foreign')
                    ->references('id')->on('usuarios')->onDelete('cascade');

                $table->foreign('filial_id', 'funcoes_filial_id_foreign')
                    ->references('id')->on('filials')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable('funcoes')) {
            return;
        }

        $this->dropForeignIfExists('funcoes', 'funcoes_filial_id_foreign');
        $this->dropForeignIfExists('funcoes', 'funcoes_usuario_id_foreign');
        $this->dropForeignIfExists('funcoes', 'funcoes_empresa_id_foreign');

        $this->dropIndexIfExists('funcoes', 'uq_funcoes_empresa_filial_nome');
        $this->dropIndexIfExists('funcoes', 'idx_funcoes_filial_id');
        $this->dropIndexIfExists('funcoes', 'idx_funcoes_usuario_id');
        $this->dropIndexIfExists('funcoes', 'idx_funcoes_empresa_id');

        Schema::dropIfExists('funcoes');
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $database = DB::getDatabaseName();

        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();

        if ($exists) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
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
