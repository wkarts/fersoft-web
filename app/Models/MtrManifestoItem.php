<?php

namespace App\Models;

class MtrManifestoItem extends BaseModel
{
    protected $table = 'mtr_manifesto_itens';

    protected $fillable = [
        'empresa_id', 'filial_id', 'usuario_id', 'mtr_manifesto_id',
        'produto_id', 'cod_ibama', 'descricao_residuo', 'quantidade',
        'unidade_medida', 'estado_fisico', 'classe_residuo',
        'acondicionamento_id', 'tratamento_id',
    ];

    protected $casts = [
        'quantidade' => 'decimal:4',
    ];

    public function manifesto()
    {
        return $this->belongsTo(MtrManifesto::class, 'mtr_manifesto_id');
    }
}
