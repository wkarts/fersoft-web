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
        Schema::table('fatura_orcamentos', function (Blueprint $table) {
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['orcamento_id'])->references(['id'])->on('orcamentos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fatura_orcamentos', function (Blueprint $table) {
            $table->dropForeign('fatura_orcamentos_empresa_id_foreign');
            $table->dropForeign('fatura_orcamentos_orcamento_id_foreign');
        });
    }
};
