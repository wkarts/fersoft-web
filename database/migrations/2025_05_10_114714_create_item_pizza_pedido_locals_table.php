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
        Schema::create('item_pizza_pedido_locals', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('item_pedido')->index('item_pizza_pedido_locals_item_pedido_foreign');
            $table->unsignedInteger('sabor_id')->index('item_pizza_pedido_locals_sabor_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_pizza_pedido_locals');
    }
};
