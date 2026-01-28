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
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->foreign(['cliente_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['filial_id'])->references(['id'])->on('filials')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['frete_id'])->references(['id'])->on('fretes')->onUpdate('no action')->onDelete('cascade');
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
        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropForeign('orcamentos_cliente_id_foreign');
            $table->dropForeign('orcamentos_empresa_id_foreign');
            $table->dropForeign('orcamentos_filial_id_foreign');
            $table->dropForeign('orcamentos_frete_id_foreign');
            $table->dropForeign('orcamentos_natureza_id_foreign');
            $table->dropForeign('orcamentos_transportadora_id_foreign');
            $table->dropForeign('orcamentos_usuario_id_foreign');
        });
    }
};
