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
        Schema::table('post_blog_ecommerces', function (Blueprint $table) {
            $table->foreign(['autor_id'])->references(['id'])->on('autor_post_blog_ecommerces')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['categoria_id'])->references(['id'])->on('categoria_post_blog_ecommerces')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_blog_ecommerces', function (Blueprint $table) {
            $table->dropForeign('post_blog_ecommerces_autor_id_foreign');
            $table->dropForeign('post_blog_ecommerces_categoria_id_foreign');
            $table->dropForeign('post_blog_ecommerces_empresa_id_foreign');
        });
    }
};
