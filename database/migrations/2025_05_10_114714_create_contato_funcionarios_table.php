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
        Schema::create('contato_funcionarios', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 40);
            $table->string('telefone', 20);
            $table->unsignedInteger('funcionario_id')->index('contato_funcionarios_funcionario_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contato_funcionarios');
    }
};
