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
        Schema::table('produtos', function (Blueprint $table) {
            $table->foreign(['categoria_id'])->references(['id'])->on('categorias')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['empresa_id'])->references(['id'])->on('empresas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['marca_id'])->references(['id'])->on('marcas')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['sub_categoria_id'])->references(['id'])->on('sub_categorias')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropForeign('produtos_categoria_id_foreign');
            $table->dropForeign('produtos_empresa_id_foreign');
            $table->dropForeign('produtos_marca_id_foreign');
            $table->dropForeign('produtos_sub_categoria_id_foreign');
        });
    }
};
