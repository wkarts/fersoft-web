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
        Schema::create('funcionario_eventos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('evento_id')->index('funcionario_eventos_evento_id_foreign');
            $table->unsignedInteger('funcionario_id')->index('funcionario_eventos_funcionario_id_foreign');
            $table->enum('condicao', ['soma', 'diminui']);
            $table->enum('metodo', ['informado', 'fixo']);
            $table->decimal('valor', 10);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funcionario_eventos');
    }
};
