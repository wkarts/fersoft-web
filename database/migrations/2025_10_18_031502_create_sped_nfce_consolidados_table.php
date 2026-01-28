<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sped_nfce_consolidados', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedInteger('empresa_id');
            $table->unsignedInteger('usuario_id');
            $table->unsignedInteger('filial_id')->nullable();

            $table->string('cnpj', 14)->index();
            $table->date('periodo_inicio')->nullable();
            $table->date('periodo_fim')->nullable();

            // Arquivo original (upload)
            $table->string('input_filename');
            $table->string('input_path');        // relativo a /public
            $table->unsignedBigInteger('input_size')->nullable();
            $table->string('input_sha1', 40)->nullable();

            // Arquivo consolidado (gerado)
            $table->string('output_filename');
            $table->string('output_path');       // relativo a /public
            $table->unsignedBigInteger('output_size')->nullable();
            $table->string('output_sha1', 40)->nullable();

            // Métricas e contagens
            $table->unsignedBigInteger('total_linhas_original')->nullable();
            $table->unsignedBigInteger('total_linhas_consolidado')->nullable();
            $table->unsignedBigInteger('total_nfce_original')->nullable();
            $table->unsignedBigInteger('total_grupos_consolidados')->nullable(); // qtd de C190 agregados/dia

            // Status e metadados
            $table->string('status', 30)->default('concluido'); // concluido|erro
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('empresa_id')->references('id')->on('empresas');
            $table->foreign('filial_id')->references('id')->on('filials');
            $table->foreign('usuario_id')->references('id')->on('usuarios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sped_nfce_consolidados');
    }
};
