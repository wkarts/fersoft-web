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
        Schema::create('pedido_pag_seguros', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pedido_delivery_id')->index('pedido_pag_seguros_pedido_delivery_id_foreign');
            $table->string('numero_cartao', 20);
            $table->string('cpf', 15);
            $table->string('nome_impresso', 25);
            $table->string('codigo_transacao', 45);
            $table->string('referencia', 35);
            $table->string('bandeira', 10);
            $table->integer('parcelas');
            $table->integer('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_pag_seguros');
    }
};
