<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tributacaos', function (Blueprint $table) {
            // Alíquotas padrão (percentuais)
            if (!Schema::hasColumn('tributacaos', 'aliq_cbs')) {
                $table->decimal('aliq_cbs', 15, 4)->default(0.9000)->after('regime');
            }

            if (!Schema::hasColumn('tributacaos', 'aliq_ibs_uf')) {
                $table->decimal('aliq_ibs_uf', 15, 4)->default(0.1000)->after('aliq_cbs');
            }

            if (!Schema::hasColumn('tributacaos', 'aliq_ibs_mun')) {
                $table->decimal('aliq_ibs_mun', 15, 4)->default(0.0500)->after('aliq_ibs_uf');
            }

            // CST / Classificação
            if (!Schema::hasColumn('tributacaos', 'cst_ibs_cbs')) {
                $table->string('cst_ibs_cbs', 8)->default('000')->after('aliq_ibs_mun');
            }

            // class trib com 6 dígitos
            if (!Schema::hasColumn('tributacaos', 'class_trib_ibs_cbs')) {
                $table->string('class_trib_ibs_cbs', 8)->default('000001')->after('cst_ibs_cbs');
            }

            // Reduções (%)
            if (!Schema::hasColumn('tributacaos', 'perc_red_ibs')) {
                $table->decimal('perc_red_ibs', 15, 4)->default(0.0000)->after('class_trib_ibs_cbs');
            }

            if (!Schema::hasColumn('tributacaos', 'perc_red_cbs')) {
                $table->decimal('perc_red_cbs', 15, 4)->default(0.0000)->after('perc_red_ibs');
            }
        });

        // Atualiza registros existentes (defaults não retroagem)
        DB::table('tributacaos')->whereNull('aliq_cbs')->update(['aliq_cbs' => 0.9000]);
        DB::table('tributacaos')->whereNull('aliq_ibs_uf')->update(['aliq_ibs_uf' => 0.1000]);
        DB::table('tributacaos')->whereNull('aliq_ibs_mun')->update(['aliq_ibs_mun' => 0.0500]);

        DB::table('tributacaos')->whereNull('cst_ibs_cbs')->update(['cst_ibs_cbs' => '000']);
        DB::table('tributacaos')->whereNull('class_trib_ibs_cbs')->update(['class_trib_ibs_cbs' => '000001']);

        DB::table('tributacaos')->whereNull('perc_red_ibs')->update(['perc_red_ibs' => 0.0000]);
        DB::table('tributacaos')->whereNull('perc_red_cbs')->update(['perc_red_cbs' => 0.0000]);
    }

    public function down(): void
    {
        Schema::table('tributacaos', function (Blueprint $table) {
            if (Schema::hasColumn('tributacaos', 'perc_red_cbs')) $table->dropColumn('perc_red_cbs');
            if (Schema::hasColumn('tributacaos', 'perc_red_ibs')) $table->dropColumn('perc_red_ibs');
            if (Schema::hasColumn('tributacaos', 'class_trib_ibs_cbs')) $table->dropColumn('class_trib_ibs_cbs');
            if (Schema::hasColumn('tributacaos', 'cst_ibs_cbs')) $table->dropColumn('cst_ibs_cbs');
            if (Schema::hasColumn('tributacaos', 'aliq_ibs_mun')) $table->dropColumn('aliq_ibs_mun');
            if (Schema::hasColumn('tributacaos', 'aliq_ibs_uf')) $table->dropColumn('aliq_ibs_uf');
            if (Schema::hasColumn('tributacaos', 'aliq_cbs')) $table->dropColumn('aliq_cbs');
        });
    }
};
