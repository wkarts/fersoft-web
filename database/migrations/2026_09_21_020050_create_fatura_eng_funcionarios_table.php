<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('fatura_eng_funcionarios')) {
            return;
        }

        Schema::create('fatura_eng_funcionarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fatura_eng_id');
            $table->unsignedInteger('funcionario_id');
            $table->string('funcao', 191)->nullable();
            $table->decimal('diarias', 12, 2)->default(1);
            $table->decimal('valor_diaria', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->timestamps();

            $table->index('fatura_eng_id', 'fatura_eng_func_fatura_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('fatura_eng_funcionarios');
    }
};
