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
        Schema::table('item_cotacaos', function (Blueprint $table) {
            $table->foreign(['cotacao_id'])->references(['id'])->on('cotacaos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['produto_id'])->references(['id'])->on('produtos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_cotacaos', function (Blueprint $table) {
            $table->dropForeign('item_cotacaos_cotacao_id_foreign');
            $table->dropForeign('item_cotacaos_produto_id_foreign');
        });
    }
};
