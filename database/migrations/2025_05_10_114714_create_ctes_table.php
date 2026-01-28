<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ctes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('ctes_empresa_id_foreign');
            $table->text('chave_nfe');
            $table->unsignedInteger('remetente_id')->index('ctes_remetente_id_foreign');
            $table->unsignedInteger('destinatario_id')->index('ctes_destinatario_id_foreign');
            $table->unsignedInteger('recebedor_id')->nullable()->index('ctes_recebedor_id_foreign');
            $table->unsignedInteger('expedidor_id')->nullable()->index('ctes_expedidor_id_foreign');
            $table->unsignedInteger('usuario_id')->index('ctes_usuario_id_foreign');
            $table->unsignedInteger('natureza_id')->index('ctes_natureza_id_foreign');
            $table->integer('tomador');
            $table->unsignedInteger('municipio_envio')->index('ctes_municipio_envio_foreign');
            $table->unsignedInteger('municipio_inicio')->index('ctes_municipio_inicio_foreign');
            $table->unsignedInteger('municipio_fim')->index('ctes_municipio_fim_foreign');
            $table->string('logradouro_tomador', 80)->nullable();
            $table->string('numero_tomador', 20)->nullable();
            $table->string('bairro_tomador', 40)->nullable();
            $table->string('cep_tomador', 10)->nullable();
            $table->unsignedInteger('municipio_tomador')->nullable()->index('ctes_municipio_tomador_foreign');
            $table->decimal('valor_transporte', 10);
            $table->decimal('valor_receber', 10);
            $table->decimal('valor_carga', 10);
            $table->string('produto_predominante', 30);
            $table->date('data_previsata_entrega');
            $table->string('observacao');
            $table->integer('sequencia_cce');
            $table->integer('cte_numero')->default(0);
            $table->string('chave', 48);
            $table->string('path_xml', 51);
            $table->string('estado', 20);
            $table->timestamp('data_registro')->useCurrent();
            $table->boolean('retira');
            $table->string('detalhes_retira', 100);
            $table->string('modal', 2);
            $table->unsignedInteger('veiculo_id')->index('ctes_veiculo_id_foreign');
            $table->string('tpDoc', 2);
            $table->string('descOutros', 100);
            $table->integer('nDoc');
            $table->decimal('vDocFisc', 10);
            $table->integer('globalizado');
            $table->integer('tipo_servico')->default(0);
            $table->string('cst', 3)->default('00');
            $table->decimal('perc_icms', 5)->default(0);
            $table->decimal('pRedBC', 5)->default(0);
            $table->boolean('status_pagamento')->default(false);
            $table->unsignedInteger('filial_id')->nullable()->index('ctes_filial_id_foreign');
            $table->string('recibo', 30)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ctes');
    }
};
