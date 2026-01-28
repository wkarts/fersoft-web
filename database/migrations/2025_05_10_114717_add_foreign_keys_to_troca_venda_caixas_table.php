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
        Schema::table('troca_venda_caixas', function (Blueprint $table) {
            $table->foreign(['antiga_venda_caixas_id'])->references(['id'])->on('venda_caixas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['nova_venda_caixas_id'])->references(['id'])->on('venda_caixas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('troca_venda_caixas', function (Blueprint $table) {
            $table->dropForeign('troca_venda_caixas_antiga_venda_caixas_id_foreign');
            $table->dropForeign('troca_venda_caixas_empresa_id_foreign');
            $table->dropForeign('troca_venda_caixas_nova_venda_caixas_id_foreign');
        });
    }
};
