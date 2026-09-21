<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('faturas_engenharia')) {
            return;
        }

        Schema::create('faturas_engenharia', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_eng_id')->nullable();
            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('usuario_id')->nullable();
            $table->unsignedInteger('filial_id')->nullable();
            $table->unsignedInteger('cliente_id')->nullable();
            $table->unsignedInteger('vendedor_id')->nullable();
            $table->unsignedInteger('condicao_pagamento_id')->nullable();
            $table->unsignedInteger('categoria_conta_id')->nullable();
            $table->unsignedInteger('servico_id')->nullable();
            $table->unsignedInteger('municipio_prestacao_id')->nullable();
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->decimal('valor_retencao', 15, 2)->default(0);
            $table->decimal('valor_liquido', 15, 2)->default(0);
            $table->date('data_faturamento')->nullable();
            $table->string('status', 30)->default('Pendente');
            $table->string('status_financeiro', 30)->nullable();
            $table->string('codigo_obra', 100)->nullable();
            $table->string('numero_nfse', 100)->nullable();
            $table->string('serie_nfse', 50)->nullable();
            $table->string('chave_nfse', 191)->nullable();
            $table->string('protocolo_nfse', 191)->nullable();
            $table->longText('xml_nfse')->nullable();
            $table->text('observacao')->nullable();
            $table->text('observacoes_fatura')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'contrato_eng_id'], 'faturas_eng_empresa_contrato_idx');
            $table->index(['empresa_id', 'status'], 'faturas_eng_empresa_status_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('faturas_engenharia');
    }
};
