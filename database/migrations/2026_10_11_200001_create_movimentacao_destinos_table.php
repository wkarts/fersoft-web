<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('movimentacao_destinos')) {
            foreach (['id', 'movimentacao_veiculo_id', 'cliente_id', 'fornecedor_id', 'ordem', 'endereco_completo', 'latitude', 'longitude', 'data_hora_chegada', 'data_hora_saida', 'status', 'created_at', 'updated_at'] as $column) {
                if (!Schema::hasColumn('movimentacao_destinos', $column)) {
                    throw new \RuntimeException('A tabela movimentacao_destinos já existe, porém está incompleta: ' . $column);
                }
            }
            return;
        }
        if (!Schema::hasTable('movimentacoes_veiculos')) {
            throw new \RuntimeException('Tabela referenciada ausente: movimentacoes_veiculos');
        }
        Schema::create('movimentacao_destinos', function (Blueprint $table) {
            $table->bigIncrements('id');
            // BIGINT UNSIGNED: igual a movimentacoes_veiculos.id no ARGWS.
            $table->unsignedBigInteger('movimentacao_veiculo_id');
            // INT assinado conforme dump Metal. Sem FK nestes dois campos no dump.
            $table->integer('cliente_id')->nullable();
            $table->integer('fornecedor_id')->nullable();
            $table->integer('ordem')->default(1);
            $table->string('endereco_completo', 255)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->dateTime('data_hora_chegada')->nullable();
            $table->dateTime('data_hora_saida')->nullable();
            $table->enum('status', ['pendente', 'visitado', 'cancelado'])->default('pendente');
            $table->timestamps();

            $table->index('movimentacao_veiculo_id', 'fk_mov_dest_movimento');
            $table->foreign('movimentacao_veiculo_id', 'fk_mov_dest_movimento')
                ->references('id')->on('movimentacoes_veiculos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Rollback conservador: nenhum DROP e nenhuma remoção de dados ou de campos.
        // Os objetos acrescentados permanecem disponíveis após migrate:rollback.
    }
};
