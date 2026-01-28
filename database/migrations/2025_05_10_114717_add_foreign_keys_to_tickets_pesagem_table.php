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
        Schema::table('tickets_pesagem', function (Blueprint $table) {
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['filial_id'])->references(['id'])->on('filials')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['motorista_id'])->references(['id'])->on('funcionarios')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['pesagem_id'])->references(['id'])->on('pesagens')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['produto_id'])->references(['id'])->on('produtos')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['usuario_id'])->references(['id'])->on('usuarios')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['veiculo_id'])->references(['id'])->on('veiculos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets_pesagem', function (Blueprint $table) {
            $table->dropForeign('tickets_pesagem_empresa_id_foreign');
            $table->dropForeign('tickets_pesagem_filial_id_foreign');
            $table->dropForeign('tickets_pesagem_motorista_id_foreign');
            $table->dropForeign('tickets_pesagem_pesagem_id_foreign');
            $table->dropForeign('tickets_pesagem_produto_id_foreign');
            $table->dropForeign('tickets_pesagem_usuario_id_foreign');
            $table->dropForeign('tickets_pesagem_veiculo_id_foreign');
        });
    }
};
