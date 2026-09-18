<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemLocacao extends BaseModel
{
    protected $fillable = [
        'locacao_id',
        'produto_id',
        'valor',
        'observacao',
        'codigo_patrimonio',
        'horimetro_inicial',
        'horimetro_final'
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function locacao()
    {
        return $this->belongsTo(Locacao::class, 'locacao_id');
    }
}
