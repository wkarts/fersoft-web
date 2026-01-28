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
        Schema::create('relatorio_os', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('usuario_id')->index('relatorio_os_usuario_id_foreign');
            $table->unsignedInteger('ordem_servico_id')->index('relatorio_os_ordem_servico_id_foreign');
            $table->text('texto');
            $table->timestamp('data_registro')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relatorio_os');
    }
};
