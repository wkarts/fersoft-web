<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemRemessaNfe extends Model
{
    use HasFactory;

    protected $fillable = [
        'remessa_id', 'produto_id', 'quantidade', 'valor_unitario', 'sub_total',
        'cst_csosn', 'cst_pis', 'cst_cofins', 'cst_ipi', 'perc_icms', 'perc_pis',
        'perc_cofins', 'perc_ipi', 'pRedBC', 'vbc_icms', 'vbc_pis', 'vbc_cofins',
        'vbc_ipi', 'vBCSTRet', 'vFrete', 'modBCST', 'vBCST', 'pICMSST', 'vICMSST',
        'pMVAST', 'x_pedido', 'num_item_pedido', 'cest', 'valor_icms', 'valor_pis',
        'valor_cofins', 'valor_ipi', 'cfop', 'produto_nome',

        // 1) IS
        'IS_BC', 'IS_ALIQ', 'IS_ALIQ_ESPEC', 'IS_UND_TRIB', 'IS_QTD_TRIB', 'IS_VALOR',

        // 2) IBS/CBS
        'CST_IBS_CBS', 'CLASS_TRIB_IBS_CBS',
        'BC_IBS_CBS', 'VALOR_IBS',

        'ALIQ_IBS_UF', 'VALOR_IBS_UF', 'PERC_DIF_IBS_UF', 'VALOR_DIF_IBS_UF', 'VALOR_DIF_IBS_UF_DEVTRIB',
        'PERC_RED_ALIQ_UF', 'ALIQ_EFET_IBS_UF',

        'ALIQ_IBS_MUN', 'VALOR_IBS_MUN', 'PERC_DIF_IBS_MUN', 'VALOR_DIF_IBS_MUN', 'VALOR_DIF_IBS_MUN_TRIB',
        'PERC_RED_ALIQ_IBS_MUN', 'ALIQ_EFET_IBS_MUN',

        'ALIQ_CBS', 'VALOR_CBS', 'PERC_DIF_CBS', 'VALOR_DIF_CBS', 'VALOR_DIF_CBS_DEVTRIB',
        'PERC_RED_ALIQ_CBS', 'ALIQ_EFET_CBS',

        // 3) Tributação regular
        'TRIB_REG_ALIQ_EFET_IBS_UF', 'TRIB_REG_VALOR_IBS_UF',
        'TRIB_REG_ALIQ_EFET_IBS_MUN', 'TRIB_REG_VALOR_IBS_MUN',
        'TRIB_REG_ALIQ_EFET_CBS', 'TRIB_REG_VALOR_CBS',

        // 4) Crédito presumido
        'CRED_PRES_COD_IBS', 'PERC_CRED_PRES_IBS', 'VALOR_CRED_PRES_IBS', 'VALOR_CRED_PRES_COND_SUS_IBS',
        'CRED_PRES_COD_CBS', 'PERC_CRED_PRES_CBS', 'VALOR_CRED_PRES_CBS', 'VALOR_CRED_PRES_COND_SUS_CBS',

        // 5) Flags / auxiliares
        'FLAG_IS',
        'NFSI_IPI_VLRIMPOSTO_DEVOLUCAO', 'NFSI_VICMSDESON', 'NFSI_TRIB_MUN', 'NFSI_TRIB_EST', 'NFSI_TRIB_FED', 'NFSI_TRIB_IMP',
        'NFSI_VICMSMONORET', 'NFSI_QBCMONORET',
        'NFSI_VICMSMONO', 'NFSI_QBCMONO',
        'NFSI_VICMSMONORETEN', 'NFSI_QBCMONORETEN',
        'NFSI_VICMSMONOOP', 'NFSI_VICMSMONODIF',

        // 6) IBS/CBS Mono/Ret/Reten
        'VALOR_IBS_MONO', 'VALOR_CBS_MONO', 'VALOR_IBS_RETEN', 'VALOR_CBS_RETEN', 'VALOR_IBS_RET', 'VALOR_CBS_RET',
        'QBCMONO_IBS_CBS', 'ADREM_IBS', 'ADREM_CBS',
        'QBCMONORETEN_IBS_CBS', 'ADREM_IBS_RETEN', 'ADREM_CBS_RETEN',
        'QBCMONORET_IBS_CBS', 'ADREM_IBS_RET', 'ADREM_CBS_RET',

        // 7) Combustível
        'FLAG_COMBUSTIVEL', 'ANP',

        // 8) Padrão Eloquent
        'ELOQUENT_UUID', 'DELETED_AT',
    ];

    public function produto(){
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function venda(){
        return $this->belongsTo(RemessaNfe::class, 'remessa_id');
    }

}
