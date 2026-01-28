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
        Schema::create('adicional_item_pedido_ifoods', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('item_pedido_id')->index('adicional_item_pedido_ifoods_item_pedido_id_foreign');
            $table->string('nome', 100);
            $table->string('unidade', 10);
            $table->decimal('quantidade', 10);
            $table->decimal('valor_unitario', 10);
            $table->decimal('total', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adicional_item_pedido_ifoods');
    }
};
