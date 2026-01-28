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
        Schema::create('nuvem_shop_item_pedidos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pedido_id')->index('nuvem_shop_item_pedidos_pedido_id_foreign');
            $table->unsignedInteger('produto_id')->index('nuvem_shop_item_pedidos_produto_id_foreign');
            $table->decimal('quantidade');
            $table->decimal('valor', 10);
            $table->string('nome', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nuvem_shop_item_pedidos');
    }
};
