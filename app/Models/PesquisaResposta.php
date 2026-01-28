<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesquisaResposta extends Model
{
    use HasFactory;
    protected $fillable = [
        'resposta', 'empresa_id', 'nota', 'pesquisa_id'
    ];

    public function empresa(){
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
