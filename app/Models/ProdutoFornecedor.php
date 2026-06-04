<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdutoFornecedor extends Model
{
    // ISSO GARANTE QUE ELE SÓ VAI LER OS VÍNCULOS DA EMPRESA LOGADA
    use MultiEmpresaTrait; 

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'produto_id', 
        'fornecedor_id', 
        'codigo_fornecedor', 
        'descricao_fornecedor', 
        'codigo_barras_fornecedor'
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}