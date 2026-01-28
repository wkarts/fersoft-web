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
        Schema::create('cash_back_clientes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id')->index('cash_back_clientes_empresa_id_foreign');
            $table->unsignedInteger('cliente_id')->index('cash_back_clientes_cliente_id_foreign');
            $table->enum('tipo', ['venda', 'pdv']);
            $table->integer('venda_id');
            $table->decimal('valor_venda', 16, 7);
            $table->decimal('valor_credito', 16, 7);
            $table->decimal('valor_percentual', 5);
            $table->date('data_expiracao');
            $table->boolean('status')->default(true);
            $table->boolean('status_mensagem_5_dias')->default(false);
            $table->boolean('status_mensagem_1_dia')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_back_clientes');
    }
};
