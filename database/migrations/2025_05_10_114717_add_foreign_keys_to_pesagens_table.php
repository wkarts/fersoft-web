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
        Schema::table('pesagens', function (Blueprint $table) {
            $table->foreign(['cliente_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['compra_id'])->references(['id'])->on('compras')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['fornecedor_id'])->references(['id'])->on('fornecedors')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['motorista_id'])->references(['id'])->on('funcionarios')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['usuario_id'])->references(['id'])->on('usuarios')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['veiculo_id'])->references(['id'])->on('veiculos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['venda_id'])->references(['id'])->on('vendas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pesagens', function (Blueprint $table) {
            $table->dropForeign('pesagens_cliente_id_foreign');
            $table->dropForeign('pesagens_compra_id_foreign');
            $table->dropForeign('pesagens_empresa_id_foreign');
            $table->dropForeign('pesagens_fornecedor_id_foreign');
            $table->dropForeign('pesagens_motorista_id_foreign');
            $table->dropForeign('pesagens_usuario_id_foreign');
            $table->dropForeign('pesagens_veiculo_id_foreign');
            $table->dropForeign('pesagens_venda_id_foreign');
        });
    }
};
