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
        Schema::create('item_venda_caixas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venda_caixa_id')->index('item_venda_caixas_venda_caixa_id_foreign');
            $table->unsignedInteger('produto_id')->index('item_venda_caixas_produto_id_foreign');
            $table->unsignedInteger('item_pedido_id')->nullable()->index('item_venda_caixas_item_pedido_id_foreign');
            $table->decimal('quantidade', 16, 7);
            $table->decimal('valor', 16, 7);
            $table->decimal('valor_custo', 16, 7)->default(0);
            $table->decimal('valor_comissao_assessor', 10)->default(0);
            $table->string('observacao', 80);
            $table->integer('cfop')->default(0);
            $table->boolean('devolvido')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_venda_caixas');
    }
};
