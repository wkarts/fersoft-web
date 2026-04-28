<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class RemessaNfe extends BaseModel
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
        'total_qbcmono',
        'total_icmsmono',
        'total_qbcmonoreten',
        'total_icmsmonoreten',
        'total_qbcmonoret',
        'total_icmsmonoret',

        // 2) Destino operação
        'destino_operacao',

        // 3) Retenções (IRRF/PIS/COFINS/CSLL)
        'ret_bc_irrf',
        'ret_aliq_irrf',
        'ret_virrf',

        'ret_bc_pis',
        'ret_aliq_pis',
        'ret_vpis',

        'ret_bc_cofins',
        'ret_aliq_cofins',
        'ret_vcofins',

        'ret_bc_csll',
        'ret_aliq_csll',
        'ret_vcsll',

        'flag_normativa_irrf',

        // 4) Totais adicionais
        'total_ipi_devolvido',
        'fk_pes_retirada',
        'flag_end_entrega',
        'total_ii',

        // 5) Receituário / Responsável técnico
        'nreceituario',
        'cpfresptec',

        // 6) Guia de Trânsito
        'tipo_guia_transito',
        'uf_guia_transito',
        'serie_guia_transito',
        'num_guia_transito',

        // 7) IBS/CBS/IS - Totais
        'total_is',
        'total_bc_ibs_cbs',
        'total_ibs',
        'total_ibs_cred_pres',
        'total_ibs_cred_pres_cond_sus',

        'total_ibs_uf_dif',
        'total_ibs_uf_dev_trib',
        'total_ibs_uf',

        'total_ibs_mun_dif',
        'total_ibs_mun_dev_trib',
        'total_ibs_mun',

        'total_cbs_dif',
        'total_cbs_dev_trib',
        'total_cbs',
        'total_cbs_cred_pres',
        'total_cbs_cred_pres_cond_sus',

        'total_ibs_mono',
        'total_cbs_mono',
        'total_ibs_mono_reten',
        'total_cbs_mono_reten',
        'total_ibs_mono_ret',
        'total_cbs_mono_ret',

        'total_nf_ibc_cbs_is',
        'total_ibs_cbs',

        'tipo_nfcredito',
        'tipo_nfdebito',
        'tipo_entegov',
        'perc_redutor_gov',
        'tipo_opergov',

        // 8) Padrão Eloquent (sem ID) - campos adicionais
        'eloquent_uuid',
        'created_at',
        'updated_at',
        'deleted_at',
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
