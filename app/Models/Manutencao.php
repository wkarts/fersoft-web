<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manutencao extends Model
{
    protected $table = 'manutencoes';
    protected $primaryKey = 'manutencao_id'; //

    protected $fillable = [
        'empresa_id', 'veiculo_id', 'responsavel_id', 'fornecedor_id', 
        'data_manutencao', 'status', 'prioridade', 'custo', 
        'km_registro', 'tipo_execucao', 'nome_oficina_externa', 
        'descricao', 'checklist'
    ];

    protected $casts = [
        'checklist' => 'array' // Resolve o erro de JSON do MySQL
    ];

    public function responsavel() {
        return $this->belongsTo(Funcionario::class, 'responsavel_id');
    }

    public function veiculo() {
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

    public function itens() {
        return $this->hasMany(ManutencaoItem::class, 'manutencao_id', 'manutencao_id');
    }
  
  public function fornecedor()
{
    // Segundo o seu SQL, a chave estrangeira é fornecedor_id
    return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
}
}