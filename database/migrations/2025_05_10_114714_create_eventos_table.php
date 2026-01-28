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
        Schema::create('eventos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 100);
            $table->string('descricao', 200);
            $table->string('logradouro', 80);
            $table->string('numero', 10);
            $table->string('bairro', 30);
            $table->string('cidade', 50);
            $table->boolean('status')->default(true);
            $table->date('inicio');
            $table->date('fim');
            $table->unsignedInteger('empresa_id')->index('eventos_empresa_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
