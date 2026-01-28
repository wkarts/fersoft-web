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
        Schema::table('remessa_nfe_faturas', function (Blueprint $table) {
            $table->foreign(['remessa_id'])->references(['id'])->on('remessa_nves')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('remessa_nfe_faturas', function (Blueprint $table) {
            $table->dropForeign('remessa_nfe_faturas_remessa_id_foreign');
        });
    }
};
