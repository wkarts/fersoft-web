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
        Schema::table('imagem_produto_ecommerces', function (Blueprint $table) {
            $table->foreign(['produto_id'])->references(['id'])->on('produto_ecommerces')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imagem_produto_ecommerces', function (Blueprint $table) {
            $table->dropForeign('imagem_produto_ecommerces_produto_id_foreign');
        });
    }
};
