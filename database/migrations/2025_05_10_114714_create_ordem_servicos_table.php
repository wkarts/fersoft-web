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
        Schema::create('ordem_servicos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('ordem_servicos_empresa_id_foreign');
            $table->unsignedInteger('cliente_id')->index('ordem_servicos_cliente_id_foreign');
            $table->integer('numero_sequencial');
            $table->unsignedInteger('usuario_id')->index('ordem_servicos_usuario_id_foreign');
            $table->string('estado', 2)->default('pd');
            $table->string('descricao');
            $table->string('forma_pagamento', 30)->nullable();
            $table->decimal('valor', 10)->default(0);
            $table->timestamp('data_registro')->useCurrent();
            $table->date('data_prevista_finalizacao')->default('1981-01-01');
            $table->integer('NfNumero')->default(0);
            $table->decimal('desconto', 10)->nullable();
            $table->decimal('acrescimo', 10)->nullable();
            $table->string('observacao', 100)->nullable();
            $table->integer('venda_id')->default(0);
            $table->integer('nfse_id')->default(0);
            $table->unsignedInteger('filial_id')->nullable()->index('ordem_servicos_filial_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordem_servicos');
    }
};
