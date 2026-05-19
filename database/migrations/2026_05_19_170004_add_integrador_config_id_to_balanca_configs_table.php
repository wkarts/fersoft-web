<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('balanca_configs')) return;

        Schema::table('balanca_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('balanca_configs', 'integrador_config_id')) {
                $table->unsignedBigInteger('integrador_config_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('balanca_configs')) return;

        Schema::table('balanca_configs', function (Blueprint $table) {
            if (Schema::hasColumn('balanca_configs', 'integrador_config_id')) {
                $table->dropColumn('integrador_config_id');
            }
        });
    }
};
