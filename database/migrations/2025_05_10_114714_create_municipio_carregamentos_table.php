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
        Schema::create('municipio_carregamentos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('mdfe_id')->index('municipio_carregamentos_mdfe_id_foreign');
            $table->unsignedInteger('cidade_id')->index('municipio_carregamentos_cidade_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('municipio_carregamentos');
    }
};
