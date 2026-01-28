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
        Schema::table('nfses', function (Blueprint $table) {
            $table->foreign(['cidade_id'])->references(['id'])->on('cidades')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['cliente_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['filial_id'])->references(['id'])->on('filials')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nfses', function (Blueprint $table) {
            $table->dropForeign('nfses_cidade_id_foreign');
            $table->dropForeign('nfses_cliente_id_foreign');
            $table->dropForeign('nfses_empresa_id_foreign');
            $table->dropForeign('nfses_filial_id_foreign');
        });
    }
};
