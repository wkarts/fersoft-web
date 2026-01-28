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
        Schema::create('cotacaos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('cotacaos_empresa_id_foreign');
            $table->unsignedInteger('fornecedor_id')->index('cotacaos_fornecedor_id_foreign');
            $table->string('forma_pagamento', 50);
            $table->string('responsavel', 50);
            $table->string('referencia', 20);
            $table->string('link', 20);
            $table->string('observacao', 100);
            $table->boolean('resposta');
            $table->boolean('ativa');
            $table->decimal('valor', 10);
            $table->decimal('desconto', 10);
            $table->boolean('escolhida');
            $table->timestamp('data_registro')->useCurrent();
            $table->integer('venda_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotacaos');
    }
};
