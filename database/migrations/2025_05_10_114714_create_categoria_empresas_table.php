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
        Schema::create('categoria_empresas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('categoria_empresas_empresa_id_foreign');
            $table->unsignedInteger('categoria_id')->index('categoria_empresas_categoria_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categoria_empresas');
    }
};
