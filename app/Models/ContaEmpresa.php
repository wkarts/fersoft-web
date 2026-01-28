<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContaEmpresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'nome', 'banco', 'agencia', 'conta', 'plano_conta_id', 'saldo_inicial', 'status', 'saldo'
    ];

     public function plano(){
        return $this->belongsTo(PlanoConta::class, 'plano_conta_id');
    }
}
