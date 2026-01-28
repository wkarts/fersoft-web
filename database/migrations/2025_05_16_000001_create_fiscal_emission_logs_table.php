<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_emission_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->unsignedBigInteger('filial_id')->nullable();
            $table->string('document_type', 20)->default('NFe');
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('document_reference')->nullable();
            $table->unsignedInteger('numero')->nullable();
            $table->string('serie', 10)->nullable();
            $table->string('chave', 60)->nullable();
            $table->unsignedTinyInteger('ambiente')->nullable();
            $table->string('status', 40)->default('enviado');
            $table->string('retorno_codigo', 10)->nullable();
            $table->text('retorno_mensagem')->nullable();
            $table->string('recibo', 30)->nullable();
            $table->string('xml_envio_path')->nullable();
            $table->string('xml_retorno_path')->nullable();
            $table->longText('retorno_payload')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'document_type']);
            $table->index(['document_type', 'document_id']);
            $table->index(['chave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_emission_logs');
    }
};
