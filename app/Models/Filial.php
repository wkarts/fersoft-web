<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Filial extends BaseModel
{
    use HasFactory;

    protected $fillable = [ 
        'status', 'razao_social', 'nome_fantasia', 'cnpj', 'ie', 'logradouro','numero', 'bairro', 
        'municipio', 'codMun', 'pais', 'codPais','fone', 'cep', 'UF', 'complemento', 'nat_op_padrao', 'ambiente', 
        'cUF', 'ultimo_numero_nfe', 'ultimo_numero_nfce', 'ultimo_numero_cte', 'ultimo_numero_mdfe',
        'numero_serie_nfe', 'numero_serie_nfce', 'numero_serie_cte', 'csc', 'csc_id', 'inscricao_municipal', 
        'aut_xml', 'logo', 'arquivo_certificado', 'senha_certificado', 'descricao', 'empresa_id', 'numero_serie_mdfe', 'email',
        'permitir_estoque_negativo'
    ];

    public function natureza(){
        return $this->belongsTo(NaturezaOperacao::class, 'nat_op_padrao');
    }
}
