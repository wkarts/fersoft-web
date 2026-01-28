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
        Schema::table('item_servico_venda_caixas', function (Blueprint $table) {
            $table->foreign(['servico_id'])->references(['id'])->on('servicos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['venda_caixa_id'])->references(['id'])->on('venda_caixas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_servico_venda_caixas', function (Blueprint $table) {
            $table->dropForeign('item_servico_venda_caixas_servico_id_foreign');
            $table->dropForeign('item_servico_venda_caixas_venda_caixa_id_foreign');
        });
    }
};
