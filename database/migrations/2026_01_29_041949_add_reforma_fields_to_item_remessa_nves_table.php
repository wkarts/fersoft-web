<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $tbl = 'item_remessa_nves';

    public function up(): void
    {
        if (!Schema::hasTable($this->tbl)) {
            return;
        }

        Schema::table($this->tbl, function (Blueprint $table) {
            $tbl = $this->tbl;

            // ==========================================================
            // 1) IS (Imposto Seletivo)
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'is_bc'))         $table->decimal('is_bc', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'is_aliq'))       $table->decimal('is_aliq', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'is_aliq_espec')) $table->decimal('is_aliq_espec', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'is_und_trib'))   $table->string('is_und_trib', 10)->nullable();
            if (!Schema::hasColumn($tbl, 'is_qtd_trib'))   $table->decimal('is_qtd_trib', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'is_valor'))      $table->decimal('is_valor', 15, 2)->nullable();

            // ==========================================================
            // 2) IBS/CBS (por item)
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'cst_ibs_cbs'))        $table->string('cst_ibs_cbs', 8)->nullable();
            if (!Schema::hasColumn($tbl, 'class_trib_ibs_cbs')) $table->string('class_trib_ibs_cbs', 8)->nullable();

            if (!Schema::hasColumn($tbl, 'bc_ibs_cbs')) $table->decimal('bc_ibs_cbs', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_ibs'))  $table->decimal('valor_ibs', 15, 2)->nullable();

            if (!Schema::hasColumn($tbl, 'aliq_ibs_uf'))              $table->decimal('aliq_ibs_uf', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_ibs_uf'))             $table->decimal('valor_ibs_uf', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'perc_dif_ibs_uf'))          $table->decimal('perc_dif_ibs_uf', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_dif_ibs_uf'))         $table->decimal('valor_dif_ibs_uf', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_dif_ibs_uf_devtrib')) $table->decimal('valor_dif_ibs_uf_devtrib', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'perc_red_aliq_uf'))         $table->decimal('perc_red_aliq_uf', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'aliq_efet_ibs_uf'))         $table->decimal('aliq_efet_ibs_uf', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'aliq_ibs_mun'))           $table->decimal('aliq_ibs_mun', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_ibs_mun'))          $table->decimal('valor_ibs_mun', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'perc_dif_ibs_mun'))       $table->decimal('perc_dif_ibs_mun', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_dif_ibs_mun'))      $table->decimal('valor_dif_ibs_mun', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_dif_ibs_mun_trib')) $table->decimal('valor_dif_ibs_mun_trib', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'perc_red_aliq_ibs_mun'))  $table->decimal('perc_red_aliq_ibs_mun', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'aliq_efet_ibs_mun'))      $table->decimal('aliq_efet_ibs_mun', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'aliq_cbs'))              $table->decimal('aliq_cbs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cbs'))             $table->decimal('valor_cbs', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'perc_dif_cbs'))          $table->decimal('perc_dif_cbs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_dif_cbs'))         $table->decimal('valor_dif_cbs', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_dif_cbs_devtrib')) $table->decimal('valor_dif_cbs_devtrib', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'perc_red_aliq_cbs'))     $table->decimal('perc_red_aliq_cbs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'aliq_efet_cbs'))         $table->decimal('aliq_efet_cbs', 15, 4)->nullable();

            // ==========================================================
            // 3) Tributação regular (por item)
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'trib_reg_aliq_efet_ibs_uf'))  $table->decimal('trib_reg_aliq_efet_ibs_uf', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'trib_reg_valor_ibs_uf'))      $table->decimal('trib_reg_valor_ibs_uf', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'trib_reg_aliq_efet_ibs_mun')) $table->decimal('trib_reg_aliq_efet_ibs_mun', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'trib_reg_valor_ibs_mun'))     $table->decimal('trib_reg_valor_ibs_mun', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'trib_reg_aliq_efet_cbs'))     $table->decimal('trib_reg_aliq_efet_cbs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'trib_reg_valor_cbs'))         $table->decimal('trib_reg_valor_cbs', 15, 2)->nullable();

            // ==========================================================
            // 4) Crédito Presumido (por item)
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'cred_pres_cod_ibs')) $table->integer('cred_pres_cod_ibs')->nullable();
            if (!Schema::hasColumn($tbl, 'perc_cred_pres_ibs'))           $table->decimal('perc_cred_pres_ibs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cred_pres_ibs'))          $table->decimal('valor_cred_pres_ibs', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cred_pres_cond_sus_ibs')) $table->decimal('valor_cred_pres_cond_sus_ibs', 15, 2)->nullable();

            if (!Schema::hasColumn($tbl, 'cred_pres_cod_cbs')) $table->integer('cred_pres_cod_cbs')->nullable();
            if (!Schema::hasColumn($tbl, 'perc_cred_pres_cbs'))           $table->decimal('perc_cred_pres_cbs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cred_pres_cbs'))          $table->decimal('valor_cred_pres_cbs', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cred_pres_cond_sus_cbs')) $table->decimal('valor_cred_pres_cond_sus_cbs', 15, 2)->nullable();

            // ==========================================================
            // 5) Flags / campos auxiliares
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'flag_is')) $table->string('flag_is', 1)->nullable();

            if (!Schema::hasColumn($tbl, 'nfsi_ipi_vlrimposto_devolucao')) $table->decimal('nfsi_ipi_vlrimposto_devolucao', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'nfsi_vicmsdeson'))               $table->decimal('nfsi_vicmsdeson', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'nfsi_trib_mun'))                 $table->decimal('nfsi_trib_mun', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'nfsi_trib_est'))                 $table->decimal('nfsi_trib_est', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'nfsi_trib_fed'))                 $table->decimal('nfsi_trib_fed', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'nfsi_trib_imp'))                 $table->decimal('nfsi_trib_imp', 15, 2)->nullable();

            if (!Schema::hasColumn($tbl, 'nfsi_vicmsmonoret')) $table->decimal('nfsi_vicmsmonoret', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'nfsi_qbcmonoret'))   $table->decimal('nfsi_qbcmonoret', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'nfsi_vicmsmono')) $table->decimal('nfsi_vicmsmono', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'nfsi_qbcmono'))   $table->decimal('nfsi_qbcmono', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'nfsi_vicmsmonoreten')) $table->decimal('nfsi_vicmsmonoreten', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'nfsi_qbcmonoreten'))   $table->decimal('nfsi_qbcmonoreten', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'nfsi_vicmsmonoop'))  $table->decimal('nfsi_vicmsmonoop', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'nfsi_vicmsmonodif')) $table->decimal('nfsi_vicmsmonodif', 15, 2)->nullable();

            // ==========================================================
            // 6) IBS/CBS Monofásico / Retenções / Ret
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'valor_ibs_mono'))  $table->decimal('valor_ibs_mono', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cbs_mono'))  $table->decimal('valor_cbs_mono', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_ibs_reten')) $table->decimal('valor_ibs_reten', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cbs_reten')) $table->decimal('valor_cbs_reten', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_ibs_ret'))   $table->decimal('valor_ibs_ret', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cbs_ret'))   $table->decimal('valor_cbs_ret', 15, 2)->nullable();

            if (!Schema::hasColumn($tbl, 'qbcmono_ibs_cbs')) $table->decimal('qbcmono_ibs_cbs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'adrem_ibs'))       $table->decimal('adrem_ibs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'adrem_cbs'))       $table->decimal('adrem_cbs', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'qbcmonoreten_ibs_cbs')) $table->decimal('qbcmonoreten_ibs_cbs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'adrem_ibs_reten'))      $table->decimal('adrem_ibs_reten', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'adrem_cbs_reten'))      $table->decimal('adrem_cbs_reten', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'qbcmonoret_ibs_cbs')) $table->decimal('qbcmonoret_ibs_cbs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'adrem_ibs_ret'))      $table->decimal('adrem_ibs_ret', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'adrem_cbs_ret'))      $table->decimal('adrem_cbs_ret', 15, 4)->nullable();

            // ==========================================================
            // 7) Combustível
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'flag_combustivel')) $table->string('flag_combustivel', 1)->nullable();
            if (!Schema::hasColumn($tbl, 'anp'))             $table->string('anp', 20)->nullable();

            // ==========================================================
            // 8) Padrão Eloquent (compatível com seu cenário atual)
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'eloquent_uuid')) $table->uuid('eloquent_uuid')->nullable();
            if (!Schema::hasColumn($tbl, 'deleted_at'))    $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable($this->tbl)) {
            return;
        }

        $cols = [
            'IS_BC','IS_ALIQ','IS_ALIQ_ESPEC','IS_UND_TRIB','IS_QTD_TRIB','IS_VALOR',
            'CST_IBS_CBS','CLASS_TRIB_IBS_CBS','BC_IBS_CBS','VALOR_IBS',
            'ALIQ_IBS_UF','VALOR_IBS_UF','PERC_DIF_IBS_UF','VALOR_DIF_IBS_UF','VALOR_DIF_IBS_UF_DEVTRIB','PERC_RED_ALIQ_UF','ALIQ_EFET_IBS_UF',
            'ALIQ_IBS_MUN','VALOR_IBS_MUN','PERC_DIF_IBS_MUN','VALOR_DIF_IBS_MUN','VALOR_DIF_IBS_MUN_TRIB','PERC_RED_ALIQ_IBS_MUN','ALIQ_EFET_IBS_MUN',
            'ALIQ_CBS','VALOR_CBS','PERC_DIF_CBS','VALOR_DIF_CBS','VALOR_DIF_CBS_DEVTRIB','PERC_RED_ALIQ_CBS','ALIQ_EFET_CBS',
            'TRIB_REG_ALIQ_EFET_IBS_UF','TRIB_REG_VALOR_IBS_UF','TRIB_REG_ALIQ_EFET_IBS_MUN','TRIB_REG_VALOR_IBS_MUN','TRIB_REG_ALIQ_EFET_CBS','TRIB_REG_VALOR_CBS',
            'CRED_PRES_COD_IBS','PERC_CRED_PRES_IBS','VALOR_CRED_PRES_IBS','VALOR_CRED_PRES_COND_SUS_IBS',
            'CRED_PRES_COD_CBS','PERC_CRED_PRES_CBS','VALOR_CRED_PRES_CBS','VALOR_CRED_PRES_COND_SUS_CBS',
            'FLAG_IS',
            'NFSI_IPI_VLRIMPOSTO_DEVOLUCAO','NFSI_VICMSDESON','NFSI_TRIB_MUN','NFSI_TRIB_EST','NFSI_TRIB_FED','NFSI_TRIB_IMP',
            'NFSI_VICMSMONORET','NFSI_QBCMONORET','NFSI_VICMSMONO','NFSI_QBCMONO','NFSI_VICMSMONORETEN','NFSI_QBCMONORETEN','NFSI_VICMSMONOOP','NFSI_VICMSMONODIF',
            'VALOR_IBS_MONO','VALOR_CBS_MONO','VALOR_IBS_RETEN','VALOR_CBS_RETEN','VALOR_IBS_RET','VALOR_CBS_RET',
            'QBCMONO_IBS_CBS','ADREM_IBS','ADREM_CBS','QBCMONORETEN_IBS_CBS','ADREM_IBS_RETEN','ADREM_CBS_RETEN','QBCMONORET_IBS_CBS','ADREM_IBS_RET','ADREM_CBS_RET',
            'FLAG_COMBUSTIVEL','ANP',
            'ELOQUENT_UUID','DELETED_AT',
        ];

        Schema::table($this->tbl, function (Blueprint $table) use ($cols) {
            $tbl = $this->tbl;

            $drop = [];
            foreach ($cols as $c) {
                if (Schema::hasColumn($tbl, $c)) {
                    $drop[] = $c;
                }
            }

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
