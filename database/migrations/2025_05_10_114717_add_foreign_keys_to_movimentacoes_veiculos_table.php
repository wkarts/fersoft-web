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
        Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['motorista_id'])->references(['id'])->on('funcionarios')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['tipo_movimentacao_id'])->references(['id'])->on('tipos_movimentacoes')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['usuario_id'])->references(['id'])->on('usuarios')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['veiculo_id'])->references(['id'])->on('veiculos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimentacoes_veiculos', function (Blueprint $table) {
            $table->dropForeign('movimentacoes_veiculos_empresa_id_foreign');
            $table->dropForeign('movimentacoes_veiculos_motorista_id_foreign');
            $table->dropForeign('movimentacoes_veiculos_tipo_movimentacao_id_foreign');
            $table->dropForeign('movimentacoes_veiculos_usuario_id_foreign');
            $table->dropForeign('movimentacoes_veiculos_veiculo_id_foreign');
        });
    }
};
