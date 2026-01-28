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
        Schema::table('lista_complemento_deliveries', function (Blueprint $table) {
            $table->foreign(['categoria_id'])->references(['id'])->on('categoria_produto_deliveries')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['complemento_id'])->references(['id'])->on('complemento_deliveries')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lista_complemento_deliveries', function (Blueprint $table) {
            $table->dropForeign('lista_complemento_deliveries_categoria_id_foreign');
            $table->dropForeign('lista_complemento_deliveries_complemento_id_foreign');
        });
    }
};
