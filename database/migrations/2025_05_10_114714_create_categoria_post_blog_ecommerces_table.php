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
        Schema::create('categoria_post_blog_ecommerces', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome', 50);
            $table->unsignedInteger('empresa_id')->index('categoria_post_blog_ecommerces_empresa_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categoria_post_blog_ecommerces');
    }
};
