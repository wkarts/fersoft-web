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
        Schema::table('sub_categorias', function (Blueprint $table) {
            $table->foreign(['categoria_id'])->references(['id'])->on('categorias')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_categorias', function (Blueprint $table) {
            $table->dropForeign('sub_categorias_categoria_id_foreign');
        });
    }
};
