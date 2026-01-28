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
        Schema::create('imagem_produto_ecommerces', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('produto_id')->index('imagem_produto_ecommerces_produto_id_foreign');
            $table->string('img', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imagem_produto_ecommerces');
    }
};
