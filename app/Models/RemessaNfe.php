<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemessaNfe extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id', 'usuario_id', 'valor_total', 'forma_pagamento', 'numero_nfe',
        'natureza_id', 'chave', 'estado', 'observacao', 'desconto', 'transportadora_id', 'sequencia_cce', 
        'empresa_id', 'acrescimo', 'data_entrega',
        'nSerie', 'data_emissao', 'numero_sequencial', 'filial_id', 'baixa_estoque', 'tipo_nfe',
        'placa', 'uf', 'valor_frete', 'tipo_frete', 'qtd_volumes', 'numeracao_volumes',
        'especie', 'peso_liquido', 'peso_bruto', 'data_retroativa', 'gerar_conta_receber', 'venda_caixa_id',
        'data_saida'
    ];

    public static function lastNFe($empresa_id = null){
        if($empresa_id == null){
            $value = session('user_logged');
            $empresa_id = $value['empresa'];
        }
        $numeroVenda = Venda::lastNF($empresa_id);

        $remessa = RemessaNfe::
        where('numero_nfe', '!=', 0)
        ->where('empresa_id', $empresa_id)
        ->orderBy('numero_nfe', 'desc')
        ->first();

        $numeroRemessa = $remessa != null ? $remessa->numero_nfe : 0;

        if($numeroRemessa > $numeroVenda){
            return $numeroRemessa;
        }else{
            return $numeroVenda;
        }
    }

    public function usuario(){
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function filial(){
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function fatura(){
        return $this->hasMany('App\Models\RemessaNfeFatura', 'remessa_id', 'id');
    }

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function natureza(){
        return $this->belongsTo(NaturezaOperacao::class, 'natureza_id');
    }

    public function transportadora(){
        return $this->belongsTo(Transportadora::class, 'transportadora_id');
    }

    public function itens(){
        return $this->hasMany('App\Models\ItemRemessaNfe', 'remessa_id', 'id');
    }

    public function referencias(){
        return $this->hasMany('App\Models\RemessaReferenciaNfe', 'remessa_id', 'id');
    }

}
