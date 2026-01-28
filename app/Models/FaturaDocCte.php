<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaturaDocCte extends Model
{
    use HasFactory;

    protected $fillable = [
        'fatura_id', 'cte_id', 'unidade', 'cte_numero', 'chave_nfe', 'valor_mercadoria',
        'peso', 'frete'
    ];

    public function cte(){
        return $this->belongsTo(Cte::class, 'cte_id');
    }
}
