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
        Schema::create('lacre_unidade_cargas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('info_id')->index('lacre_unidade_cargas_info_id_foreign');
            $table->string('numero', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lacre_unidade_cargas');
    }
};
