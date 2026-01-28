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
        Schema::table('item_pizza_pedido_locals', function (Blueprint $table) {
            $table->foreign(['item_pedido'])->references(['id'])->on('item_pedidos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['sabor_id'])->references(['id'])->on('produto_deliveries')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_pizza_pedido_locals', function (Blueprint $table) {
            $table->dropForeign('item_pizza_pedido_locals_item_pedido_foreign');
            $table->dropForeign('item_pizza_pedido_locals_sabor_id_foreign');
        });
    }
};
