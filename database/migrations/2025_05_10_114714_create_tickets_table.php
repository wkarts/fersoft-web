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
        Schema::create('tickets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('tickets_empresa_id_foreign');
            $table->enum('estado', ['aberto', 'respondida', 'finalizado']);
            $table->string('departamento', 50);
            $table->string('assunto', 100);
            $table->string('mensagem_finalizar', 200);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
