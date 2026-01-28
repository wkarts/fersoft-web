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
        Schema::table('item_pedido_ifoods', function (Blueprint $table) {
            $table->foreign(['pedido_id'])->references(['id'])->on('pedido_ifoods')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_pedido_ifoods', function (Blueprint $table) {
            $table->dropForeign('item_pedido_ifoods_pedido_id_foreign');
        });
    }
};
