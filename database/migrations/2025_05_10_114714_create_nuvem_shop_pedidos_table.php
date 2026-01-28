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
        Schema::create('nuvem_shop_pedidos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->nullable()->index('nuvem_shop_pedidos_empresa_id_foreign');
            $table->string('pedido_id', 30);
            $table->string('rua', 80);
            $table->string('numero', 80);
            $table->string('bairro', 50);
            $table->string('cidade', 40);
            $table->string('cep', 10);
            $table->decimal('subtotal', 10);
            $table->decimal('total', 10);
            $table->decimal('valor_frete', 10);
            $table->decimal('desconto', 10);
            $table->string('observacao', 150);
            $table->string('cliente_id', 30);
            $table->string('nome', 50);
            $table->string('email', 50);
            $table->string('documento', 20);
            $table->integer('numero_nfe')->default(0);
            $table->string('status_envio', 20);
            $table->string('gateway', 30);
            $table->string('status_pagamento', 30);
            $table->string('data', 30);
            $table->integer('venda_id')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nuvem_shop_pedidos');
    }
};
