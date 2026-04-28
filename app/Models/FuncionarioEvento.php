<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FuncionarioEvento extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'evento_id', 'funcionario_id', 'condicao', 'metodo', 'valor', 'ativo'
    ];

    public function evento(){
        return $this->belongsTo(EventoSalario::class, 'evento_id');
    }

    public function funcionario(){
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }
}
