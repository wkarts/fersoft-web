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
        Schema::create('cliente_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 30);
            $table->string('uid', 30);
            $table->string('sobre_nome', 30);
            $table->string('senha', 80);
            $table->string('celular', 15);
            $table->string('cpf', 15);
            $table->string('email', 50);
            $table->integer('token');
            $table->boolean('ativo');
            $table->string('foto', 30);
            $table->unsignedInteger('empresa_id')->nullable()->index('cliente_deliveries_empresa_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_deliveries');
    }
};
