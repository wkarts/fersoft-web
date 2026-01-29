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
        'data_saida',

        // 1) ICMS Monofásico - Totais
        'TOTAL_QBCMONO',
        'TOTAL_ICMSMONO',
        'TOTAL_QBCMONORETEN',
        'TOTAL_ICMSMONORETEN',
        'TOTAL_QBCMONORET',
        'TOTAL_ICMSMONORET',

        // 2) Destino operação
        'DESTINO_OPERACAO',

        // 3) Retenções (IRRF/PIS/COFINS/CSLL)
        'RET_BC_IRRF',
        'RET_ALIQ_IRRF',
        'RET_VIRRF',

        'RET_BC_PIS',
        'RET_ALIQ_PIS',
        'RET_VPIS',

        'RET_BC_COFINS',
        'RET_ALIQ_COFINS',
        'RET_VCOFINS',

        'RET_BC_CSLL',
        'RET_ALIQ_CSLL',
        'RET_VCSLL',

        'FLAG_NORMATIVA_IRRF',

        // 4) Totais adicionais
        'TOTAL_IPI_DEVOLVIDO',
        'FK_PES_RETIRADA',
        'FLAG_END_ENTREGA',
        'TOTAL_II',

        // 5) Receituário / Responsável técnico
        'NRECEITUARIO',
        'CPFRESPTEC',

        // 6) Guia de Trânsito
        'TIPO_GUIA_TRANSITO',
        'UF_GUIA_TRANSITO',
        'SERIE_GUIA_TRANSITO',
        'NUM_GUIA_TRANSITO',

        // 7) IBS/CBS/IS - Totais
        'TOTAL_IS',
        'TOTAL_BC_IBS_CBS',
        'TOTAL_IBS',
        'TOTAL_IBS_CRED_PRES',
        'TOTAL_IBS_CRED_PRES_COND_SUS',

        'TOTAL_IBS_UF_DIF',
        'TOTAL_IBS_UF_DEV_TRIB',
        'TOTAL_IBS_UF',

        'TOTAL_IBS_MUN_DIF',
        'TOTAL_IBS_MUN_DEV_TRIB',
        'TOTAL_IBS_MUN',

        'TOTAL_CBS_DIF',
        'TOTAL_CBS_DEV_TRIB',
        'TOTAL_CBS',
        'TOTAL_CBS_CRED_PRES',
        'TOTAL_CBS_CRED_PRES_COND_SUS',

        'TOTAL_IBS_MONO',
        'TOTAL_CBS_MONO',
        'TOTAL_IBS_MONO_RETEN',
        'TOTAL_CBS_MONO_RETEN',
        'TOTAL_IBS_MONO_RET',
        'TOTAL_CBS_MONO_RET',

        'TOTAL_NF_IBC_CBS_IS',
        'TOTAL_IBS_CBS',

        'TIPO_NFCREDITO',
        'TIPO_NFDEBITO',
        'TIPO_ENTEGOV',
        'PERC_REDUTOR_GOV',
        'TIPO_OPERGOV',

        // 8) Padrão Eloquent (sem ID) - campos adicionais
        'ELOQUENT_UUID',
        'CREATED_AT',
        'UPDATED_AT',
        'DELETED_AT',
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
