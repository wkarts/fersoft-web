<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('ponto_afd_arquivos')) {
            Schema::create('ponto_afd_arquivos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('empresa_id')->index();
                $table->unsignedInteger('ponto_relogio_id')->nullable()->index();
                $table->string('nome_original', 190);
                $table->string('hash_arquivo', 64)->index();
                $table->string('caminho_arquivo', 255);
                $table->unsignedInteger('total_linhas')->default(0);
                $table->unsignedInteger('linhas_validas')->default(0);
                $table->unsignedInteger('linhas_invalidas')->default(0);
                $table->string('status', 40)->default('pendente');
                $table->text('erro_processamento')->nullable();
                $table->unsignedInteger('usuario_id')->nullable()->index();
                $table->timestamp('processado_em')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ponto_afd_arquivos')) {
            Schema::drop('ponto_afd_arquivos');
        }
    }
};
