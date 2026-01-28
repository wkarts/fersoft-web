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
        Schema::create('sped_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('sped_configs_empresa_id_foreign');
            $table->string('codigo_conta_analitica', 30)->nullable();
            $table->string('codigo_receita', 30)->nullable();
            $table->boolean('gerar_bloco_k')->default(false);
            $table->integer('layout_bloco_k')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sped_configs');
    }
};
