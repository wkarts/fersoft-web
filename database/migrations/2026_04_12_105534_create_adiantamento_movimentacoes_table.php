<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdiantamentoMovimentacoesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('adiantamento_movimentacoes')) {
            Schema::create('adiantamento_movimentacoes', function (Blueprint $table) {
                $table->increments('id');

                $table->unsignedInteger('empresa_id');
                $table->unsignedInteger('usuario_id');
                $table->unsignedInteger('filial_id');

                $table->unsignedInteger('adiantamento_id');
                $table->unsignedInteger('conta_receber_id')->nullable()->comment('ID da fatura de venda (se cliente)');
                $table->unsignedInteger('conta_pagar_id')->nullable()->comment('ID da fatura de compra (se fornecedor)');

                $table->decimal('valor', 16, 2);
                $table->date('data');

                $table->timestamps();

                $table->index('empresa_id', 'idx_adiant_mov_empresa_id');
                $table->index('usuario_id', 'idx_adiant_mov_usuario_id');
                $table->index('filial_id', 'idx_adiant_mov_filial_id');
                $table->index('adiantamento_id', 'idx_adiant_mov_adiantamento_id');
                $table->index('conta_receber_id', 'idx_adiant_mov_conta_receber_id');
                $table->index('conta_pagar_id', 'idx_adiant_mov_conta_pagar_id');

                $table->foreign('empresa_id', 'adiant_mov_empresa_id_foreign')
                    ->references('id')->on('empresas')->onDelete('cascade');

                $table->foreign('usuario_id', 'adiant_mov_usuario_id_foreign')
                    ->references('id')->on('usuarios')->onDelete('cascade');

                $table->foreign('filial_id', 'adiant_mov_filial_id_foreign')
                    ->references('id')->on('filials')->onDelete('cascade');

                $table->foreign('adiantamento_id', 'fk_mov_adiantamento')
                    ->references('id')->on('adiantamentos')->onDelete('cascade');

                $table->foreign('conta_receber_id', 'adiant_mov_conta_receber_fk')
                    ->references('id')->on('conta_recebers')->onDelete('set null');

                $table->foreign('conta_pagar_id', 'adiant_mov_conta_pagar_fk')
                    ->references('id')->on('conta_pagars')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('adiantamento_movimentacoes');
    }
}
