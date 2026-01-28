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
        Schema::create('item_vendas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('venda_id')->index('item_vendas_venda_id_foreign');
            $table->unsignedInteger('produto_id')->index('item_vendas_produto_id_foreign');
            $table->decimal('quantidade', 16, 7);
            $table->decimal('quantidade_dimensao', 10, 3)->default(1);
            $table->decimal('valor', 16, 7);
            $table->decimal('valor_custo', 16, 7)->default(0);
            $table->integer('cfop')->default(0);
            $table->decimal('altura', 10)->nullable();
            $table->decimal('largura', 10)->nullable();
            $table->decimal('profundidade', 10)->nullable();
            $table->decimal('acrescimo_perca', 10)->nullable();
            $table->decimal('esquerda', 10)->nullable();
            $table->decimal('direita', 10)->nullable();
            $table->decimal('inferior', 10)->nullable();
            $table->decimal('superior', 10)->nullable();
            $table->boolean('devolvido')->default(false);
            $table->string('x_pedido', 30);
            $table->string('num_item_pedido', 30);
            $table->string('produto_nome', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_vendas');
    }
};
