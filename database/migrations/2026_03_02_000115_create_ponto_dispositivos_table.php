<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ponto_dispositivos')) {
            Schema::create('ponto_dispositivos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id')->index();
                $table->unsignedInteger('funcionario_id')->nullable()->index();
                $table->string('uuid', 120)->index();
                $table->string('plataforma', 30)->nullable();
                $table->string('modelo', 80)->nullable();
                $table->string('versao_app', 30)->nullable();
                $table->boolean('ativo')->default(true)->index();
                $table->timestamp('ultimo_acesso_em')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ponto_dispositivos')) {
            Schema::drop('ponto_dispositivos');
        }
    }
};
