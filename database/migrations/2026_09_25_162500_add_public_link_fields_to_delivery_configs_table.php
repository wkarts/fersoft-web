<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('delivery_configs', 'public_link_mode')) {
            Schema::table('delivery_configs', function (Blueprint $table) {
                $table->string('public_link_mode', 20)
                    ->default('auto')
                    ->after('api_token');
            });
        }

        if (!Schema::hasColumn('delivery_configs', 'public_link_value')) {
            Schema::table('delivery_configs', function (Blueprint $table) {
                $table->string('public_link_value', 100)
                    ->nullable()
                    ->unique('delivery_configs_public_link_value_unique')
                    ->after('public_link_mode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('delivery_configs', 'public_link_value')) {
            Schema::table('delivery_configs', function (Blueprint $table) {
                $table->dropUnique('delivery_configs_public_link_value_unique');
                $table->dropColumn('public_link_value');
            });
        }

        if (Schema::hasColumn('delivery_configs', 'public_link_mode')) {
            Schema::table('delivery_configs', function (Blueprint $table) {
                $table->dropColumn('public_link_mode');
            });
        }
    }
};
