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
        Schema::table('item_pedido_complemento_deliveries', function (Blueprint $table) {
            $table->foreign(['complemento_id'])->references(['id'])->on('complemento_deliveries')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['item_pedido_id'])->references(['id'])->on('item_pedido_deliveries')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_pedido_complemento_deliveries', function (Blueprint $table) {
            $table->dropForeign('item_pedido_complemento_deliveries_complemento_id_foreign');
            $table->dropForeign('item_pedido_complemento_deliveries_item_pedido_id_foreign');
        });
    }
};
