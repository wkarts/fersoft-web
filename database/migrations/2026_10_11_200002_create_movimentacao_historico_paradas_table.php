<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('movimentacao_historico_paradas')) {
            foreach (['id', 'movimentacao_veiculo_id', 'data_hora_inicio', 'data_hora_fim', 'duracao_minutos', 'endereco', 'latitude', 'longitude', 'created_at', 'updated_at'] as $column) {
                if (!Schema::hasColumn('movimentacao_historico_paradas', $column)) {
                    throw new \RuntimeException('A tabela movimentacao_historico_paradas já existe, porém está incompleta: ' . $column);
                }
            }
            return;
        }
        if (!Schema::hasTable('movimentacoes_veiculos')) {
            throw new \RuntimeException('Tabela referenciada ausente: movimentacoes_veiculos');
        }
        Schema::create('movimentacao_historico_paradas', function (Blueprint $table) {
            $table->bigIncrements('id');
            // BIGINT UNSIGNED: igual a movimentacoes_veiculos.id no ARGWS.
            $table->unsignedBigInteger('movimentacao_veiculo_id');
            $table->dateTime('data_hora_inicio');
            $table->dateTime('data_hora_fim');
            $table->integer('duracao_minutos');
            $table->string('endereco', 255)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();

            $table->index('movimentacao_veiculo_id', 'fk_mov_paradas_movimento');
            $table->foreign('movimentacao_veiculo_id', 'fk_mov_paradas_movimento')
                ->references('id')->on('movimentacoes_veiculos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Rollback conservador: nenhum DROP e nenhuma remoção de dados ou de campos.
        // Os objetos acrescentados permanecem disponíveis após migrate:rollback.
    }
};
