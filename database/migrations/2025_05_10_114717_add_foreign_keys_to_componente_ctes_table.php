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
        Schema::table('componente_ctes', function (Blueprint $table) {
            $table->foreign(['cte_id'])->references(['id'])->on('ctes')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('componente_ctes', function (Blueprint $table) {
            $table->dropForeign('componente_ctes_cte_id_foreign');
        });
    }
};
