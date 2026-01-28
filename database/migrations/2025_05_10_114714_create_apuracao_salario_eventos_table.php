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
        Schema::create('apuracao_salario_eventos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('apuracao_id')->index('apuracao_salario_eventos_apuracao_id_foreign');
            $table->unsignedInteger('evento_id')->index('apuracao_salario_eventos_evento_id_foreign');
            $table->decimal('valor');
            $table->enum('metodo', ['informado', 'fixo']);
            $table->enum('condicao', ['soma', 'diminui']);
            $table->string('nome', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apuracao_salario_eventos');
    }
};
