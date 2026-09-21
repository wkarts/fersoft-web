<?php

namespace App\Models;


class Servico extends BaseModel
{
    protected $fillable = [
        'nome', 'unidade_cobranca', 'valor', 'categoria_id', 'empresa_id', 'tempo_servico',
        'tempo_adicional', 'valor_adicional', 'tempo_tolerancia', 'codigo_servico', 'comissao',
        'aliquota_iss', 'aliquota_pis', 'aliquota_cofins', 'aliquota_inss'
    ];

    public function categoria(){
        return $this->belongsTo(CategoriaServico::class, 'categoria_id');
    }
}
