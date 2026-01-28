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
        Schema::table('conta_recebers', function (Blueprint $table) {
            $table->foreign(['categoria_id'])->references(['id'])->on('categoria_contas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['cliente_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['filial_id'])->references(['id'])->on('filials')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['venda_caixa_id'])->references(['id'])->on('venda_caixas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['venda_id'])->references(['id'])->on('vendas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conta_recebers', function (Blueprint $table) {
            $table->dropForeign('conta_recebers_categoria_id_foreign');
            $table->dropForeign('conta_recebers_cliente_id_foreign');
            $table->dropForeign('conta_recebers_empresa_id_foreign');
            $table->dropForeign('conta_recebers_filial_id_foreign');
            $table->dropForeign('conta_recebers_venda_caixa_id_foreign');
            $table->dropForeign('conta_recebers_venda_id_foreign');
        });
    }
};
