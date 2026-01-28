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
        Schema::create('vale_pedagios', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('mdfe_id')->index('vale_pedagios_mdfe_id_foreign');
            $table->string('cnpj_fornecedor', 18);
            $table->string('cnpj_fornecedor_pagador', 18);
            $table->string('numero_compra', 18);
            $table->decimal('valor', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vale_pedagios');
    }
};
