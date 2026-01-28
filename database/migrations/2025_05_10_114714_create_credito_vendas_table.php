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
        Schema::create('credito_vendas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('credito_vendas_empresa_id_foreign');
            $table->unsignedInteger('venda_id')->index('credito_vendas_venda_id_foreign');
            $table->unsignedInteger('cliente_id')->index('credito_vendas_cliente_id_foreign');
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credito_vendas');
    }
};
