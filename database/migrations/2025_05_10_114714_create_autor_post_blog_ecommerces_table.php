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
        Schema::create('autor_post_blog_ecommerces', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 50);
            $table->string('tipo', 50);
            $table->string('img', 40);
            $table->unsignedInteger('empresa_id')->index('autor_post_blog_ecommerces_empresa_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autor_post_blog_ecommerces');
    }
};
