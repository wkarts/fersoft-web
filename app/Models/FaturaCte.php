<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FaturaCte extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_fatura', 'vencimento', 'valor_total', 'desconto', 'empresa_id', 'remetente_id',
        'observacao', 'conta_receber_id'
    ];

    public function remetente(){
        return $this->belongsTo(Cliente::class, 'remetente_id');
    }

    public function documentos(){
        return $this->hasMany(FaturaDocCte::class, 'fatura_id');
    }
}
