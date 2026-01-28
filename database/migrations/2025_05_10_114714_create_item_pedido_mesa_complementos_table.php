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
        Schema::create('item_pedido_mesa_complementos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('item_pedido_id')->index('item_pedido_mesa_complementos_item_pedido_id_foreign');
            $table->unsignedInteger('complemento_id')->index('item_pedido_mesa_complementos_complemento_id_foreign');
            $table->integer('quantidade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_pedido_mesa_complementos');
    }
};
