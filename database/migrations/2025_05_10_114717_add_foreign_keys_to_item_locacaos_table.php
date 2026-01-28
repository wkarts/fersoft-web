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
        Schema::table('item_locacaos', function (Blueprint $table) {
            $table->foreign(['locacao_id'])->references(['id'])->on('locacaos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['produto_id'])->references(['id'])->on('produtos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_locacaos', function (Blueprint $table) {
            $table->dropForeign('item_locacaos_locacao_id_foreign');
            $table->dropForeign('item_locacaos_produto_id_foreign');
        });
    }
};
