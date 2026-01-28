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
        Schema::table('devolucaos', function (Blueprint $table) {
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['filial_id'])->references(['id'])->on('filials')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['fornecedor_id'])->references(['id'])->on('fornecedors')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['natureza_id'])->references(['id'])->on('natureza_operacaos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['transportadora_id'])->references(['id'])->on('transportadoras')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['usuario_id'])->references(['id'])->on('usuarios')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devolucaos', function (Blueprint $table) {
            $table->dropForeign('devolucaos_empresa_id_foreign');
            $table->dropForeign('devolucaos_filial_id_foreign');
            $table->dropForeign('devolucaos_fornecedor_id_foreign');
            $table->dropForeign('devolucaos_natureza_id_foreign');
            $table->dropForeign('devolucaos_transportadora_id_foreign');
            $table->dropForeign('devolucaos_usuario_id_foreign');
        });
    }
};
