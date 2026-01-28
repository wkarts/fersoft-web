<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ConfigNota;

class Cte extends Model
{
    protected $fillable = [
        'chave_nfe', 'remetente_id', 'destinatario_id', 'usuario_id', 'natureza_id', 'tomador',
        'municipio_envio', 'municipio_inicio', 'municipio_fim', 'logradouro_tomador', 
        'numero_tomador', 'bairro_tomador', 'cep_tomador', 'municipio_tomador',
        'valor_transporte', 'valor_receber', 'valor_carga', 
        'produto_predominante', 'data_previsata_entrega', 'observacao',
        'sequencia_cce', 'cte_numero', 'chave', 'path_xml', 'estado', 'retira', 'detalhes_retira',
        'modal', 'veiculo_id', 'tpDoc', 'descOutros', 'nDoc', 'vDocFisc', 'empresa_id', 
        'globalizado', 'cst', 'perc_icms', 'recebedor_id', 'expedidor_id', 'pRedBC',
        'status_pagamento', 'filial_id', 'recibo', 'tipo_servico'
    ];

    public function filial(){
        return $this->belongsTo(Filial::class, 'filial_id');
    }

   // 0-Remetente; 1-Expedidor; 2-Recebedor; 3-Destinatário

    public function getTomador(){
        if($this->tomador == 0) return 'Remetente';
        else if($this->tomador == 1) return 'Expedidor';
        else if($this->tomador == 2) return 'Recebedor';
        else if($this->tomador == 3) return 'Destinatário';
    }

    public function getTomadorNome(){
        if($this->tomador == 0){ 
            return $this->remetente->razao_social;
        }else if($this->tomador == 1){
            return $this->expedidor ? $this->expedidor->razao_social : '--';
        }else if($this->tomador == 2){
            return $this->recebedor ? $this->recebedor->razao_social : '--';
        }
        else if($this->tomador == 3){
            return $this->recebedor ? $this->destinatario->razao_social : '--';
        }
    }

    public function getTomadorFull(){
        if($this->tomador == 0) return $this->remetente;
        else if($this->tomador == 1) return $this->expedidor;
        else if($this->tomador == 2) return $this->recebedor;
        else if($this->tomador == 3) return $this->destinatario;
    }

    public function componentes(){
        return $this->hasMany('App\Models\ComponenteCte', 'cte_id', 'id');
    }

    public function medidas(){
        return $this->hasMany('App\Models\MedidaCte', 'cte_id', 'id');
    }

    public function natureza(){
        return $this->belongsTo(NaturezaOperacao::class, 'natureza_id');
    }

    public function despesas(){
        return $this->hasMany('App\Models\DespesaCte', 'cte_id', 'id');
    }

    public function receitas(){
        return $this->hasMany('App\Models\ReceitaCte', 'cte_id', 'id');
    }

    public function somaMedidas(){
        $total = 0;
        foreach($this->medidas as $d){
            $total += $d->quantidade_carga;
        }
        return $total;
    }

    public function somaComponentes(){
        $total = 0;
        foreach($this->componentes as $d){
            $total += $d->valor;
        }
        return $total;
    }

    public function somaDespesa(){
        $total = 0;
        foreach($this->despesas as $d){
            $total += $d->valor;
        }
        return $total;
    }

    public function somaReceita(){
        $total = 0;
        foreach($this->receitas as $r){
            $total += $r->valor;
        }
        return $total;
    }

    public function destinatario(){
        return $this->belongsTo(Cliente::class, 'destinatario_id');
    }

