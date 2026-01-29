<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $tbl = 'item_venda_caixas';

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
            if (!Schema::hasColumn($tbl, 'IS_BC'))         $table->decimal('IS_BC', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'IS_ALIQ'))       $table->decimal('IS_ALIQ', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'IS_ALIQ_ESPEC')) $table->decimal('IS_ALIQ_ESPEC', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'IS_UND_TRIB'))   $table->string('IS_UND_TRIB', 10)->nullable();
            if (!Schema::hasColumn($tbl, 'IS_QTD_TRIB'))   $table->decimal('IS_QTD_TRIB', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'IS_VALOR'))      $table->decimal('IS_VALOR', 15, 2)->nullable();

            // ==========================================================
            // 2) IBS/CBS (por item)
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'CST_IBS_CBS'))        $table->string('CST_IBS_CBS', 8)->nullable();
            if (!Schema::hasColumn($tbl, 'CLASS_TRIB_IBS_CBS')) $table->string('CLASS_TRIB_IBS_CBS', 8)->nullable();

            if (!Schema::hasColumn($tbl, 'BC_IBS_CBS')) $table->decimal('BC_IBS_CBS', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_IBS'))  $table->decimal('VALOR_IBS', 15, 2)->nullable();

            if (!Schema::hasColumn($tbl, 'ALIQ_IBS_UF'))              $table->decimal('ALIQ_IBS_UF', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_IBS_UF'))             $table->decimal('VALOR_IBS_UF', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'PERC_DIF_IBS_UF'))          $table->decimal('PERC_DIF_IBS_UF', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_DIF_IBS_UF'))         $table->decimal('VALOR_DIF_IBS_UF', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_DIF_IBS_UF_DEVTRIB')) $table->decimal('VALOR_DIF_IBS_UF_DEVTRIB', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'PERC_RED_ALIQ_UF'))         $table->decimal('PERC_RED_ALIQ_UF', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'ALIQ_EFET_IBS_UF'))         $table->decimal('ALIQ_EFET_IBS_UF', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'ALIQ_IBS_MUN'))           $table->decimal('ALIQ_IBS_MUN', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_IBS_MUN'))          $table->decimal('VALOR_IBS_MUN', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'PERC_DIF_IBS_MUN'))       $table->decimal('PERC_DIF_IBS_MUN', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_DIF_IBS_MUN'))      $table->decimal('VALOR_DIF_IBS_MUN', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_DIF_IBS_MUN_TRIB')) $table->decimal('VALOR_DIF_IBS_MUN_TRIB', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'PERC_RED_ALIQ_IBS_MUN'))  $table->decimal('PERC_RED_ALIQ_IBS_MUN', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'ALIQ_EFET_IBS_MUN'))      $table->decimal('ALIQ_EFET_IBS_MUN', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'ALIQ_CBS'))              $table->decimal('ALIQ_CBS', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_CBS'))             $table->decimal('VALOR_CBS', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'PERC_DIF_CBS'))          $table->decimal('PERC_DIF_CBS', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_DIF_CBS'))         $table->decimal('VALOR_DIF_CBS', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_DIF_CBS_DEVTRIB')) $table->decimal('VALOR_DIF_CBS_DEVTRIB', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'PERC_RED_ALIQ_CBS'))     $table->decimal('PERC_RED_ALIQ_CBS', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'ALIQ_EFET_CBS'))         $table->decimal('ALIQ_EFET_CBS', 15, 4)->nullable();

            // ==========================================================
            // 3) Tributação regular (por item)
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'TRIB_REG_ALIQ_EFET_IBS_UF'))  $table->decimal('TRIB_REG_ALIQ_EFET_IBS_UF', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'TRIB_REG_VALOR_IBS_UF'))      $table->decimal('TRIB_REG_VALOR_IBS_UF', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'TRIB_REG_ALIQ_EFET_IBS_MUN')) $table->decimal('TRIB_REG_ALIQ_EFET_IBS_MUN', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'TRIB_REG_VALOR_IBS_MUN'))     $table->decimal('TRIB_REG_VALOR_IBS_MUN', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'TRIB_REG_ALIQ_EFET_CBS'))     $table->decimal('TRIB_REG_ALIQ_EFET_CBS', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'TRIB_REG_VALOR_CBS'))         $table->decimal('TRIB_REG_VALOR_CBS', 15, 2)->nullable();

            // ==========================================================
            // 4) Crédito Presumido (por item)
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'CRED_PRES_COD_IBS')) $table->integer('CRED_PRES_COD_IBS')->nullable();
            if (!Schema::hasColumn($tbl, 'PERC_CRED_PRES_IBS'))           $table->decimal('PERC_CRED_PRES_IBS', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_CRED_PRES_IBS'))          $table->decimal('VALOR_CRED_PRES_IBS', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_CRED_PRES_COND_SUS_IBS')) $table->decimal('VALOR_CRED_PRES_COND_SUS_IBS', 15, 2)->nullable();

            if (!Schema::hasColumn($tbl, 'CRED_PRES_COD_CBS')) $table->integer('CRED_PRES_COD_CBS')->nullable();
            if (!Schema::hasColumn($tbl, 'PERC_CRED_PRES_CBS'))           $table->decimal('PERC_CRED_PRES_CBS', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_CRED_PRES_CBS'))          $table->decimal('VALOR_CRED_PRES_CBS', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_CRED_PRES_COND_SUS_CBS')) $table->decimal('VALOR_CRED_PRES_COND_SUS_CBS', 15, 2)->nullable();

            // ==========================================================
            // 5) Flags / campos auxiliares
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'FLAG_IS')) $table->string('FLAG_IS', 1)->nullable();

            if (!Schema::hasColumn($tbl, 'NFSI_IPI_VLRIMPOSTO_DEVOLUCAO')) $table->decimal('NFSI_IPI_VLRIMPOSTO_DEVOLUCAO', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'NFSI_VICMSDESON'))               $table->decimal('NFSI_VICMSDESON', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'NFSI_TRIB_MUN'))                 $table->decimal('NFSI_TRIB_MUN', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'NFSI_TRIB_EST'))                 $table->decimal('NFSI_TRIB_EST', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'NFSI_TRIB_FED'))                 $table->decimal('NFSI_TRIB_FED', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'NFSI_TRIB_IMP'))                 $table->decimal('NFSI_TRIB_IMP', 15, 2)->nullable();

            if (!Schema::hasColumn($tbl, 'NFSI_VICMSMONORET')) $table->decimal('NFSI_VICMSMONORET', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'NFSI_QBCMONORET'))   $table->decimal('NFSI_QBCMONORET', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'NFSI_VICMSMONO')) $table->decimal('NFSI_VICMSMONO', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'NFSI_QBCMONO'))   $table->decimal('NFSI_QBCMONO', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'NFSI_VICMSMONORETEN')) $table->decimal('NFSI_VICMSMONORETEN', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'NFSI_QBCMONORETEN'))   $table->decimal('NFSI_QBCMONORETEN', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'NFSI_VICMSMONOOP'))  $table->decimal('NFSI_VICMSMONOOP', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'NFSI_VICMSMONODIF')) $table->decimal('NFSI_VICMSMONODIF', 15, 2)->nullable();

            // ==========================================================
            // 6) IBS/CBS Monofásico / Retenções / Ret
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'VALOR_IBS_MONO'))  $table->decimal('VALOR_IBS_MONO', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_CBS_MONO'))  $table->decimal('VALOR_CBS_MONO', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_IBS_RETEN')) $table->decimal('VALOR_IBS_RETEN', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_CBS_RETEN')) $table->decimal('VALOR_CBS_RETEN', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_IBS_RET'))   $table->decimal('VALOR_IBS_RET', 15, 2)->nullable();
            if (!Schema::hasColumn($tbl, 'VALOR_CBS_RET'))   $table->decimal('VALOR_CBS_RET', 15, 2)->nullable();

            if (!Schema::hasColumn($tbl, 'QBCMONO_IBS_CBS')) $table->decimal('QBCMONO_IBS_CBS', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'ADREM_IBS'))       $table->decimal('ADREM_IBS', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'ADREM_CBS'))       $table->decimal('ADREM_CBS', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'QBCMONORETEN_IBS_CBS')) $table->decimal('QBCMONORETEN_IBS_CBS', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'ADREM_IBS_RETEN'))      $table->decimal('ADREM_IBS_RETEN', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'ADREM_CBS_RETEN'))      $table->decimal('ADREM_CBS_RETEN', 15, 4)->nullable();

            if (!Schema::hasColumn($tbl, 'QBCMONORET_IBS_CBS')) $table->decimal('QBCMONORET_IBS_CBS', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'ADREM_IBS_RET'))      $table->decimal('ADREM_IBS_RET', 15, 4)->nullable();
            if (!Schema::hasColumn($tbl, 'ADREM_CBS_RET'))      $table->decimal('ADREM_CBS_RET', 15, 4)->nullable();

            // ==========================================================
            // 7) Combustível
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'FLAG_COMBUSTIVEL')) $table->string('FLAG_COMBUSTIVEL', 1)->nullable();
            if (!Schema::hasColumn($tbl, 'ANP'))             $table->string('ANP', 20)->nullable();

            // ==========================================================
            // 8) Padrão Eloquent (compatível com seu cenário atual)
            // ==========================================================
            if (!Schema::hasColumn($tbl, 'ELOQUENT_UUID')) $table->uuid('ELOQUENT_UUID')->nullable();
            if (!Schema::hasColumn($tbl, 'DELETED_AT'))    $table->timestamp('DELETED_AT')->nullable();
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
