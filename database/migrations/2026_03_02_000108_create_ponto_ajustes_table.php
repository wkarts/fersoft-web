<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ponto_ajustes')) {
            Schema::create('ponto_ajustes', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id')->index();
                $table->unsignedInteger('funcionario_id')->index();
                $table->unsignedInteger('ponto_marcacao_id')->nullable()->index();
                $table->unsignedInteger('solicitado_por')->index();
                $table->string('tipo', 60);
                $table->string('status', 40)->default('pendente');
                $table->dateTime('data_hora_original')->nullable();
                $table->dateTime('data_hora_nova')->nullable();
                $table->text('justificativa');
                $table->string('anexo', 190)->nullable();
                $table->json('auditoria_antes')->nullable();
                $table->json('auditoria_depois')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ponto_ajustes')) {
            Schema::drop('ponto_ajustes');
        }
    }
};
