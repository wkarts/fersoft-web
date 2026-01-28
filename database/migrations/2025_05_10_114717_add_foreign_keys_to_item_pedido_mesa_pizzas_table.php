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
        Schema::table('item_pedido_mesa_pizzas', function (Blueprint $table) {
            $table->foreign(['item_pedido'])->references(['id'])->on('item_pedido_mesas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['sabor_id'])->references(['id'])->on('produto_deliveries')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_pedido_mesa_pizzas', function (Blueprint $table) {
            $table->dropForeign('item_pedido_mesa_pizzas_item_pedido_foreign');
            $table->dropForeign('item_pedido_mesa_pizzas_sabor_id_foreign');
        });
    }
};
