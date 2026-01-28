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
        Schema::table('troca_venda_items', function (Blueprint $table) {
            $table->foreign(['produto_id'])->references(['id'])->on('produtos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['troca_id'])->references(['id'])->on('troca_vendas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('troca_venda_items', function (Blueprint $table) {
            $table->dropForeign('troca_venda_items_produto_id_foreign');
            $table->dropForeign('troca_venda_items_troca_id_foreign');
        });
    }
};
