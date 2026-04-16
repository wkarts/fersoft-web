<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adiantamento extends Model
{
    // Adicione o 'filial_id' aqui na lista!
    protected $fillable = [
        'empresa_id', 'filial_id', 'cliente_id', 'fornecedor_id', 'valor_total', 
        'valor_utilizado', 'data', 'status', 'descricao', 'item_conta_empresa_id'
    ];

    public function cliente() {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function fornecedor() {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function getSaldoAttribute() {
        return $this->valor_total - $this->valor_utilizado;
    }
  
    public function movimentacoes()
    {
    // Um adiantamento pode ter muitas movimentações de baixa
    return $this->hasMany(AdiantamentoMovimentacao::class, 'adiantamento_id');
    }
}