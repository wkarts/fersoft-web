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
        Schema::table('item_pedido_deliveries', function (Blueprint $table) {
            $table->foreign(['pedido_id'])->references(['id'])->on('pedido_deliveries')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['produto_id'])->references(['id'])->on('produto_deliveries')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['tamanho_id'])->references(['id'])->on('tamanho_pizzas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_pedido_deliveries', function (Blueprint $table) {
            $table->dropForeign('item_pedido_deliveries_pedido_id_foreign');
            $table->dropForeign('item_pedido_deliveries_produto_id_foreign');
            $table->dropForeign('item_pedido_deliveries_tamanho_id_foreign');
        });
    }
};
