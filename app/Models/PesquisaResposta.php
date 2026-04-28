<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PesquisaResposta extends BaseModel
{
    use HasFactory;
    protected $fillable = [
        'resposta', 'empresa_id', 'nota', 'pesquisa_id'
    ];

    public function empresa(){
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
