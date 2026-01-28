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
        Schema::create('delivery_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('delivery_configs_empresa_id_foreign');
            $table->unsignedInteger('cidade_id')->index('delivery_configs_cidade_id_foreign');
            $table->string('link_face');
            $table->string('link_twiteer');
            $table->string('link_google');
            $table->string('link_instagram');
            $table->string('telefone', 20);
            $table->string('rua', 80);
            $table->string('numero', 15);
            $table->string('bairro', 30);
            $table->string('cep', 9);
            $table->string('tempo_medio_entrega', 10);
            $table->string('tempo_maximo_cancelamento', 10);
            $table->decimal('valor_entrega', 10);
            $table->string('nome', 30);
            $table->string('descricao', 200);
            $table->string('latitude', 15);
            $table->string('longitude', 15);
            $table->string('politica_privacidade');
            $table->decimal('valor_km', 10);
            $table->integer('valor_entrega_gratis');
            $table->integer('maximo_km_entrega');
            $table->string('tipo_entrega', 20);
            $table->boolean('usar_bairros');
            $table->boolean('status')->default(false);
            $table->boolean('notificacao_novo_pedido')->default(true);
            $table->string('mercadopago_public_key', 120);
            $table->string('mercadopago_access_token', 120);
            $table->integer('maximo_adicionais');
            $table->integer('maximo_adicionais_pizza');
            $table->integer('tipo_divisao_pizza');
            $table->integer('maximo_sabores_pizza');
            $table->string('logo', 25);
            $table->string('one_signal_app_id', 50);
            $table->string('one_signal_key', 50);
            $table->string('tipos_pagamento')->default('[]');
            $table->decimal('pedido_minimo', 10);
            $table->decimal('avaliacao_media', 10);
            $table->string('api_token', 50)->nullable();
            $table->boolean('autenticacao_sms')->default(false);
            $table->boolean('confirmacao_pedido_cliente')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_configs');
    }
};
