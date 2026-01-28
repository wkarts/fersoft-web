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
        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreign(['bairro_id'])->references(['id'])->on('bairro_deliveries')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['cliente_id'])->references(['id'])->on('clientes')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['mesa_id'])->references(['id'])->on('mesas')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign('pedidos_bairro_id_foreign');
            $table->dropForeign('pedidos_cliente_id_foreign');
            $table->dropForeign('pedidos_empresa_id_foreign');
            $table->dropForeign('pedidos_mesa_id_foreign');
        });
    }
};
