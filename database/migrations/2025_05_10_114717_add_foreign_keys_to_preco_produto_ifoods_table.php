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
        Schema::table('preco_produto_ifoods', function (Blueprint $table) {
            $table->foreign(['produto_ifood_id'])->references(['id'])->on('produto_ifoods')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('preco_produto_ifoods', function (Blueprint $table) {
            $table->dropForeign('preco_produto_ifoods_produto_ifood_id_foreign');
        });
    }
};
