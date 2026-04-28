<?php

namespace App\Models;


class MunicipioCarregamento extends BaseModel
{
    protected $fillable = [
        'cidade_id', 'mdfe_id'
    ];

    public function cidade(){
        return $this->belongsTo(Cidade::class, 'cidade_id');
    }
}
