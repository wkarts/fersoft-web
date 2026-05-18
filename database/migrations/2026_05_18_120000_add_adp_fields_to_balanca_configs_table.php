<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('balanca_configs')) {
            Schema::table('balanca_configs', function (Blueprint $table) {
                if (!Schema::hasColumn('balanca_configs', 'integrador')) {
                    $table->string('integrador', 30)->default('local')->after('backend_server_address');
                }
                if (!Schema::hasColumn('balanca_configs', 'adp_scale_uuid')) {
                    $table->string('adp_scale_uuid', 100)->nullable()->after('integrador');
                }
                if (!Schema::hasColumn('balanca_configs', 'usa_cameras')) {
                    $table->boolean('usa_cameras')->default(false)->after('adp_scale_uuid');
                }
                if (!Schema::hasColumn('balanca_configs', 'quantidade_cameras')) {
                    $table->unsignedSmallInteger('quantidade_cameras')->default(0)->after('usa_cameras');
                }
                if (!Schema::hasColumn('balanca_configs', 'adp_camera_uuids')) {
                    $table->json('adp_camera_uuids')->nullable()->after('quantidade_cameras');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('balanca_configs')) {
            Schema::table('balanca_configs', function (Blueprint $table) {
                foreach (['adp_camera_uuids', 'quantidade_cameras', 'usa_cameras', 'adp_scale_uuid', 'integrador'] as $column) {
                    if (Schema::hasColumn('balanca_configs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
