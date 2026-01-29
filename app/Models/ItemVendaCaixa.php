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
