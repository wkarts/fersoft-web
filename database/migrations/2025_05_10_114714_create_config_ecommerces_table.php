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
        Schema::create('config_ecommerces', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 30);
            $table->string('link', 30);
            $table->string('logo', 80);
            $table->string('rua', 80);
            $table->string('numero', 10);
            $table->string('bairro', 30);
            $table->string('cidade', 30);
            $table->string('uf', 2);
            $table->string('cep', 10);
            $table->string('telefone', 15);
            $table->string('email', 60);
            $table->string('link_facebook', 120);
            $table->string('link_twiter', 120);
            $table->string('link_instagram', 120);
            $table->decimal('frete_gratis_valor', 10);
            $table->string('mercadopago_public_key', 120);
            $table->string('mercadopago_access_token', 120);
            $table->string('funcionamento', 120);
            $table->string('latitude', 10);
            $table->string('longitude', 10);
            $table->text('politica_privacidade');
            $table->text('src_mapa');
            $table->string('cor_principal', 8);
            $table->string('tema_ecommerce', 30);
            $table->string('token', 30);
            $table->boolean('habilitar_retirada')->default(false);
            $table->decimal('desconto_padrao_boleto', 4);
            $table->decimal('desconto_padrao_pix', 4);
            $table->decimal('desconto_padrao_cartao', 4);
            $table->string('google_api', 40)->default('');
            $table->unsignedInteger('empresa_id')->index('config_ecommerces_empresa_id_foreign');
            $table->string('api_token', 25)->default('');
            $table->boolean('usar_api')->default(false);
            $table->string('cor_fundo', 7)->default('#000');
            $table->string('cor_btn', 7)->default('#000');
            $table->text('mensagem_agradecimento');
            $table->string('img_contato', 80)->default('');
            $table->string('fav_icon', 80)->default('');
            $table->integer('timer_carrossel')->default(5);
            $table->boolean('modelo_orcamento')->default(false);
            $table->text('formas_pagamento');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_ecommerces');
    }
};
