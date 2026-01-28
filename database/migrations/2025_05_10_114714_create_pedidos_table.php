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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('pedidos_empresa_id_foreign');
            $table->string('comanda', 12);
            $table->string('observacao', 200);
            $table->boolean('status');
            $table->unsignedInteger('mesa_id')->nullable()->index('pedidos_mesa_id_foreign');
            $table->unsignedInteger('cliente_id')->nullable()->index('pedidos_cliente_id_foreign');
            $table->unsignedInteger('bairro_id')->nullable()->index('pedidos_bairro_id_foreign');
            $table->string('nome', 50);
            $table->string('rua', 50);
            $table->string('numero', 10);
            $table->string('referencia', 30);
            $table->string('telefone', 15);
            $table->boolean('desativado');
            $table->string('referencia_cliete', 200)->default('');
            $table->boolean('mesa_ativa')->default(true);
            $table->boolean('fechar_mesa')->default(true);
            $table->timestamp('data_registro')->useCurrent();
            $table->boolean('catraca_aberta')->default(false);
            $table->boolean('catraca_fechada')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
