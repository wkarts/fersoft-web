<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdutoIfood extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'id_ifood', 'nome', 'imagem', 'serving', 'ean', 'sellingOption_minimum',
        'sellingOption_incremental', 'sellingOption_averageUnit', 'sellingOption_availableUnits', 'descricao',
        'valor', 'categoria', 'status', 'categoria_id', 'estoque', 'id_ifood_aux'
    ];

    public function prices(){
        return $this->hasMany(PrecoProdutoIfood::class, 'produto_ifood_id');
    }

    public function categoria(){
        return $this->belongsTo(CategoriaIfood::class, 'categoria_id');
    }

    public function produto(){
        return $this->hasOne(Produto::class, 'ifood_id', 'id_ifood');
    }

}
