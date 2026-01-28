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
        Schema::table('pedido_pag_seguros', function (Blueprint $table) {
            $table->foreign(['pedido_delivery_id'])->references(['id'])->on('pedido_deliveries')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_pag_seguros', function (Blueprint $table) {
            $table->dropForeign('pedido_pag_seguros_pedido_delivery_id_foreign');
        });
    }
};
