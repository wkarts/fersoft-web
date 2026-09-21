<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('contrato_eng_funcionarios')) {
            return;
        }

        Schema::create('contrato_eng_funcionarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contrato_eng_id');
            $table->unsignedInteger('funcionario_id');
            $table->date('data_alocacao')->nullable();
            $table->date('data_desalocacao')->nullable();
            $table->string('status', 30)->default('Ativo');
            $table->timestamps();

            $table->index(['contrato_eng_id', 'status'], 'contrato_eng_func_status_idx');
            $table->index('funcionario_id', 'contrato_eng_func_funcionario_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contrato_eng_funcionarios');
    }
};
