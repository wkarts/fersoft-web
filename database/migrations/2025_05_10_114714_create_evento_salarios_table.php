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
        Schema::create('evento_salarios', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 50);
            $table->string('tipo_valor', 50);
            $table->enum('tipo', ['semanal', 'mensal', 'anual']);
            $table->enum('metodo', ['informado', 'fixo']);
            $table->enum('condicao', ['soma', 'diminui']);
            $table->boolean('ativo')->default(true);
            $table->unsignedInteger('empresa_id')->index('evento_salarios_empresa_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evento_salarios');
    }
};
