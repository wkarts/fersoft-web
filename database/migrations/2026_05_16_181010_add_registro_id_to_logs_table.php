<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('logs')) {
            return;
        }

        Schema::table('logs', function (Blueprint $table) {
            if (!Schema::hasColumn('logs', 'registro_id')) {
                $table->unsignedBigInteger('registro_id')->nullable()->index()->after('modelo');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('logs') || !Schema::hasColumn('logs', 'registro_id')) {
            return;
        }

        Schema::table('logs', function (Blueprint $table) {
            $table->dropColumn('registro_id');
        });
    }
};
