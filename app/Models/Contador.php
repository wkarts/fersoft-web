<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contador extends Model
{
    use HasFactory;
    protected $fillable = [
        'razao_social', 'nome_fantasia', 'cnpj', 'ie', 'logradouro',
        'numero', 'bairro', 'fone', 'email', 'cep', 'percentual_comissao', 'cidade_id',
        'cadastrado_por_cliente', 'agencia', 'conta', 'banco', 'chave_pix', 'dados_bancarios',
        'contador_parceiro', 'empresa_id', 'representante_id'
    ];

    public function cidade(){
        return $this->belongsTo(Cidade::class, 'cidade_id');
    }

    public function empresa(){
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function representante(){
        return $this->belongsTo(Representante::class, 'representante_id');
    }

    public function representanteExt(){
        return $this->belongsTo(Usuario::class, 'representante_id');
    }

    public function empresasDoContador(){
        return $this->hasMany(Empresa::class, 'contador_id');
    }

}
