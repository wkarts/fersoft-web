<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateDespesasMovimentacoesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('despesas_movimentacoes')) {
            Schema::create('despesas_movimentacoes', function (Blueprint $table) {
                $table->increments('id');

                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('usuario_id');
                $table->unsignedInteger('filial_id');

                // Compatível com movimentacoes_veiculos.id da base original (BIGINT UNSIGNED)
                $table->unsignedBigInteger('movimentacao_id')->nullable();

                $table->string('tipo', 50)->nullable();
                $table->decimal('valor', 10, 2)->nullable();
                $table->string('descricao', 255)->nullable();

                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->index('empresa_id', 'idx_despesas_mov_empresa_id');
                $table->index('usuario_id', 'idx_despesas_mov_usuario_id');
                $table->index('filial_id', 'idx_despesas_mov_filial_id');
                $table->index('movimentacao_id', 'idx_despesas_mov_movimentacao_id');

                $table->foreign('empresa_id', 'despesas_mov_empresa_id_foreign')
                    ->references('id')
                    ->on('empresas')
                    ->onDelete('cascade');

                $table->foreign('usuario_id', 'despesas_mov_usuario_id_foreign')
                    ->references('id')
                    ->on('usuarios')
                    ->onDelete('cascade');

                $table->foreign('filial_id', 'despesas_mov_filial_id_foreign')
                    ->references('id')
                    ->on('filials')
                    ->onDelete('cascade');

                $table->foreign('movimentacao_id', 'despesas_mov_movimentacao_fk')
                    ->references('id')
                    ->on('movimentacoes_veiculos')
                    ->onDelete('set null');
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable('despesas_movimentacoes')) {
            return;
        }

        $this->dropForeignIfExists('despesas_movimentacoes', 'despesas_mov_movimentacao_fk');
        $this->dropForeignIfExists('despesas_movimentacoes', 'despesas_mov_filial_id_foreign');
        $this->dropForeignIfExists('despesas_movimentacoes', 'despesas_mov_usuario_id_foreign');
        $this->dropForeignIfExists('despesas_movimentacoes', 'despesas_mov_empresa_id_foreign');

        Schema::table('despesas_movimentacoes', function (Blueprint $table) {
            $indexes = [
                'idx_despesas_mov_movimentacao_id',
                'idx_despesas_mov_filial_id',
                'idx_despesas_mov_usuario_id',
                'idx_despesas_mov_empresa_id',
            ];

            foreach ($indexes as $indexName) {
                try {
                    $table->dropIndex($indexName);
                } catch (\Throwable $e) {
                }
            }
        });

        Schema::dropIfExists('despesas_movimentacoes');
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
