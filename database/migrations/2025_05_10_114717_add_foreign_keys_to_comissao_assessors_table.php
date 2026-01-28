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
        Schema::table('comissao_assessors', function (Blueprint $table) {
            $table->foreign(['assessor_id'])->references(['id'])->on('acessors')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['venda_caixa_id'])->references(['id'])->on('venda_caixas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comissao_assessors', function (Blueprint $table) {
            $table->dropForeign('comissao_assessors_assessor_id_foreign');
            $table->dropForeign('comissao_assessors_venda_caixa_id_foreign');
        });
    }
};
