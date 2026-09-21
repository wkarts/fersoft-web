<?php

namespace App\Models;


class Servico extends BaseModel
{
    protected $fillable = [
        'nome', 'unidade_cobranca', 'valor', 'categoria_id', 'empresa_id', 'tempo_servico',
        'tempo_adicional', 'valor_adicional', 'tempo_tolerancia', 'codigo_servico', 'comissao',
        'aliquota_iss', 'aliquota_pis', 'aliquota_cofins', 'aliquota_inss',
        'codigo_tributacao_nacional', 'codigo_nbs', 'codigo_tributacao_municipio',
        'cst_ibscbs', 'aliquota_ibs', 'aliquota_cbs', 'codigo_class_trib', 'exige_obra'
    ];

    protected $casts = [
        'aliquota_ibs' => 'decimal:2',
        'aliquota_cbs' => 'decimal:2',
        'exige_obra' => 'boolean',
    ];

    public function categoria(){
        return $this->belongsTo(CategoriaServico::class, 'categoria_id');
    }
}
