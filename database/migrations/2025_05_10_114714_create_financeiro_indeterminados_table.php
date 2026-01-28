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
        Schema::create('financeiro_indeterminados', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('financeiro_indeterminados_empresa_id_foreign');
            $table->date('data_pagamento');
            $table->decimal('valor', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financeiro_indeterminados');
    }
};
