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
        Schema::table('nuvem_shop_item_pedidos', function (Blueprint $table) {
            $table->foreign(['pedido_id'])->references(['id'])->on('nuvem_shop_pedidos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['produto_id'])->references(['id'])->on('produtos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nuvem_shop_item_pedidos', function (Blueprint $table) {
            $table->dropForeign('nuvem_shop_item_pedidos_pedido_id_foreign');
            $table->dropForeign('nuvem_shop_item_pedidos_produto_id_foreign');
        });
    }
};
