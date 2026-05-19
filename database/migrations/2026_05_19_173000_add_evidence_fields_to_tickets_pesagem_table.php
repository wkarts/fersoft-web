<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tickets_pesagem')) return;

        Schema::table('tickets_pesagem', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets_pesagem', 'balanca_evidence_json')) {
                $table->longText('balanca_evidence_json')->nullable();
            }
            if (!Schema::hasColumn('tickets_pesagem', 'camera_snapshots_json')) {
                $table->longText('camera_snapshots_json')->nullable();
            }
            if (!Schema::hasColumn('tickets_pesagem', 'camera_snapshot_at')) {
                $table->dateTime('camera_snapshot_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tickets_pesagem')) return;

        Schema::table('tickets_pesagem', function (Blueprint $table) {
            if (Schema::hasColumn('tickets_pesagem', 'camera_snapshot_at')) $table->dropColumn('camera_snapshot_at');
            if (Schema::hasColumn('tickets_pesagem', 'camera_snapshots_json')) $table->dropColumn('camera_snapshots_json');
            if (Schema::hasColumn('tickets_pesagem', 'balanca_evidence_json')) $table->dropColumn('balanca_evidence_json');
        });
    }
};
