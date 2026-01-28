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
        Schema::create('item_pedido_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produto_id')->index('item_pedido_deliveries_produto_id_foreign');
            $table->unsignedInteger('pedido_id')->index('item_pedido_deliveries_pedido_id_foreign');
            $table->boolean('status');
            $table->decimal('quantidade');
            $table->decimal('valor', 12);
            $table->string('observacao', 50);
            $table->unsignedInteger('tamanho_id')->nullable()->index('item_pedido_deliveries_tamanho_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_pedido_deliveries');
    }
};
