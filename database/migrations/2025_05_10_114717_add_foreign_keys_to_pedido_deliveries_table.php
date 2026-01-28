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
        Schema::table('pedido_deliveries', function (Blueprint $table) {
            $table->foreign(['cliente_id'])->references(['id'])->on('cliente_deliveries')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['cupom_id'])->references(['id'])->on('codigo_descontos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['endereco_id'])->references(['id'])->on('endereco_deliveries')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_deliveries', function (Blueprint $table) {
            $table->dropForeign('pedido_deliveries_cliente_id_foreign');
            $table->dropForeign('pedido_deliveries_cupom_id_foreign');
            $table->dropForeign('pedido_deliveries_empresa_id_foreign');
            $table->dropForeign('pedido_deliveries_endereco_id_foreign');
        });
    }
};
