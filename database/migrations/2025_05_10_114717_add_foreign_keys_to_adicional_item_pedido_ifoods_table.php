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
        Schema::table('adicional_item_pedido_ifoods', function (Blueprint $table) {
            $table->foreign(['item_pedido_id'])->references(['id'])->on('item_pedido_ifoods')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adicional_item_pedido_ifoods', function (Blueprint $table) {
            $table->dropForeign('adicional_item_pedido_ifoods_item_pedido_id_foreign');
        });
    }
};
