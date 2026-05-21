<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('config_notas', function (Blueprint $table) {
            if (!Schema::hasColumn('config_notas', 'pesagem_storage_provider')) {
                $table->string('pesagem_storage_provider', 40)->nullable()->after('pesagem_snapshot_base_path');
            }

            if (!Schema::hasColumn('config_notas', 'pesagem_storage_config_json')) {
                $table->longText('pesagem_storage_config_json')->nullable()->after('pesagem_storage_provider');
            }
        });
    }

    public function down(): void
    {
        Schema::table('config_notas', function (Blueprint $table) {
            foreach (['pesagem_storage_config_json', 'pesagem_storage_provider'] as $column) {
                if (Schema::hasColumn('config_notas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
