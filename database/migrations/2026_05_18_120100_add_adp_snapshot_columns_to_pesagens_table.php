<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pesagens')) {
            Schema::table('pesagens', function (Blueprint $table) {
                if (!Schema::hasColumn('pesagens', 'camera_snapshots')) {
                    $table->json('camera_snapshots')->nullable()->after('observacoes');
                }
                if (!Schema::hasColumn('pesagens', 'camera_snapshot_at')) {
                    $table->timestamp('camera_snapshot_at')->nullable()->after('camera_snapshots');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pesagens')) {
            Schema::table('pesagens', function (Blueprint $table) {
                foreach (['camera_snapshot_at', 'camera_snapshots'] as $column) {
                    if (Schema::hasColumn('pesagens', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
