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
        Schema::table('despesa_ctes', function (Blueprint $table) {
            $table->foreign(['categoria_id'])->references(['id'])->on('categoria_despesa_ctes')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['cte_id'])->references(['id'])->on('ctes')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('despesa_ctes', function (Blueprint $table) {
            $table->dropForeign('despesa_ctes_categoria_id_foreign');
            $table->dropForeign('despesa_ctes_cte_id_foreign');
        });
    }
};
