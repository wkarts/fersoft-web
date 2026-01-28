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
        Schema::create('tela_pedidos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 30);
            $table->integer('alerta_amarelo')->default(0);
            $table->integer('alerta_vermelho')->default(0);
            $table->unsignedInteger('empresa_id')->index('tela_pedidos_empresa_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tela_pedidos');
    }
};
