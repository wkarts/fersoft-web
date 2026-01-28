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
        Schema::create('fatura_orcamentos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('fatura_orcamentos_empresa_id_foreign');
            $table->unsignedInteger('orcamento_id')->nullable()->index('fatura_orcamentos_orcamento_id_foreign');
            $table->decimal('valor', 10);
            $table->date('vencimento');
            $table->string('tipo_pagamento', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fatura_orcamentos');
    }
};
