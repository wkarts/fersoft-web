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
        Schema::create('atividade_servicos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('servico_id')->index('atividade_servicos_servico_id_foreign');
            $table->unsignedInteger('atividade_id')->index('atividade_servicos_atividade_id_foreign');
            $table->integer('quantidade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atividade_servicos');
    }
};
