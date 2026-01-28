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
        Schema::create('funcionario_os', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('usuario_id')->index('funcionario_os_usuario_id_foreign');
            $table->unsignedInteger('ordem_servico_id')->index('funcionario_os_ordem_servico_id_foreign');
            $table->unsignedInteger('funcionario_id')->index('funcionario_os_funcionario_id_foreign');
            $table->string('funcao');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funcionario_os');
    }
};
