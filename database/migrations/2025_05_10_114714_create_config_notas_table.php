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
        Schema::create('config_notas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('config_notas_empresa_id_foreign');
            $table->string('razao_social', 100);
            $table->string('nome_fantasia', 80);
            $table->string('cnpj', 19);
            $table->string('ie', 20);
            $table->string('logradouro', 80);
            $table->string('complemento', 100)->default('');
            $table->string('numero', 10);
            $table->string('bairro', 50);
            $table->string('fone', 20);
            $table->string('cep', 10);
            $table->string('pais', 20);
            $table->string('email', 60);
            $table->string('municipio', 30);
            $table->integer('codPais');
            $table->integer('codMun');
            $table->char('UF', 2);
            $table->string('CST_CSOSN_padrao', 3);
            $table->string('CST_COFINS_padrao', 3);
            $table->string('CST_PIS_padrao', 3);
            $table->string('CST_IPI_padrao', 3);
            $table->string('cBenef_padrao', 10)->nullable();
            $table->integer('frete_padrao');
            $table->string('tipo_pagamento_padrao', 2);
            $table->integer('nat_op_padrao');
            $table->integer('ambiente');
            $table->string('cUF', 2);
            $table->string('numero_serie_nfe', 3);
            $table->string('numero_serie_nfce', 3);
            $table->string('numero_serie_cte', 3);
            $table->string('numero_serie_mdfe', 3);
            $table->string('numero_serie_nfse', 3);
            $table->integer('ultimo_numero_nfe');
            $table->integer('ultimo_numero_nfce');
            $table->integer('ultimo_numero_cte');
            $table->integer('ultimo_numero_mdfe');
            $table->integer('ultimo_numero_nfse');
            $table->string('csc', 60);
            $table->string('csc_id', 10);
            $table->boolean('certificado_a3')->default(false);
            $table->string('inscricao_municipal', 25)->default('');
            $table->string('aut_xml', 20)->default('');
            $table->string('logo', 100)->default('');
            $table->integer('validade_orcamento')->default(0);
            $table->integer('casas_decimais')->default(2);
            $table->integer('casas_decimais_qtd')->default(2);
            $table->text('campo_obs_nfe');
            $table->text('campo_obs_pedido');
            $table->string('senha_remover', 80)->default('');
            $table->decimal('percentual_lucro_padrao', 6)->default(0);
            $table->decimal('percentual_max_desconto', 6)->default(0);
            $table->string('sobrescrita_csonn_consumidor_final', 3);
            $table->boolean('caixa_por_usuario')->default(true);
            $table->boolean('usar_email_proprio')->default(false);
            $table->boolean('gerenciar_estoque_produto')->default(false);
            $table->boolean('gerenciar_comissao_usuario_logado')->default(false);
            $table->string('token_ibpt', 80);
            $table->string('token_nfse', 150);
            $table->string('integracao_nfse', 20);
            $table->string('token_whatsapp', 80);
            $table->enum('whatsapp_technology', ['legacy', 'evo'])->default('evo');
            $table->string('token_sync')->default('')->unique()->comment('Token de sincronização');
            $table->string('codigo_tributacao_municipio', 80);
            $table->string('alerta_sonoro', 10);
            $table->integer('parcelamento_maximo')->default(12);
            $table->text('graficos_dash')->nullable();
            $table->boolean('busca_documento_automatico')->default(false);
            $table->decimal('juro_padrao', 6)->default(0);
            $table->decimal('multa_padrao', 6)->default(0);
            $table->integer('tipo_impressao_danfe')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_notas');
    }
};
