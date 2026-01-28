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
        Schema::create('pagamento_pedido_ifoods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('pedido_id')->index('pagamento_pedido_ifoods_pedido_id_foreign');
            $table->string('forma_pagamento', 30)->nullable();
            $table->string('tipo_pagamento', 30)->nullable();
            $table->string('bandeira_cartao', 20)->nullable();
            $table->decimal('valor', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagamento_pedido_ifoods');
    }
};
