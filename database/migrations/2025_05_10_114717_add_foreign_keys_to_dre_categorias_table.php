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
        Schema::table('dre_categorias', function (Blueprint $table) {
            $table->foreign(['dre_id'])->references(['id'])->on('dres')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dre_categorias', function (Blueprint $table) {
            $table->dropForeign('dre_categorias_dre_id_foreign');
        });
    }
};
