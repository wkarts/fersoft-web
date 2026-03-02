<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ponto_afd_registros')) {
            Schema::create('ponto_afd_registros', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id')->index();
                $table->unsignedInteger('ponto_afd_arquivo_id')->index();
                $table->unsignedInteger('funcionario_id')->nullable()->index();
                $table->unsignedInteger('numero_linha')->index();
                $table->string('tipo_registro', 10)->nullable();
                $table->string('pis', 20)->nullable()->index();
                $table->string('cpf', 14)->nullable()->index();
                $table->string('matricula', 60)->nullable()->index();
                $table->dateTime('data_hora_marcacao')->nullable()->index();
                $table->longText('linha_bruta');
                $table->json('dados_parseados')->nullable();
                $table->boolean('inconsistente')->default(false)->index();
                $table->string('motivo_inconsistencia', 190)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ponto_afd_registros')) {
            Schema::drop('ponto_afd_registros');
        }
    }
};
