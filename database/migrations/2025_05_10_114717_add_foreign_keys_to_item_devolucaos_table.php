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
        Schema::table('item_devolucaos', function (Blueprint $table) {
            $table->foreign(['devolucao_id'])->references(['id'])->on('devolucaos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_devolucaos', function (Blueprint $table) {
            $table->dropForeign('item_devolucaos_devolucao_id_foreign');
        });
    }
};
