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
        Schema::table('item_pedidos', function (Blueprint $table) {
            $table->foreign(['pedido_id'])->references(['id'])->on('pedidos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['produto_id'])->references(['id'])->on('produtos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['tamanho_pizza_id'])->references(['id'])->on('tamanho_pizzas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_pedidos', function (Blueprint $table) {
            $table->dropForeign('item_pedidos_pedido_id_foreign');
            $table->dropForeign('item_pedidos_produto_id_foreign');
            $table->dropForeign('item_pedidos_tamanho_pizza_id_foreign');
        });
    }
};
