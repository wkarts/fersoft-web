<?php

namespace App\Models;


class LancamentoCategoria extends BaseModel
{
    protected $fillable = [
        'categoria_id', 'nome', 'valor', 'percentual'
    ];

    public function categoria(){
        return $this->belongsTo(DreCategoria::class, 'categoria_id');
    }

}
