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
        Schema::create('fatura_frente_caixas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venda_caixa_id')->index('fatura_frente_caixas_venda_caixa_id_foreign');
            $table->decimal('valor', 16, 7);
            $table->string('forma_pagamento', 20);
            $table->boolean('entrada')->default(false);
            $table->date('data_vencimento');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fatura_frente_caixas');
    }
};
