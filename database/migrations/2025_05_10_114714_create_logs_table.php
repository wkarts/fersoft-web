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
        Schema::create('logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('empresa_id')->nullable()->index('logs_empresa_id_foreign');
            $table->unsignedInteger('usuario_id')->nullable()->index('logs_usuario_id_foreign');
            $table->unsignedInteger('filial_id')->nullable()->index('logs_filial_id_foreign');
            $table->string('acao');
            $table->string('modelo')->nullable();
            $table->json('dados_anteriores')->nullable();
            $table->json('dados_depois')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('token', 100)->unique();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
