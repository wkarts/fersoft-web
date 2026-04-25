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
        if (!Schema::hasTable('conta_empresas')) {
            return;
        }

        if (!Schema::hasColumn('conta_empresas', 'exibir_dashboard_analitico')) {
            Schema::table('conta_empresas', function (Blueprint $table) {
                $table->boolean('exibir_dashboard_analitico')
                    ->default(false)
                    ->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('conta_empresas')) {
            return;
        }

        if (Schema::hasColumn('conta_empresas', 'exibir_dashboard_analitico')) {
            Schema::table('conta_empresas', function (Blueprint $table) {
                $table->dropColumn('exibir_dashboard_analitico');
            });
        }
    }
};
