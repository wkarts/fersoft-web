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
        Schema::create('lista_complemento_deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('categoria_id')->index('lista_complemento_deliveries_categoria_id_foreign');
            $table->unsignedInteger('complemento_id')->index('lista_complemento_deliveries_complemento_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lista_complemento_deliveries');
    }
};
