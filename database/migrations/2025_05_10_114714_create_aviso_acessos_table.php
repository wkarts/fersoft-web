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
        Schema::create('aviso_acessos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('aviso_acessos_empresa_id_foreign');
            $table->unsignedInteger('aviso_id')->index('aviso_acessos_aviso_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aviso_acessos');
    }
};
