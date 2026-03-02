<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ponto_ocorrencias')) {
            Schema::create('ponto_ocorrencias', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id')->index();
                $table->unsignedInteger('funcionario_id')->index();
                $table->unsignedInteger('ponto_marcacao_id')->nullable()->index();
                $table->date('data_referencia')->index();
                $table->string('tipo', 60)->index();
                $table->string('origem', 60)->default('tratamento_jornada')->index();
                $table->integer('minutos')->default(0);
                $table->text('descricao')->nullable();
                $table->json('dados')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ponto_ocorrencias')) {
            Schema::drop('ponto_ocorrencias');
        }
    }
};
