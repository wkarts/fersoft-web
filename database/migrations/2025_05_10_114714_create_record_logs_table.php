<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('record_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('tipo', ['criar', 'atualizar', 'deletar', 'emissao', 'cancelamento']);
            $table->unsignedInteger('usuario_log_id')->index('record_logs_usuario_log_id_foreign');
            $table->string('tabela', 40);
            $table->integer('registro_id');
            $table->unsignedInteger('empresa_id')->index('record_logs_empresa_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record_logs');
    }
};
