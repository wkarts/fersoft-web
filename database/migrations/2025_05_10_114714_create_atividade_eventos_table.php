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
        Schema::create('atividade_eventos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('responsavel_nome', 50);
            $table->string('responsavel_telefone', 15);
            $table->string('crianca_nome', 50);
            $table->time('inicio');
            $table->time('fim');
            $table->decimal('total', 10);
            $table->boolean('status')->default(false);
            $table->unsignedInteger('evento_id')->index('atividade_eventos_evento_id_foreign');
            $table->unsignedInteger('funcionario_id')->index('atividade_eventos_funcionario_id_foreign');
            $table->string('forma_pagamento', 25);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atividade_eventos');
    }
};
