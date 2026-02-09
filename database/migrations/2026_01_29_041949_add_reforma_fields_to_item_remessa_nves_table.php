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
            'is_bc','is_aliq','is_aliq_espec','is_und_trib','is_qtd_trib','is_valor',
            'cst_ibs_cbs','class_trib_ibs_cbs','bc_ibs_cbs','valor_ibs',
            'aliq_ibs_uf','valor_ibs_uf','perc_dif_ibs_uf','valor_dif_ibs_uf','valor_dif_ibs_uf_devtrib','perc_red_aliq_uf','aliq_efet_ibs_uf',
            'aliq_ibs_mun','valor_ibs_mun','perc_dif_ibs_mun','valor_dif_ibs_mun','valor_dif_ibs_mun_trib','perc_red_aliq_ibs_mun','aliq_efet_ibs_mun',
            'aliq_cbs','valor_cbs','perc_dif_cbs','valor_dif_cbs','valor_dif_cbs_devtrib','perc_red_aliq_cbs','aliq_efet_cbs',
            'trib_reg_aliq_efet_ibs_uf','trib_reg_valor_ibs_uf','trib_reg_aliq_efet_ibs_mun','trib_reg_valor_ibs_mun','trib_reg_aliq_efet_cbs','trib_reg_valor_cbs',
            'cred_pres_cod_ibs','perc_cred_pres_ibs','valor_cred_pres_ibs','valor_cred_pres_cond_sus_ibs',
            'cred_pres_cod_cbs','perc_cred_pres_cbs','valor_cred_pres_cbs','valor_cred_pres_cond_sus_cbs',
            'flag_is',
            'nfsi_ipi_vlrimposto_devolucao','nfsi_vicmsdeson','nfsi_trib_mun','nfsi_trib_est','nfsi_trib_fed','nfsi_trib_imp',
            'nfsi_vicmsmonoret','nfsi_qbcmonoret','nfsi_vicmsmono','nfsi_qbcmono','nfsi_vicmsmonoreten','nfsi_qbcmonoreten','nfsi_vicmsmonoop','nfsi_vicmsmonodif',
            'valor_ibs_mono','valor_cbs_mono','valor_ibs_reten','valor_cbs_reten','valor_ibs_ret','valor_cbs_ret',
            'qbcmono_ibs_cbs','adrem_ibs','adrem_cbs','qbcmonoreten_ibs_cbs','adrem_ibs_reten','adrem_cbs_reten','qbcmonoret_ibs_cbs','adrem_ibs_ret','adrem_cbs_ret',
            'flag_combustivel','anp',
            'eloquent_uuid','deleted_at',
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
