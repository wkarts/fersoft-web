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
        Schema::table('venda_caixa_pre_vendas', function (Blueprint $table) {
            $table->foreign(['cliente_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['natureza_id'])->references(['id'])->on('natureza_operacaos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['usuario_id'])->references(['id'])->on('usuarios')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venda_caixa_pre_vendas', function (Blueprint $table) {
            $table->dropForeign('venda_caixa_pre_vendas_cliente_id_foreign');
            $table->dropForeign('venda_caixa_pre_vendas_empresa_id_foreign');
            $table->dropForeign('venda_caixa_pre_vendas_natureza_id_foreign');
            $table->dropForeign('venda_caixa_pre_vendas_usuario_id_foreign');
        });
    }
};
