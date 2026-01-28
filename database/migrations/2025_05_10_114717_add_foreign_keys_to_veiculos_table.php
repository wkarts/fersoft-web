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
        Schema::table('veiculos', function (Blueprint $table) {
            $table->foreign(['combustivel_fk'])->references(['id'])->on('combustivel_veiculo')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['marca_fk'])->references(['id'])->on('marca_veiculo')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['modelo_fk'])->references(['id'])->on('modelo_veiculo')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['motorista_id'])->references(['id'])->on('funcionarios')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['usuario_id'])->references(['id'])->on('usuarios')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('veiculos', function (Blueprint $table) {
            $table->dropForeign('veiculos_combustivel_fk_foreign');
            $table->dropForeign('veiculos_empresa_id_foreign');
            $table->dropForeign('veiculos_marca_fk_foreign');
            $table->dropForeign('veiculos_modelo_fk_foreign');
            $table->dropForeign('veiculos_motorista_id_foreign');
            $table->dropForeign('veiculos_usuario_id_foreign');
        });
    }
};
