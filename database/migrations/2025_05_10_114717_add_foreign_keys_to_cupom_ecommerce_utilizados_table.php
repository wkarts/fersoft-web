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
        Schema::table('cupom_ecommerce_utilizados', function (Blueprint $table) {
            $table->foreign(['cliente_id'])->references(['id'])->on('cliente_ecommerces')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['cupom_id'])->references(['id'])->on('cupom_desconto_ecommerces')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cupom_ecommerce_utilizados', function (Blueprint $table) {
            $table->dropForeign('cupom_ecommerce_utilizados_cliente_id_foreign');
            $table->dropForeign('cupom_ecommerce_utilizados_cupom_id_foreign');
        });
    }
};
