<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets_pesagem', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets_pesagem', 'imagens_persistidas_json')) {
                $table->longText('imagens_persistidas_json')->nullable()->after('camera_snapshot_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets_pesagem', function (Blueprint $table) {
            if (Schema::hasColumn('tickets_pesagem', 'imagens_persistidas_json')) {
                $table->dropColumn('imagens_persistidas_json');
            }
        });
    }
};
