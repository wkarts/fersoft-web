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
        Schema::create('orcamentos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('orcamentos_empresa_id_foreign');
            $table->unsignedInteger('cliente_id')->index('orcamentos_cliente_id_foreign');
            $table->unsignedInteger('usuario_id')->index('orcamentos_usuario_id_foreign');
            $table->unsignedInteger('natureza_id')->nullable()->index('orcamentos_natureza_id_foreign');
            $table->unsignedInteger('frete_id')->nullable()->index('orcamentos_frete_id_foreign');
            $table->unsignedInteger('transportadora_id')->nullable()->index('orcamentos_transportadora_id_foreign');
            $table->decimal('valor_total', 16, 7);
            $table->decimal('desconto', 10);
            $table->decimal('acrescimo', 10);
            $table->string('forma_pagamento', 20);
            $table->string('tipo_pagamento', 2);
            $table->string('observacao');
            $table->string('estado', 20);
            $table->boolean('email_enviado');
            $table->date('validade');
            $table->date('data_entrega')->nullable();
            $table->integer('venda_id');
            $table->date('data_retroativa')->nullable();
            $table->unsignedInteger('filial_id')->nullable()->index('orcamentos_filial_id_foreign');
            $table->integer('vendedor_id')->nullable();
            $table->boolean('ecommerce')->nullable();
            $table->integer('numero_sequencial');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orcamentos');
    }
};
