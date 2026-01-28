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
        Schema::create('financeiro_representantes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('representante_empresa_id')->index('financeiro_representantes_representante_empresa_id_foreign');
            $table->string('forma_pagamento', 30);
            $table->decimal('valor');
            $table->boolean('pagamento_comissao');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financeiro_representantes');
    }
};
