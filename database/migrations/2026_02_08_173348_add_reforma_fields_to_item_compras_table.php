<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $tbl = 'item_compras';

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
            // 2) IBS/CBS - Base e alíquotas
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'bc_ibs_cbs'))        $table->decimal('bc_ibs_cbs', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'cst_ibs_cbs'))       $table->string('cst_ibs_cbs', 3)->nullable();
            if (!Schema::hasColumn($tbl, 'class_trib_ibs_cbs'))$table->string('class_trib_ibs_cbs', 10)->nullable();

            if (!Schema::hasColumn($tbl, 'aliq_ibs_uf'))       $table->decimal('aliq_ibs_uf', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'aliq_ibs_mun'))      $table->decimal('aliq_ibs_mun', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'aliq_cbs'))          $table->decimal('aliq_cbs', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'aliq_efet_ibs_uf'))  $table->decimal('aliq_efet_ibs_uf', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'aliq_efet_ibs_mun')) $table->decimal('aliq_efet_ibs_mun', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'aliq_efet_cbs'))     $table->decimal('aliq_efet_cbs', 15, 4)->nullable();

            // ==========================================================
            // 3) Valores IBS/CBS
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'valor_ibs'))         $table->decimal('valor_ibs', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_ibs_uf'))      $table->decimal('valor_ibs_uf', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_ibs_mun'))     $table->decimal('valor_ibs_mun', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cbs'))         $table->decimal('valor_cbs', 15, 2)->nullable();

            // Diferenças (devolução/tributação)
            if (!Schema::hasColumn($tbl, 'perc_dif_ibs_uf'))         $table->decimal('perc_dif_ibs_uf', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_dif_ibs_uf'))        $table->decimal('valor_dif_ibs_uf', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_dif_ibs_uf_devtrib'))$table->decimal('valor_dif_ibs_uf_devtrib', 15, 2)->nullable();

            if (!Schema::hasColumn($tbl, 'perc_dif_ibs_mun'))        $table->decimal('perc_dif_ibs_mun', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_dif_ibs_mun'))       $table->decimal('valor_dif_ibs_mun', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_dif_ibs_mun_trib'))  $table->decimal('valor_dif_ibs_mun_trib', 15, 2)->nullable();

            if (!Schema::hasColumn($tbl, 'perc_dif_cbs'))            $table->decimal('perc_dif_cbs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_dif_cbs'))           $table->decimal('valor_dif_cbs', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_dif_cbs_devtrib'))   $table->decimal('valor_dif_cbs_devtrib', 15, 2)->nullable();

            // ==========================================================
            // 4) Crédito Presumido
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'cred_pres_cod_ibs'))                 $table->integer('cred_pres_cod_ibs')->nullable();
            if (!Schema::hasColumn($tbl, 'perc_cred_pres_ibs'))                $table->decimal('perc_cred_pres_ibs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cred_pres_ibs'))               $table->decimal('valor_cred_pres_ibs', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cred_pres_cond_sus_ibs'))      $table->decimal('valor_cred_pres_cond_sus_ibs', 15, 2)->nullable();

            if (!Schema::hasColumn($tbl, 'cred_pres_cod_cbs'))                 $table->integer('cred_pres_cod_cbs')->nullable();
            if (!Schema::hasColumn($tbl, 'perc_cred_pres_cbs'))                $table->decimal('perc_cred_pres_cbs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cred_pres_cbs'))               $table->decimal('valor_cred_pres_cbs', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cred_pres_cond_sus_cbs'))      $table->decimal('valor_cred_pres_cond_sus_cbs', 15, 2)->nullable();

            // ==========================================================
            // 5) Monofásico (combustíveis etc.)
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'flag_combustivel'))       $table->string('flag_combustivel', 1)->nullable();
            if (!Schema::hasColumn($tbl, 'anp'))                    $table->string('anp', 20)->nullable();

            if (!Schema::hasColumn($tbl, 'qbcmono_ibs_cbs'))         $table->decimal('qbcmono_ibs_cbs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'qbcmonoreten_ibs_cbs'))    $table->decimal('qbcmonoreten_ibs_cbs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'qbcmonoret_ibs_cbs'))      $table->decimal('qbcmonoret_ibs_cbs', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'adrem_ibs'))              $table->decimal('adrem_ibs', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'adrem_cbs'))              $table->decimal('adrem_cbs', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'adrem_ibs_reten'))         $table->decimal('adrem_ibs_reten', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'adrem_cbs_reten'))         $table->decimal('adrem_cbs_reten', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'adrem_ibs_ret'))           $table->decimal('adrem_ibs_ret', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'adrem_cbs_ret'))           $table->decimal('adrem_cbs_ret', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'valor_ibs_mono'))          $table->decimal('valor_ibs_mono', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cbs_mono'))          $table->decimal('valor_cbs_mono', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_ibs_reten'))         $table->decimal('valor_ibs_reten', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cbs_reten'))         $table->decimal('valor_cbs_reten', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_ibs_ret'))           $table->decimal('valor_ibs_ret', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'valor_cbs_ret'))           $table->decimal('valor_cbs_ret', 15, 2)->nullable();
        });
    }

    public function down(): void
    {
        // Conservador: não remove colunas
    }
};
