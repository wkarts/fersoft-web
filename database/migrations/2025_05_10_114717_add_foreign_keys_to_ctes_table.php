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
        Schema::table('ctes', function (Blueprint $table) {
            $table->foreign(['destinatario_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['expedidor_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['filial_id'])->references(['id'])->on('filials')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['municipio_envio'])->references(['id'])->on('cidades')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['municipio_fim'])->references(['id'])->on('cidades')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['municipio_inicio'])->references(['id'])->on('cidades')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['municipio_tomador'])->references(['id'])->on('cidades')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['natureza_id'])->references(['id'])->on('natureza_operacaos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['recebedor_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['remetente_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['usuario_id'])->references(['id'])->on('usuarios')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['veiculo_id'])->references(['id'])->on('veiculos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ctes', function (Blueprint $table) {
            $table->dropForeign('ctes_destinatario_id_foreign');
            $table->dropForeign('ctes_empresa_id_foreign');
            $table->dropForeign('ctes_expedidor_id_foreign');
            $table->dropForeign('ctes_filial_id_foreign');
            $table->dropForeign('ctes_municipio_envio_foreign');
            $table->dropForeign('ctes_municipio_fim_foreign');
            $table->dropForeign('ctes_municipio_inicio_foreign');
            $table->dropForeign('ctes_municipio_tomador_foreign');
            $table->dropForeign('ctes_natureza_id_foreign');
            $table->dropForeign('ctes_recebedor_id_foreign');
            $table->dropForeign('ctes_remetente_id_foreign');
            $table->dropForeign('ctes_usuario_id_foreign');
            $table->dropForeign('ctes_veiculo_id_foreign');
        });
    }
};
