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
        Schema::create('item_pedido_mesa_pizzas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('item_pedido')->index('item_pedido_mesa_pizzas_item_pedido_foreign');
            $table->unsignedInteger('sabor_id')->index('item_pedido_mesa_pizzas_sabor_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_pedido_mesa_pizzas');
    }
};
