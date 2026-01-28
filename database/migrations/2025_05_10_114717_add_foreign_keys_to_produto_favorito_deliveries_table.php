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
        Schema::table('produto_favorito_deliveries', function (Blueprint $table) {
            $table->foreign(['cliente_id'])->references(['id'])->on('cliente_deliveries')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['produto_id'])->references(['id'])->on('produto_deliveries')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produto_favorito_deliveries', function (Blueprint $table) {
            $table->dropForeign('produto_favorito_deliveries_cliente_id_foreign');
            $table->dropForeign('produto_favorito_deliveries_produto_id_foreign');
        });
    }
};
