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
        Schema::table('item_compras', function (Blueprint $table) {
            $table->foreign(['compra_id'])->references(['id'])->on('compras')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['produto_id'])->references(['id'])->on('produtos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_compras', function (Blueprint $table) {
            $table->dropForeign('item_compras_compra_id_foreign');
            $table->dropForeign('item_compras_produto_id_foreign');
        });
    }
};
