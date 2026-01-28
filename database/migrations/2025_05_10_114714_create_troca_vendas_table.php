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
        Schema::create('troca_vendas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('venda_id');
            $table->enum('tipo', ['pedido', 'pdv']);
            $table->decimal('valor_total', 16, 7);
            $table->decimal('valor_credito', 16, 7);
            $table->unsignedInteger('empresa_id')->index('troca_vendas_empresa_id_foreign');
            $table->unsignedInteger('cliente_id')->nullable()->index('troca_vendas_cliente_id_foreign');
            $table->unsignedInteger('usuario_id')->index('troca_vendas_usuario_id_foreign');
            $table->date('data_venda');
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('troca_vendas');
    }
};
