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
        Schema::create('representante_empresas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('representante_id')->index('representante_empresas_representante_id_foreign');
            $table->unsignedInteger('empresa_id')->index('representante_empresas_empresa_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('representante_empresas');
    }
};
