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
        Schema::table('produto_pizzas', function (Blueprint $table) {
            $table->foreign(['produto_id'])->references(['id'])->on('produto_deliveries')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['tamanho_id'])->references(['id'])->on('tamanho_pizzas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produto_pizzas', function (Blueprint $table) {
            $table->dropForeign('produto_pizzas_produto_id_foreign');
            $table->dropForeign('produto_pizzas_tamanho_id_foreign');
        });
    }
};
