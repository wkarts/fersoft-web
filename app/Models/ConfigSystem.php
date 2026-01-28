<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigSystem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cor', 'mensagem_plano_indeterminado', 'inicio_mensagem_plano', 'fim_mensagem_plano', 'valor_base_contrato',
        'usuario_correios', 'codigo_acesso_correios', 'cartao_postagem_correios', 'token_integra_notas'
    ];
}
