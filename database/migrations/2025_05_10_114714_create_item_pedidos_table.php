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
        Schema::create('item_pedidos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produto_id')->index('item_pedidos_produto_id_foreign');
            $table->unsignedInteger('pedido_id')->index('item_pedidos_pedido_id_foreign');
            $table->unsignedInteger('tamanho_pizza_id')->nullable()->index('item_pedidos_tamanho_pizza_id_foreign');
            $table->string('observacao', 40);
            $table->boolean('status');
            $table->decimal('quantidade');
            $table->decimal('valor', 10);
            $table->boolean('impresso');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_pedidos');
    }
};
