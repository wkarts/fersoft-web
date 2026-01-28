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
        Schema::table('delivery_config_galerias', function (Blueprint $table) {
            $table->foreign(['config_id'])->references(['id'])->on('delivery_configs')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_config_galerias', function (Blueprint $table) {
            $table->dropForeign('delivery_config_galerias_config_id_foreign');
        });
    }
};
