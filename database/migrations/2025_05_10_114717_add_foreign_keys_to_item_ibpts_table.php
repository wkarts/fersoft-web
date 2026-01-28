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
        Schema::table('item_ibpts', function (Blueprint $table) {
            $table->foreign(['ibpt_id'])->references(['id'])->on('ibpts')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_ibpts', function (Blueprint $table) {
            $table->dropForeign('item_ibpts_ibpt_id_foreign');
        });
    }
};
