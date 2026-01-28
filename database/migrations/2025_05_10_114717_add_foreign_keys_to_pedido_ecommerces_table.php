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
        Schema::table('pedido_ecommerces', function (Blueprint $table) {
            $table->foreign(['cliente_id'])->references(['id'])->on('cliente_ecommerces')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['endereco_id'])->references(['id'])->on('endereco_ecommerces')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_ecommerces', function (Blueprint $table) {
            $table->dropForeign('pedido_ecommerces_cliente_id_foreign');
            $table->dropForeign('pedido_ecommerces_empresa_id_foreign');
            $table->dropForeign('pedido_ecommerces_endereco_id_foreign');
        });
    }
};
