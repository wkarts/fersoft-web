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
        Schema::table('cte_os', function (Blueprint $table) {
            $table->foreign(['emitente_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['municipio_envio'])->references(['id'])->on('cidades')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['municipio_fim'])->references(['id'])->on('cidades')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['municipio_inicio'])->references(['id'])->on('cidades')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['natureza_id'])->references(['id'])->on('natureza_operacaos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['tomador_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['usuario_id'])->references(['id'])->on('usuarios')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['veiculo_id'])->references(['id'])->on('veiculos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cte_os', function (Blueprint $table) {
            $table->dropForeign('cte_os_emitente_id_foreign');
            $table->dropForeign('cte_os_empresa_id_foreign');
            $table->dropForeign('cte_os_municipio_envio_foreign');
            $table->dropForeign('cte_os_municipio_fim_foreign');
            $table->dropForeign('cte_os_municipio_inicio_foreign');
            $table->dropForeign('cte_os_natureza_id_foreign');
            $table->dropForeign('cte_os_tomador_id_foreign');
            $table->dropForeign('cte_os_usuario_id_foreign');
            $table->dropForeign('cte_os_veiculo_id_foreign');
        });
    }
};
