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
        Schema::table('categoria_loja_segmentos', function (Blueprint $table) {
            $table->foreign(['categoria_id'])->references(['id'])->on('categoria_master_deliveries')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['loja_id'])->references(['id'])->on('delivery_configs')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categoria_loja_segmentos', function (Blueprint $table) {
            $table->dropForeign('categoria_loja_segmentos_categoria_id_foreign');
            $table->dropForeign('categoria_loja_segmentos_loja_id_foreign');
        });
    }
};
