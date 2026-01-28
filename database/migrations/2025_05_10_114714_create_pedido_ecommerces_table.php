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
        Schema::create('pedido_ecommerces', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('pedido_ecommerces_empresa_id_foreign');
            $table->unsignedInteger('cliente_id')->nullable()->index('pedido_ecommerces_cliente_id_foreign');
            $table->unsignedInteger('endereco_id')->nullable()->index('pedido_ecommerces_endereco_id_foreign');
            $table->integer('status');
            $table->integer('status_preparacao');
            $table->string('codigo_rastreio', 20)->default('');
            $table->decimal('valor_total', 10);
            $table->decimal('valor_frete', 10);
            $table->decimal('desconto', 10);
            $table->string('tipo_frete', 10);
            $table->integer('venda_id')->default(0);
            $table->integer('numero_nfe')->default(0);
            $table->string('observacao', 100);
            $table->string('rand_pedido', 20);
            $table->string('token', 20)->default('');
            $table->text('link_boleto');
            $table->text('qr_code_base64');
            $table->text('qr_code');
            $table->string('transacao_id', 100)->default('');
            $table->string('forma_pagamento', 10)->default('');
            $table->string('status_pagamento', 15)->default('');
            $table->string('status_detalhe', 100)->default('');
            $table->string('hash', 20)->default('');
            $table->string('cupom_desconto', 6)->nullable();
            $table->boolean('modelo_orcamento')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_ecommerces');
    }
};
