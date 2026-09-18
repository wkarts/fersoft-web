<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TabelaPrecoNfe extends Model
{
    // Avisa o Laravel o nome exato da tabela
    protected $table = 'tabela_preco_nfes';

    protected $fillable = ['empresa_id', 'produto_id', 'preco_nfe'];
}