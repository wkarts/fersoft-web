<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venda_caixas', function (Blueprint $table) {
            if (!Schema::hasColumn('venda_caixas', 'total_qbcmono'))       $table->decimal('total_qbcmono', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_icmsmono'))      $table->decimal('total_icmsmono', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_qbcmonoreten'))  $table->decimal('total_qbcmonoreten', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_icmsmonoreten')) $table->decimal('total_icmsmonoreten', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_qbcmonoret'))    $table->decimal('total_qbcmonoret', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_icmsmonoret'))   $table->decimal('total_icmsmonoret', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'destino_operacao'))    $table->integer('destino_operacao')->nullable();

            if (!Schema::hasColumn('venda_caixas', 'ret_bc_irrf'))         $table->decimal('ret_bc_irrf', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'ret_aliq_irrf'))       $table->decimal('ret_aliq_irrf', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'ret_virrf'))           $table->decimal('ret_virrf', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'ret_bc_pis'))          $table->decimal('ret_bc_pis', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'ret_aliq_pis'))        $table->decimal('ret_aliq_pis', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'ret_vpis'))            $table->decimal('ret_vpis', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'ret_bc_cofins'))       $table->decimal('ret_bc_cofins', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'ret_aliq_cofins'))     $table->decimal('ret_aliq_cofins', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'ret_vcofins'))         $table->decimal('ret_vcofins', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'ret_bc_csll'))         $table->decimal('ret_bc_csll', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'ret_aliq_csll'))       $table->decimal('ret_aliq_csll', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'ret_vcsll'))           $table->decimal('ret_vcsll', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'flag_normativa_irrf')) $table->string('flag_normativa_irrf', 1)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'total_ipi_devolvido')) $table->decimal('total_ipi_devolvido', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'fk_pes_retirada'))     $table->integer('fk_pes_retirada')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'flag_end_entrega'))    $table->string('flag_end_entrega', 1)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_ii'))            $table->decimal('total_ii', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'nreceituario'))        $table->string('nreceituario', 30)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'cpfresptec'))          $table->string('cpfresptec', 14)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'tipo_guia_transito'))  $table->integer('tipo_guia_transito')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'uf_guia_transito'))    $table->string('uf_guia_transito', 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'serie_guia_transito')) $table->string('serie_guia_transito', 10)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'num_guia_transito'))   $table->string('num_guia_transito', 10)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'total_is'))                          $table->decimal('total_is', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_bc_ibs_cbs'))                  $table->decimal('total_bc_ibs_cbs', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_ibs'))                         $table->decimal('total_ibs', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_ibs_cred_pres'))               $table->decimal('total_ibs_cred_pres', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_ibs_cred_pres_cond_sus'))      $table->decimal('total_ibs_cred_pres_cond_sus', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'total_ibs_uf_dif'))                  $table->decimal('total_ibs_uf_dif', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_ibs_uf_dev_trib'))             $table->decimal('total_ibs_uf_dev_trib', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_ibs_uf'))                      $table->decimal('total_ibs_uf', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'total_ibs_mun_dif'))                 $table->decimal('total_ibs_mun_dif', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_ibs_mun_dev_trib'))            $table->decimal('total_ibs_mun_dev_trib', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_ibs_mun'))                     $table->decimal('total_ibs_mun', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'total_cbs_dif'))                     $table->decimal('total_cbs_dif', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_cbs_dev_trib'))                $table->decimal('total_cbs_dev_trib', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_cbs'))                         $table->decimal('total_cbs', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_cbs_cred_pres'))               $table->decimal('total_cbs_cred_pres', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_cbs_cred_pres_cond_sus'))      $table->decimal('total_cbs_cred_pres_cond_sus', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'total_ibs_mono'))                    $table->decimal('total_ibs_mono', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_cbs_mono'))                    $table->decimal('total_cbs_mono', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_ibs_mono_reten'))              $table->decimal('total_ibs_mono_reten', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_cbs_mono_reten'))              $table->decimal('total_cbs_mono_reten', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_ibs_mono_ret'))                $table->decimal('total_ibs_mono_ret', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_cbs_mono_ret'))                $table->decimal('total_cbs_mono_ret', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'total_nf_ibc_cbs_is'))               $table->decimal('total_nf_ibc_cbs_is', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'total_ibs_cbs'))                     $table->decimal('total_ibs_cbs', 15, 2)->nullable();

            if (!Schema::hasColumn('venda_caixas', 'tipo_nfcredito'))                    $table->integer('tipo_nfcredito')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'tipo_nfdebito'))                     $table->integer('tipo_nfdebito')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'tipo_entegov'))                      $table->integer('tipo_entegov')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'perc_redutor_gov'))                  $table->decimal('perc_redutor_gov', 15, 2)->nullable();
            if (!Schema::hasColumn('venda_caixas', 'tipo_opergov'))                      $table->integer('tipo_opergov')->nullable();

            if (!Schema::hasColumn('venda_caixas', 'eloquent_uuid'))     $table->uuid('eloquent_uuid')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'created_at'))        $table->timestamp('created_at')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'updated_at'))        $table->timestamp('updated_at')->nullable();
            if (!Schema::hasColumn('venda_caixas', 'deleted_at'))        $table->timestamp('deleted_at')->nullable();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('DROP TRIGGER IF EXISTS venda_caixas_BI0');
            DB::unprepared("
                CREATE TRIGGER venda_caixas_BI0
                BEFORE INSERT ON venda_caixas
                FOR EACH ROW
                BEGIN
                    IF (NEW.ELOQUENT_UUID IS NULL) THEN SET NEW.ELOQUENT_UUID = UUID(); END IF;
                    IF (NEW.CREATED_AT   IS NULL) THEN SET NEW.CREATED_AT   = CURRENT_TIMESTAMP; END IF;
                END
            ");

            DB::statement('DROP TRIGGER IF EXISTS venda_caixas_BU0');
            DB::unprepared("
                CREATE TRIGGER venda_caixas_BU0
                BEFORE UPDATE ON venda_caixas
                FOR EACH ROW
                BEGIN
                    IF (NEW.ELOQUENT_UUID IS NULL) THEN SET NEW.ELOQUENT_UUID = UUID(); END IF;
                    SET NEW.UPDATED_AT = CURRENT_TIMESTAMP;
                END
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('DROP TRIGGER IF EXISTS venda_caixas_BI0');
            DB::statement('DROP TRIGGER IF EXISTS venda_caixas_BU0');
        }

        Schema::table('venda_caixas', function (Blueprint $table) {
            $cols = [
                'TOTAL_QBCMONO','TOTAL_ICMSMONO','TOTAL_QBCMONORETEN','TOTAL_ICMSMONORETEN','TOTAL_QBCMONORET','TOTAL_ICMSMONORET',
                'DESTINO_OPERACAO',
                'RET_BC_IRRF','RET_ALIQ_IRRF','RET_VIRRF',
                'RET_BC_PIS','RET_ALIQ_PIS','RET_VPIS',
                'RET_BC_COFINS','RET_ALIQ_COFINS','RET_VCOFINS',
                'RET_BC_CSLL','RET_ALIQ_CSLL','RET_VCSLL',
                'FLAG_NORMATIVA_IRRF',
                'TOTAL_IPI_DEVOLVIDO','FK_PES_RETIRADA','FLAG_END_ENTREGA','TOTAL_II',
                'NRECEITUARIO','CPFRESPTEC',
                'TIPO_GUIA_TRANSITO','UF_GUIA_TRANSITO','SERIE_GUIA_TRANSITO','NUM_GUIA_TRANSITO',
                'TOTAL_IS','TOTAL_BC_IBS_CBS','TOTAL_IBS','TOTAL_IBS_CRED_PRES','TOTAL_IBS_CRED_PRES_COND_SUS',
                'TOTAL_IBS_UF_DIF','TOTAL_IBS_UF_DEV_TRIB','TOTAL_IBS_UF',
                'TOTAL_IBS_MUN_DIF','TOTAL_IBS_MUN_DEV_TRIB','TOTAL_IBS_MUN',
                'TOTAL_CBS_DIF','TOTAL_CBS_DEV_TRIB','TOTAL_CBS','TOTAL_CBS_CRED_PRES','TOTAL_CBS_CRED_PRES_COND_SUS',
                'TOTAL_IBS_MONO','TOTAL_CBS_MONO','TOTAL_IBS_MONO_RETEN','TOTAL_CBS_MONO_RETEN','TOTAL_IBS_MONO_RET','TOTAL_CBS_MONO_RET',
                'TOTAL_NF_IBC_CBS_IS','TOTAL_IBS_CBS',
                'TIPO_NFCREDITO','TIPO_NFDEBITO','TIPO_ENTEGOV','PERC_REDUTOR_GOV','TIPO_OPERGOV',
                'ELOQUENT_UUID','CREATED_AT','UPDATED_AT','DELETED_AT'
            ];

            foreach ($cols as $c) {
                if (Schema::hasColumn('venda_caixas', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
