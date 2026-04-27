<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConfigCaixa extends BaseModel
{
	use HasFactory;
	protected $fillable = [
		'finalizar', 'reiniciar', 'editar_desconto', 'editar_acrescimo', 'editar_observacao', 
		'setar_valor_recebido', 'forma_pagamento_dinheiro', 'forma_pagamento_debito',
		'forma_pagamento_credito', 'forma_pagamento_pix', 'setar_leitor', 
		'valor_recebido_automatico', 'usuario_id', 'balanca_valor_peso', 
		'balanca_digito_verificador', 'mercadopago_public_key', 'mercadopago_access_token',
		'modelo_pdv', 'impressora_modelo', 'setar_quantidade', 'finalizar_fiscal',
		'finalizar_nao_fiscal', 'tipos_pagamento', 'tipo_pagamento_padrao', 'exibe_produtos',
		'botao_nao_fiscal', 'cupom_modelo', 'impressao_pre_venda', 'mensagem_padrao_cupom',
		'exibe_modal_cartoes', 'imprimir_ticket_troca'
	];

	public static function getTiposPagamento(){
        $config = ConfigCaixa::
        where('usuario_id', get_id_user())
        ->first();

        if($config == null) return [];

        return json_decode($config->tipos_pagamento);
    }

    public static function getTipoPagamentoPadrao(){
        $config = ConfigCaixa::
        where('usuario_id', get_id_user())
        ->first();

        if($config == null) return '';

        return $config->tipo_pagamento_padrao;
    }
}
