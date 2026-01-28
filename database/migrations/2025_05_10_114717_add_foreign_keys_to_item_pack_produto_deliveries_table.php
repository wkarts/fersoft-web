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
        Schema::table('item_pack_produto_deliveries', function (Blueprint $table) {
            $table->foreign(['pack_id'])->references(['id'])->on('pack_produto_deliveries')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['produto_delivery_id'])->references(['id'])->on('produto_deliveries')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_pack_produto_deliveries', function (Blueprint $table) {
            $table->dropForeign('item_pack_produto_deliveries_pack_id_foreign');
            $table->dropForeign('item_pack_produto_deliveries_produto_delivery_id_foreign');
        });
    }
};
