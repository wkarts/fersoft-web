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
        Schema::table('pedido_motoboys', function (Blueprint $table) {
            $table->foreign(['motoboy_id'])->references(['id'])->on('motoboys')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['pedido_id'])->references(['id'])->on('pedido_deliveries')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_motoboys', function (Blueprint $table) {
            $table->dropForeign('pedido_motoboys_motoboy_id_foreign');
            $table->dropForeign('pedido_motoboys_pedido_id_foreign');
        });
    }
};
