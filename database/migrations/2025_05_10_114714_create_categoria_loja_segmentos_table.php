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
        Schema::create('categoria_loja_segmentos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('categoria_id')->index('categoria_loja_segmentos_categoria_id_foreign');
            $table->unsignedInteger('loja_id')->index('categoria_loja_segmentos_loja_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categoria_loja_segmentos');
    }
};
