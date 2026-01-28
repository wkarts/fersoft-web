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
        Schema::create('item_pedido_ifoods', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pedido_id')->index('item_pedido_ifoods_pedido_id_foreign');
            $table->integer('produto_id');
            $table->string('nome_produto', 150);
            $table->string('image_url', 200);
            $table->string('unidade', 40);
            $table->decimal('valor_unitario', 10);
            $table->decimal('quantidade', 10);
            $table->decimal('total', 10);
            $table->decimal('valor_adicional', 10);
            $table->string('observacao', 200);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_pedido_ifoods');
    }
};
