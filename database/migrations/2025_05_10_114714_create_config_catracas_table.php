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
        Schema::create('config_catracas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('config_catracas_empresa_id_foreign');
            $table->integer('usuario_id');
            $table->integer('segundos_requisicao');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_catracas');
    }
};
