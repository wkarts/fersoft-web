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
        Schema::table('percursos', function (Blueprint $table) {
            $table->foreign(['mdfe_id'])->references(['id'])->on('mdves')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('percursos', function (Blueprint $table) {
            $table->dropForeign('percursos_mdfe_id_foreign');
        });
    }
};
