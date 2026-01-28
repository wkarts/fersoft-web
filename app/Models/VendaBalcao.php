<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendaBalcao extends Model
{
    protected $fillable = [
        'empresa_id', 'codigo_venda', 'numero_sequencial', 'cliente_id', 'usuario_id', 'transportadora_id', 'valor_total',
        'desconto', 'acrescimo', 'forma_pagamento', 'tipo_pagamento', 'observacao', 'estado', 'bandeira_cartao',
        'cnpj_cartao', 'cAut_cartao', 'descricao_pag_outros', 'filial_id', 'placa', 'uf', 'valor',
        'tipo', 'quantidade_volumes', 'numeracao_volumes', 'especie', 'peso_liquido', 'peso_bruto', 'cliente_nome',
        'vendedor_id'
    ];

    public static function tiposPagamento(){
        return [
            '01' => 'Dinheiro',
            '02' => 'Cheque',
            '03' => 'Cartão de Crédito',
            '04' => 'Cartão de Débito',
            '05' => 'Crédito Loja',
            '06' => 'Crediário',
            '10' => 'Vale Alimentação',
            '11' => 'Vale Refeição',
            '12' => 'Vale Presente',
            '13' => 'Vale Combustível',
            '14' => 'Duplicata Mercantil',
            '15' => 'Boleto Bancário',
            '16' => 'Depósito Bancário',
            '17' => 'Pagamento Instantâneo (PIX) - Dinâmico',
            '18' => 'Transferência bancária, Carteira Digital',
            '19' => 'Programa de fidelidade, Cashback, Crédito Virtual',
            '20' => 'Pagamento Instantâneo (PIX) – Estático',
            '21' => 'Crédito em Loja',
            '22' => 'Pagamento Eletrônico não Informado - falha de hardware do sistema emissor',
            '90' => 'Sem Pagamento',
            // '99' => 'Outros',
        ];
    }

    public function fatura(){
        return $this->hasMany(FaturaVendaBalcao::class, 'venda_balcao_id');
    }

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function usuario(){
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function transportadora(){
        return $this->belongsTo(Transportadora::class, 'transportadora_id');
    }

    public function itens(){
        return $this->hasMany(ItemVendaBalcao::class, 'venda_balcao_id')->with('produto');
    }

    public function getTipoPagamento(){
        foreach(Venda::tiposPagamento() as $key => $t){
            if($this->tipo_pagamento == $key) return $t;
        }
    }
}
