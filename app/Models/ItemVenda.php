<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemVenda extends Model
{
    //use SoftDeletes;

    public $timestamps = false;

    //public const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'produto_id', 'venda_id', 'quantidade', 'valor', 'cfop', 'altura', 'largura', 'profundidade',
        'acrescimo_perca', 'esquerda', 'direita', 'inferior', 'superior', 'valor_custo',
        'quantidade_dimensao', 'devolvido', 'x_pedido', 'num_item_pedido', 'produto_nome',

        // 1) IS
        'is_bc', 'is_aliq', 'is_aliq_espec', 'is_und_trib', 'is_qtd_trib', 'is_valor',

        // 2) IBS/CBS
        'cst_ibs_cbs', 'class_trib_ibs_cbs',
        'bc_ibs_cbs', 'valor_ibs',

        'aliq_ibs_uf', 'valor_ibs_uf', 'perc_dif_ibs_uf', 'valor_dif_ibs_uf', 'valor_dif_ibs_uf_devtrib',
        'perc_red_aliq_uf', 'aliq_efet_ibs_uf',

        'aliq_ibs_mun', 'valor_ibs_mun', 'perc_dif_ibs_mun', 'valor_dif_ibs_mun', 'valor_dif_ibs_mun_trib',
        'perc_red_aliq_ibs_mun', 'aliq_efet_ibs_mun',

        'aliq_cbs', 'valor_cbs', 'perc_dif_cbs', 'valor_dif_cbs', 'valor_dif_cbs_devtrib',
        'perc_red_aliq_cbs', 'aliq_efet_cbs',

        // 3) Tributação regular
        'trib_reg_aliq_efet_ibs_uf', 'trib_reg_valor_ibs_uf',
        'trib_reg_aliq_efet_ibs_mun', 'trib_reg_valor_ibs_mun',
        'trib_reg_aliq_efet_cbs', 'trib_reg_valor_cbs',

        // 4) Crédito presumido
        'cred_pres_cod_ibs', 'perc_cred_pres_ibs', 'valor_cred_pres_ibs', 'valor_cred_pres_cond_sus_ibs',
        'cred_pres_cod_cbs', 'perc_cred_pres_cbs', 'valor_cred_pres_cbs', 'valor_cred_pres_cond_sus_cbs',

        // 5) Flags / auxiliares
        'flag_is',
        'nfsi_ipi_vlrimposto_devolucao', 'nfsi_vicmsdeson', 'nfsi_trib_mun', 'nfsi_trib_est', 'nfsi_trib_fed', 'nfsi_trib_imp',
        'nfsi_vicmsmonoret', 'nfsi_qbcmonoret',
        'nfsi_vicmsmono', 'nfsi_qbcmono',
        'nfsi_vicmsmonoreten', 'nfsi_qbcmonoreten',
        'nfsi_vicmsmonoop', 'nfsi_vicmsmonodif',

        // 6) IBS/CBS Mono/Ret/Reten
        'valor_ibs_mono', 'valor_cbs_mono', 'valor_ibs_reten', 'valor_cbs_reten', 'valor_ibs_ret', 'valor_cbs_ret',
        'qbcmono_ibs_cbs', 'adrem_ibs', 'adrem_cbs',
        'qbcmonoreten_ibs_cbs', 'adrem_ibs_reten', 'adrem_cbs_reten',
        'qbcmonoret_ibs_cbs', 'adrem_ibs_ret', 'adrem_cbs_ret',

        // 7) Combustível
        'flag_combustivel', 'anp',

        // 8) Padrão Eloquent
        'eloquent_uuid', 'deleted_at',
    ];

    protected $casts = [
        'quantidade' => 'decimal:4',
        'valor' => 'decimal:2',
        'valor_custo' => 'decimal:2',

        'is_bc' => 'decimal:2',
        'is_aliq' => 'decimal:6',
        'is_qtd_trib' => 'decimal:4',
        'is_valor' => 'decimal:2',

        'bc_ibs_cbs' => 'decimal:2',
        'valor_ibs' => 'decimal:2',

        'aliq_ibs_uf' => 'decimal:6',
        'valor_ibs_uf' => 'decimal:2',
        'perc_dif_ibs_uf' => 'decimal:6',
        'valor_dif_ibs_uf' => 'decimal:2',
        'valor_dif_ibs_uf_devtrib' => 'decimal:2',

        'aliq_ibs_mun' => 'decimal:6',
        'valor_ibs_mun' => 'decimal:2',
        'perc_dif_ibs_mun' => 'decimal:6',
        'valor_dif_ibs_mun' => 'decimal:2',
        'valor_dif_ibs_mun_trib' => 'decimal:2',

        'aliq_cbs' => 'decimal:6',
        'valor_cbs' => 'decimal:2',
        'perc_dif_cbs' => 'decimal:6',
        'valor_dif_cbs' => 'decimal:2',
        'valor_dif_cbs_devtrib' => 'decimal:2',

        'valor_ibs_mono' => 'decimal:2',
        'valor_cbs_mono' => 'decimal:2',
        'valor_ibs_reten' => 'decimal:2',
        'valor_cbs_reten' => 'decimal:2',
        'valor_ibs_ret' => 'decimal:2',
        'valor_cbs_ret' => 'decimal:2',

        'qbcmono_ibs_cbs' => 'decimal:4',
        'qbcmonoreten_ibs_cbs' => 'decimal:4',
        'qbcmonoret_ibs_cbs' => 'decimal:4',

        'adrem_ibs' => 'decimal:6',
        'adrem_cbs' => 'decimal:6',
        'adrem_ibs_reten' => 'decimal:6',
        'adrem_cbs_reten' => 'decimal:6',
        'adrem_ibs_ret' => 'decimal:6',
        'adrem_cbs_ret' => 'decimal:6',
    ];

    public function produto(){
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function venda(){
        return $this->belongsTo(Venda::class, 'venda_id');
    }

    public function percentualUf($uf){
        $tributacao = TributacaoUf
            ::where('uf', $uf)
            ->where('produto_id', $this->produto_id)
            ->first();

        return $tributacao;
    }
}
