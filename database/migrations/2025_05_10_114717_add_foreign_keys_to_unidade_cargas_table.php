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
        Schema::table('unidade_cargas', function (Blueprint $table) {
            $table->foreign(['info_id'])->references(['id'])->on('info_descargas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unidade_cargas', function (Blueprint $table) {
            $table->dropForeign('unidade_cargas_info_id_foreign');
        });
    }
};
