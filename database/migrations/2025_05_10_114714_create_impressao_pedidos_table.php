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
        Schema::create('impressao_pedidos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id')->index('impressao_pedidos_empresa_id_foreign');
            $table->integer('impressora_id');
            $table->integer('produto_id');
            $table->integer('pedido_id');
            $table->decimal('quantidade_item', 10);
            $table->decimal('valor_total', 10);
            $table->enum('tabela', ['pedidos', 'delivery'])->default('pedidos');
            $table->boolean('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impressao_pedidos');
    }
};
