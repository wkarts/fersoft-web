<?php

namespace App\Models;


class Servico extends BaseModel
{
    protected $fillable = [
        'nome',
        'unidade_cobranca',
        'valor',
        'categoria_id',
        'empresa_id',
        'tempo_servico',
        'tempo_adicional',
        'valor_adicional',
        'tempo_tolerancia',
        'codigo_servico',
        'codigo_tributacao_nacional',
        'comissao',
        'aliquota_iss',
        'aliquota_pis',
        'aliquota_cofins',
        'aliquota_inss',
        'codigo_nbs',
        'cst_ibscbs',
        'aliquota_ibs',
        'aliquota_cbs',
        'codigo_class_trib',
        'iss_retido'
    ];

    public function categoria(){
        return $this->belongsTo(CategoriaServico::class, 'categoria_id');
    }
}