    public function veiculo(){
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

    public function remetente(){
        return $this->belongsTo(Cliente::class, 'remetente_id');
    }

    public function recebedor(){
        return $this->belongsTo(Cliente::class, 'recebedor_id');
    }

    public function expedidor(){
        return $this->belongsTo(Cliente::class, 'expedidor_id');
    }

    public function fatura(){
        return $this->hasOne(FaturaDocCte::class, 'cte_id');
    }

    public function municipioTomador(){
        return $this->belongsTo(Cidade::class, 'municipio_tomador');
    }

    public function municipioEnvio(){
        return $this->belongsTo(Cidade::class, 'municipio_envio');
    }

    public function municipioInicio(){
        return $this->belongsTo(Cidade::class, 'municipio_inicio');
    }

    public function municipioFim(){
        return $this->belongsTo(Cidade::class, 'municipio_fim');
    }

    public static function lastCTe(){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $cte = Cte::
        where('cte_numero', '!=', 0)
        ->where('empresa_id', $empresa_id)
        ->orderBy('cte_numero', 'desc')
        ->first();

        $cteos = CteOs::
        where('numero_emissao', '!=', 0)
        ->where('empresa_id', $empresa_id)
        ->orderBy('numero_emissao', 'desc')
        ->first();

        $config = ConfigNota::
        where('empresa_id', $empresa_id)
        ->first();

        $numeroConfig = $config->ultimo_numero_cte;
        $numeroCte = $cte != null ? $cte->cte_numero : 0;
        $numeroCteOs = $cteos != null ? $cteos->numero_emissao : 0;

        if($numeroCte == 0 && $numeroCteOs == 0) {
            return $numeroConfig ?? 0;
        }else{
            if($numeroConfig >= $numeroCte && $numeroConfig >= $numeroCteOs){
                return $numeroConfig;
            }else if($numeroCte > $numeroConfig && $numeroCte > $numeroCteOs){
                return $numeroCte;
            }else{
                return $numeroCteOs;
            }

        }
    }

    public static function unidadesMedida(){
        return [
            '00' => 'M3',
            '01' => 'KG',
            '02' => 'TON',
            '03' => 'UNIDADE',
            '04' => 'M2',
        ];
    }

    public static function modals(){
        return [
            '01' => 'RODOVIARIO',
            '02' => 'AEREO',
            '03' => 'AQUAVIARIO',
            '04' => 'FERROVIARIO', 
            '05' => 'DUTOVIARIO', 
            '06' => 'MULTIMODAL',
        ];
    }

    public static function tiposMedida(){
        return [
            'PESO BRUTO',
            'PESO DECLARADO',
            'PESO CUBADO',
            'PESO AFORADO', 
            'PESO AFERIDO',
            'LITRAGEM', 
            'CAIXAS'
        ];
    }

    public static function tiposServico(){
        return [
            '0' => 'Normal',
            '1' => 'Subcontratação', 
            '2' => 'Redespacho',
            '3' => 'Redespacho Intermediário',
            '4' => 'Serviço Vinculado a Multimodal'
        ];
    }

    public static function tiposTomador(){
        return [
            '0' => 'Remetente',
            '1' => 'Expedidor', 
            '2' => 'Recebedor',
            '3' => 'Destinatário'
        ];
    }

    public static function gruposCte(){
        return [
            'ide',
            'toma03',
            'toma04',
            'enderToma',
            'autXML',
            'compl',
            'ObsCont',
            'ObsFisco',
            'emit',
            'enderEmit',
            'rem',
            'enderReme',
            'infNF',
            'infOutros',
            'infUnidTransp',
            'IacUnidCarga',
            'infUnidCarga',
            'exped',
            'enderExped',
            'receb',
            'enderReceb',
            'dest',
            'enderDest',
            'vPrest',
            'Comp',
            'imp',
            'ICMS',
            'infQ',
            'docAnt'
        ];
    }

    public static function filtroData($dataInicial, $dataFinal, $estado){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $c = Cte::
        where('empresa_id', $empresa_id)
        ->whereBetween('data_registro', [$dataInicial, 
            $dataFinal]);

        if($estado != 'TODOS') $c->where('ctes.estado', $estado);

        return $c->get();
    }

    public static function filtroDataCliente($cliente, $dataInicial, $dataFinal, $estado){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $c = Cte::
        select('ctes.*')
        ->join('clientes', 'clientes.id' , '=', 'ctes.cliente_id')
        ->where('ctes.empresa_id', $empresa_id)
        ->where('clientes.razao_social', 'LIKE', "%$cliente%")

        ->whereBetween('data_registro', [$dataInicial, 
            $dataFinal]);

        if($estado != 'TODOS') $c->where('ctes.estado', $estado);
        return $c->get();
    }

    public static function filtroCliente($cliente, $estado){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $c = Cte::
        select('ctes.*')
        ->join('clientes', 'clientes.id' , '=', 'ctes.cliente_id')
        ->where('ctes.empresa_id', $empresa_id)
        ->where('clientes.razao_social', 'LIKE', "%$cliente%");

        if($estado != 'TODOS') $c->where('ctes.estado', $estado);

        return $c->get();
    }

    public static function filtroEstado($estado){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $c = Cte::
        where('ctes.empresa_id', $empresa_id)
        ->where('ctes.estado', $estado);

        return $c->get();
    }

    public static function getCsts(){
        return [
            '00' => '00 - tributação normal ICMS',
            '20' => '20 - tributação com BC reduzida do ICMS', 
            '40' => '40 - ICMS isenção',
            '41' => '41 - ICMS não tributada',
            '51' => '51 - ICMS diferido',
            '60' => '60 - ICMS cobrado por substituição tributária',
            '90' => '90 - ICMS outros',
        ];
    }

    public static function getCsosn(){
        return [
            'SN' => 'Simples Nacional',
        ];
    }
}
