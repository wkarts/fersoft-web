<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ponto_relogios')) {
            Schema::create('ponto_relogios', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id')->index();
                $table->string('nome', 100);
                $table->string('fabricante', 100)->nullable();
                $table->string('modelo', 100)->nullable();
                $table->string('numero_serie', 100)->nullable()->index();
                $table->string('local', 120)->nullable();
                $table->string('tipo_origem', 50)->default('REP');
                $table->boolean('ativo')->default(true);
                $table->json('parametros_importacao')->nullable();
                $table->text('observacoes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ponto_relogios')) {
            Schema::drop('ponto_relogios');
        }
    }
};
