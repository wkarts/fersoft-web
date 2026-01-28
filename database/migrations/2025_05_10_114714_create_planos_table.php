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
        Schema::create('planos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 40);
            $table->decimal('valor', 6);
            $table->integer('maximo_clientes');
            $table->integer('maximo_produtos');
            $table->integer('maximo_fornecedores');
            $table->integer('maximo_nfes');
            $table->integer('maximo_nfces');
            $table->integer('maximo_cte');
            $table->integer('maximo_mdfe');
            $table->integer('maximo_evento');
            $table->integer('maximo_usuario');
            $table->integer('armazenamento');
            $table->integer('maximo_usuario_simultaneo');
            $table->boolean('delivery');
            $table->integer('perfil_id');
            $table->integer('intervalo_dias');
            $table->text('descricao');
            $table->string('img', 100);
            $table->boolean('visivel')->default(true);
            $table->boolean('api_sieg')->default(false);
            $table->boolean('visivel_representante')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planos');
    }
};
