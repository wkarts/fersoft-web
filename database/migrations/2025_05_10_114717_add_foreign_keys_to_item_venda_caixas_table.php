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
        Schema::table('item_venda_caixas', function (Blueprint $table) {
            $table->foreign(['item_pedido_id'])->references(['id'])->on('item_pedidos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['produto_id'])->references(['id'])->on('produtos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['venda_caixa_id'])->references(['id'])->on('venda_caixas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_venda_caixas', function (Blueprint $table) {
            $table->dropForeign('item_venda_caixas_item_pedido_id_foreign');
            $table->dropForeign('item_venda_caixas_produto_id_foreign');
            $table->dropForeign('item_venda_caixas_venda_caixa_id_foreign');
        });
    }
};
