<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAbastecimentosMovimentacoesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('abastecimentos_movimentacoes')) {
            Schema::create('abastecimentos_movimentacoes', function (Blueprint $table) {
                $table->increments('id');

                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('usuario_id');
                $table->unsignedInteger('filial_id');

                // Compatível com movimentacoes_veiculos.id da base original
                $table->unsignedBigInteger('movimentacao_id');
                $table->unsignedInteger('produto_id');

                $table->enum('tipo', ['diesel', 'arla']);
                $table->decimal('quantidade', 10, 3);
                $table->decimal('valor_unitario', 10, 3);
                $table->decimal('valor_total', 10, 2);
                $table->date('data_abastecimento');
                $table->integer('km_abastecimento')->nullable();

                $table->timestamps();

                $table->index('empresa_id', 'idx_abast_mov_empresa_id');
                $table->index('usuario_id', 'idx_abast_mov_usuario_id');
                $table->index('filial_id', 'idx_abast_mov_filial_id');
                $table->index('movimentacao_id', 'idx_abast_mov_movimentacao_id');
                $table->index('produto_id', 'idx_abast_mov_produto_id');

                $table->foreign('empresa_id', 'abast_mov_empresa_id_foreign')
                    ->references('id')->on('empresas')->onDelete('cascade');

                $table->foreign('usuario_id', 'abast_mov_usuario_id_foreign')
                    ->references('id')->on('usuarios')->onDelete('cascade');

                $table->foreign('filial_id', 'abast_mov_filial_id_foreign')
                    ->references('id')->on('filials')->onDelete('cascade');

                $table->foreign('movimentacao_id', 'abastecimento_movimentacao_id_foreign')
                    ->references('id')->on('movimentacoes_veiculos')->onDelete('cascade');

                $table->foreign('produto_id', 'abastecimento_produto_id_foreign')
                    ->references('id')->on('produtos')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable('abastecimentos_movimentacoes')) {
            return;
        }

        $this->dropForeignIfExists('abastecimentos_movimentacoes', 'abastecimento_produto_id_foreign');
        $this->dropForeignIfExists('abastecimentos_movimentacoes', 'abastecimento_movimentacao_id_foreign');
        $this->dropForeignIfExists('abastecimentos_movimentacoes', 'abast_mov_filial_id_foreign');
        $this->dropForeignIfExists('abastecimentos_movimentacoes', 'abast_mov_usuario_id_foreign');
        $this->dropForeignIfExists('abastecimentos_movimentacoes', 'abast_mov_empresa_id_foreign');

        $this->dropIndexIfExists('abastecimentos_movimentacoes', 'idx_abast_mov_produto_id');
        $this->dropIndexIfExists('abastecimentos_movimentacoes', 'idx_abast_mov_movimentacao_id');
        $this->dropIndexIfExists('abastecimentos_movimentacoes', 'idx_abast_mov_filial_id');
        $this->dropIndexIfExists('abastecimentos_movimentacoes', 'idx_abast_mov_usuario_id');
        $this->dropIndexIfExists('abastecimentos_movimentacoes', 'idx_abast_mov_empresa_id');

        Schema::dropIfExists('abastecimentos_movimentacoes');
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

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->isSqlite()) {
            return;
        }

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

    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

}
