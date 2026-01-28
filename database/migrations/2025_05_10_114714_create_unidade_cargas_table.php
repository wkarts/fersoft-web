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
        Schema::create('unidade_cargas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('info_id')->index('unidade_cargas_info_id_foreign');
            $table->string('id_unidade_carga', 20);
            $table->decimal('quantidade_rateio', 5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidade_cargas');
    }
};
