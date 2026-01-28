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
        Schema::create('avaliacao_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('avaliacao_deliveries_empresa_id_foreign');
            $table->unsignedInteger('pedido_id')->index('avaliacao_deliveries_pedido_id_foreign');
            $table->unsignedInteger('cliente_id')->index('avaliacao_deliveries_cliente_id_foreign');
            $table->string('descricao_pedido', 200);
            $table->string('observacao_cliente', 200);
            $table->integer('nota');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avaliacao_deliveries');
    }
};
