<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanoContasContabil extends Model
{
    use HasFactory;

    // Aponta para a tabela correta no banco
    protected $table = 'plano_contas_contabil';

    // Libera os campos para o insert
    protected $fillable = [
        'empresa_id',
        'codigo_acesso',
        'classificador',
        'nome',
        'aceita_lancamento',
    ];
}