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
        Schema::table('custos_operacionais', function (Blueprint $table) {
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['responsavel_id'])->references(['id'])->on('funcionarios')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['usuario_id'])->references(['id'])->on('usuarios')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['veiculo_id'])->references(['id'])->on('veiculos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custos_operacionais', function (Blueprint $table) {
            $table->dropForeign('custos_operacionais_empresa_id_foreign');
            $table->dropForeign('custos_operacionais_responsavel_id_foreign');
            $table->dropForeign('custos_operacionais_usuario_id_foreign');
            $table->dropForeign('custos_operacionais_veiculo_id_foreign');
        });
    }
};
