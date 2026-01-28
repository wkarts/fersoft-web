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
        Schema::table('produto_lista_precos', function (Blueprint $table) {
            $table->foreign(['lista_id'])->references(['id'])->on('lista_precos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['produto_id'])->references(['id'])->on('produtos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produto_lista_precos', function (Blueprint $table) {
            $table->dropForeign('produto_lista_precos_lista_id_foreign');
            $table->dropForeign('produto_lista_precos_produto_id_foreign');
        });
    }
};
