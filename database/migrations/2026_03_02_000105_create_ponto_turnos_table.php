<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ponto_turnos')) {
            Schema::create('ponto_turnos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id')->index();
                $table->string('nome', 120);
                $table->time('hora_inicio');
                $table->time('hora_fim');
                $table->integer('intervalo_minutos')->default(0);
                $table->boolean('cruza_meia_noite')->default(false);
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ponto_turnos')) {
            Schema::drop('ponto_turnos');
        }
    }
};
