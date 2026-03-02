<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ponto_escalas')) {
            Schema::create('ponto_escalas', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id')->index();
                $table->string('nome', 120);
                $table->string('tipo', 40)->default('personalizada');
                $table->date('vigencia_inicio')->nullable();
                $table->date('vigencia_fim')->nullable();
                $table->json('regras')->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ponto_escalas')) {
            Schema::drop('ponto_escalas');
        }
    }
};
