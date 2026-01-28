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
        Schema::table('pedido_qr_code_clientes', function (Blueprint $table) {
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['pedido_id'])->references(['id'])->on('pedidos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_qr_code_clientes', function (Blueprint $table) {
            $table->dropForeign('pedido_qr_code_clientes_empresa_id_foreign');
            $table->dropForeign('pedido_qr_code_clientes_pedido_id_foreign');
        });
    }
};
