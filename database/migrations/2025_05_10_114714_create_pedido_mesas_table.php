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
        Schema::create('pedido_mesas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('pedido_mesas_empresa_id_foreign');
            $table->decimal('valor_total', 10)->nullable();
            $table->string('forma_pagamento', 20)->nullable();
            $table->string('observacao', 50)->nullable();
            $table->enum('estado', ['fechado', 'aberto', 'concluido', 'recusado']);
            $table->string('uid', 40);
            $table->string('nome_cliente');
            $table->string('telefone_cliente');
            $table->integer('mesa_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_mesas');
    }
};
