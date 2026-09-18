<?php

namespace App\Models;

class OsChecklist extends BaseModel
{
    protected $table = 'os_checklists';

    protected $fillable = [
        'ordem_servico_id',
        'item_avaliado',
        'situacao',
        'executado',
        'observacao'
    ];

    public function ordemServico()
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }
}
