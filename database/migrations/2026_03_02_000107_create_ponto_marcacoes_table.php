<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ponto_marcacoes')) {
            Schema::create('ponto_marcacoes', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id')->index();
                $table->unsignedInteger('funcionario_id')->index();
                $table->unsignedInteger('ponto_afd_registro_id')->nullable()->index();
                $table->dateTime('data_hora_marcacao')->index();
                $table->string('origem', 40)->default('afd');
                $table->string('status', 40)->default('bruta');
                $table->json('dados_brutos')->nullable();
                $table->boolean('inconsistente')->default(false);
                $table->text('observacoes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ponto_marcacoes')) {
            Schema::drop('ponto_marcacoes');
        }
    }
};
