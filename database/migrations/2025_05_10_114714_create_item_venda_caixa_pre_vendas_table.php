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
        Schema::create('item_venda_caixa_pre_vendas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venda_caixa_prevenda_id')->index('item_venda_caixa_pre_vendas_venda_caixa_prevenda_id_foreign');
            $table->unsignedInteger('produto_id')->index('item_venda_caixa_pre_vendas_produto_id_foreign');
            $table->unsignedInteger('item_pedido_id')->nullable()->index('item_venda_caixa_pre_vendas_item_pedido_id_foreign');
            $table->decimal('quantidade', 10, 3);
            $table->decimal('valor', 16, 7);
            $table->string('observacao', 80);
            $table->integer('cfop')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_venda_caixa_pre_vendas');
    }
};
