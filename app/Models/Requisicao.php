<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Requisicao extends Model
{
    protected $table = 'requisicoes';
    
    protected $fillable = [
        'empresa_id', 
        'funcionario_id', 
        'responsavel_id', 
        'unidade', 
        'data_requisicao', 
        'observacao', 
        'status'
    ];

    // Relacionamento com o Funcionário que recebeu
    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }

    // Relacionamento com o Usuário que entregou
    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    // Relacionamento com os itens da requisição
    public function itens()
    {
        return $this->hasMany(RequisicaoItem::class, 'requisicao_id');
    }
}