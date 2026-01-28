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
        Schema::table('item_orcamentos', function (Blueprint $table) {
            $table->foreign(['orcamento_id'])->references(['id'])->on('orcamentos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['produto_id'])->references(['id'])->on('produtos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_orcamentos', function (Blueprint $table) {
            $table->dropForeign('item_orcamentos_orcamento_id_foreign');
            $table->dropForeign('item_orcamentos_produto_id_foreign');
        });
    }
};
