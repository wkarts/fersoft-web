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
        Schema::table('fatura_ctes', function (Blueprint $table) {
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['remetente_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fatura_ctes', function (Blueprint $table) {
            $table->dropForeign('fatura_ctes_empresa_id_foreign');
            $table->dropForeign('fatura_ctes_remetente_id_foreign');
        });
    }
};
