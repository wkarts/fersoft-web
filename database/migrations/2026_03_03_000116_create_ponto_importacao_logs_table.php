<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ponto_importacao_logs')) {
            Schema::create('ponto_importacao_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id')->index();
                $table->unsignedInteger('ponto_afd_arquivo_id')->nullable()->index();
                $table->unsignedInteger('usuario_id')->nullable()->index();
                $table->string('run_id', 36)->index();
                $table->string('evento', 60)->index();
                $table->string('status', 20)->default('info')->index();
                $table->text('mensagem');
                $table->json('contexto')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ponto_importacao_logs')) {
            Schema::drop('ponto_importacao_logs');
        }
    }
};

