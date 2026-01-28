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
        Schema::create('pedido_motoboys', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('motoboy_id')->nullable()->index('pedido_motoboys_motoboy_id_foreign');
            $table->unsignedInteger('pedido_id')->nullable()->index('pedido_motoboys_pedido_id_foreign');
            $table->decimal('valor', 7);
            $table->boolean('status_pagamento')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_motoboys');
    }
};
