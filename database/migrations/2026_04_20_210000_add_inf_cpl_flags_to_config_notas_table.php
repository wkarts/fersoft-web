<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('config_notas')) {
            return;
        }

        Schema::table('config_notas', function (Blueprint $table) {
            if (!Schema::hasColumn('config_notas', 'exibir_ibscbs_inf_cpl')) {
                $table->boolean('exibir_ibscbs_inf_cpl')->default(false)->after('campo_obs_nfe');
            }

            if (!Schema::hasColumn('config_notas', 'exibir_piscofins_inf_cpl')) {
                $table->boolean('exibir_piscofins_inf_cpl')->default(false)->after('exibir_ibscbs_inf_cpl');
            }

            if (!Schema::hasColumn('config_notas', 'exibir_deolho_imposto_inf_cpl')) {
                $table->boolean('exibir_deolho_imposto_inf_cpl')->default(true)->after('exibir_piscofins_inf_cpl');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('config_notas')) {
            return;
        }

        Schema::table('config_notas', function (Blueprint $table) {
            if (Schema::hasColumn('config_notas', 'exibir_deolho_imposto_inf_cpl')) {
                $table->dropColumn('exibir_deolho_imposto_inf_cpl');
            }

            if (Schema::hasColumn('config_notas', 'exibir_piscofins_inf_cpl')) {
                $table->dropColumn('exibir_piscofins_inf_cpl');
            }

            if (Schema::hasColumn('config_notas', 'exibir_ibscbs_inf_cpl')) {
                $table->dropColumn('exibir_ibscbs_inf_cpl');
            }
        });
    }
};
