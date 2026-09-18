<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemContaEmpresa extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'conta_id',
        'descricao',
        'tipo_pagamento',
        'valor',
        'user_id',
        'caixa_id',
        'tipo',
        'origem',
        'saldo_atual',
        'data_pagamento',
        'categoria_id',
        'empresa_id',
        'conta_pagar_id',
       'conta_receber_id'
    ];

    public function conta(){
        return $this->belongsTo(ContaEmpresa::class, 'conta_id');
    }

    public function caixa(){
        return $this->belongsTo(AberturaCaixa::class, 'caixa_id');
     
    }
  
    public function categoria()
{
    // Estamos dizendo que o item pertence a uma categoria da tabela 'categoria_contas'
    return $this->belongsTo(\App\Models\CategoriaConta::class, 'categoria_id');
}
  
  public function usuario()
{
    // O nome da classe no ficheiro Usuario.php é 'Usuario'
    return $this->belongsTo(\App\Models\Usuario::class, 'user_id');
}
  
}
