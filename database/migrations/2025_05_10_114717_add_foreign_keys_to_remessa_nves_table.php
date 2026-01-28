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
        Schema::table('remessa_nves', function (Blueprint $table) {
            $table->foreign(['cliente_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['filial_id'])->references(['id'])->on('filials')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['natureza_id'])->references(['id'])->on('natureza_operacaos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['transportadora_id'])->references(['id'])->on('transportadoras')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['usuario_id'])->references(['id'])->on('usuarios')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remessa_nves', function (Blueprint $table) {
            $table->dropForeign('remessa_nves_cliente_id_foreign');
            $table->dropForeign('remessa_nves_empresa_id_foreign');
            $table->dropForeign('remessa_nves_filial_id_foreign');
            $table->dropForeign('remessa_nves_natureza_id_foreign');
            $table->dropForeign('remessa_nves_transportadora_id_foreign');
            $table->dropForeign('remessa_nves_usuario_id_foreign');
        });
    }
};
