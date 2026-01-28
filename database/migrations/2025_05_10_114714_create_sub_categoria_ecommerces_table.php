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
        Schema::create('sub_categoria_ecommerces', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('categoria_id')->index('sub_categoria_ecommerces_categoria_id_foreign');
            $table->string('nome', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_categoria_ecommerces');
    }
};
