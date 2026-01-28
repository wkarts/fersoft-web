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
        Schema::create('plano_empresa_representantes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('plano_empresa_representantes_empresa_id_foreign');
            $table->unsignedInteger('plano_id')->index('plano_empresa_representantes_plano_id_foreign');
            $table->unsignedInteger('representante_id')->index('plano_empresa_representantes_representante_id_foreign');
            $table->date('expiracao');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plano_empresa_representantes');
    }
};
