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
        Schema::create('post_blog_ecommerces', function (Blueprint $table) {
            $table->increments('id');
            $table->string('titulo', 50);
            $table->string('img', 50);
            $table->string('tags', 100);
            $table->text('texto');
            $table->unsignedInteger('categoria_id')->index('post_blog_ecommerces_categoria_id_foreign');
            $table->unsignedInteger('autor_id')->index('post_blog_ecommerces_autor_id_foreign');
            $table->unsignedInteger('empresa_id')->index('post_blog_ecommerces_empresa_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_blog_ecommerces');
    }
};
