<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PedidoDelivery;

class ItemVendaCaixa extends Model
{
    protected $fillable = [
		'produto_id', 'venda_caixa_id', 'quantidade', 'valor', 'item_pedido_id', 'observacao',
        'cfop', 'valor_custo', 'devolvido', 'valor_comissao_assessor',

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

	public function produto(){
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function itemPedido(){
        return $this->belongsTo(ItemPedido::class, 'item_pedido_id');
    }

    public function venda(){
        return $this->belongsTo(VendaCaixa::class, 'venda_caixa_id');
    }

    public function nomeDoProduto(){
    	$nome = $this->produto->nome;
    	if($this->observacao != '')
    		$nome .= ' obs: ' . $this->observacao;
    	return $nome;
    }

    public function nomeDoProdutoDelivery($pedido_delivery_id, $indice){
        $pedido = PedidoDelivery::find($pedido_delivery_id);
        foreach($pedido->itens as $key => $i){
            if($key == $indice){
                return $i->nomeDoProduto();
            }
        }

    }
}
