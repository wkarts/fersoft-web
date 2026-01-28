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
        Schema::table('avaliacao_deliveries', function (Blueprint $table) {
            $table->foreign(['cliente_id'])->references(['id'])->on('cliente_deliveries')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['pedido_id'])->references(['id'])->on('pedido_deliveries')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avaliacao_deliveries', function (Blueprint $table) {
            $table->dropForeign('avaliacao_deliveries_cliente_id_foreign');
            $table->dropForeign('avaliacao_deliveries_empresa_id_foreign');
            $table->dropForeign('avaliacao_deliveries_pedido_id_foreign');
        });
    }
};
