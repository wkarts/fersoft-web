<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ponto_jornadas')) {
            Schema::create('ponto_jornadas', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id')->index();
                $table->string('nome', 120);
                $table->json('regras_semana')->nullable();
                $table->integer('tolerancia_atraso_min')->default(0);
                $table->integer('tolerancia_extra_min')->default(0);
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ponto_jornadas')) {
            Schema::drop('ponto_jornadas');
        }
    }
};
