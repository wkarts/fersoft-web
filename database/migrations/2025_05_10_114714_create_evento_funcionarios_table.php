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
        Schema::create('evento_funcionarios', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('evento_id')->index('evento_funcionarios_evento_id_foreign');
            $table->unsignedInteger('funcionario_id')->index('evento_funcionarios_funcionario_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evento_funcionarios');
    }
};
