<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('adp_cameras') && !Schema::hasColumn('adp_cameras', 'camera_access_mode')) {
            Schema::table('adp_cameras', function (Blueprint $table) {
                $table->string('camera_access_mode', 20)->default('all')->after('status')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('adp_cameras') && Schema::hasColumn('adp_cameras', 'camera_access_mode')) {
            Schema::table('adp_cameras', function (Blueprint $table) {
                $table->dropColumn('camera_access_mode');
            });
        }
    }
};
