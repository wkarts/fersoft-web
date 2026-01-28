<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendaCaixaPreVenda extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id', 'usuario_id', 'valor_total', 'NFcNumero', 'natureza_id', 'chave', 'path_xml', 
        'estado', 'tipo_pagamento', 'forma_pagamento', 'dinheiro_recebido', 'troco', 'nome', 'cpf', 
        'observacao', 'desconto', 'acrescimo', 'pedido_delivery_id', 'tipo_pagamento_1', 
        'valor_pagamento_1', 'tipo_pagamento_2', 'valor_pagamento_2', 'tipo_pagamento_3', 
        'valor_pagamento_3', 'empresa_id', 'bandeira_cartao', 'cnpj_cartao', 'cAut_cartao', 
        'descricao_pag_outros', 'rascunho', 'prevenda_nivel', 'consignado'
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function itens(){
        return $this->hasMany(ItemVendaCaixaPreVenda::class, 'venda_caixa_prevenda_id', 'id')->with('produto');
    }

    public function vendedor(){
        $usuario = Usuario::find($this->usuario_id);
        if($usuario->funcionario) return $usuario->funcionario->nome;
        else return '--';
    }
}
