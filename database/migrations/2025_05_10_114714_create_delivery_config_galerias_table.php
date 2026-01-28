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
        Schema::create('delivery_config_galerias', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('config_id')->index('delivery_config_galerias_config_id_foreign');
            $table->string('imagem', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_config_galerias');
    }
};
