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
        Schema::table('item_i_b_t_es', function (Blueprint $table) {
            $table->foreign(['ibte_id'])->references(['id'])->on('i_b_p_ts')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_i_b_t_es', function (Blueprint $table) {
            $table->dropForeign('item_i_b_t_es_ibte_id_foreign');
        });
    }
};
