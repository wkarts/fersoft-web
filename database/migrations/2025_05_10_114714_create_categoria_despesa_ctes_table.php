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
        Schema::create('categoria_despesa_ctes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id')->index('categoria_despesa_ctes_empresa_id_foreign');
            $table->string('nome', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categoria_despesa_ctes');
    }
};
