<?php

namespace App\Models;

use Faker\Provider\Base;
use Illuminate\Database\Eloquent\Model;

class Fornecedor extends BaseModel
{
    protected $fillable = [
        'razao_social',
        'nome_fantasia',
        'bairro',
        'numero',
        'rua',
        'cpf_cnpj',
        'telefone',
        'celular',
        'email',
        'cep',
        'ie_rg',
        'cidade_id',
        'empresa_id',
        'contribuinte',
        'pix',
      	'tabela_preco_id',
        'tipo_pix',
        'complemento',
        'cod_pais',
        'id_estrangeiro',
        'latitude',
        'longitude',
        'imagem',
        'banco',
        'agencia',
        'conta'
    ];

    protected $appends = ['imgApp'];

    public function getImgAppAttribute()
    {
        if (empty($this->imagem)) {
            return env("PATH_URL") . "/imgs/no_fornecedores.png";
        }
        return env("PATH_URL") . "/imgs_fornecedores/{$this->imagem}";
    }

    public function cidade(){
        return $this->belongsTo(Cidade::class, 'cidade_id');
    }

    public static function verificaCadastrado($cnpj){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $forn = Fornecedor::where('cpf_cnpj', $cnpj)
            ->where('empresa_id', $empresa_id)
            ->first();

        return $forn;
    }

    public static function tiposDePix(){
        return [
            'cpf',
            'cnpj',
            'email',
            'telefone',
            'chave aleatória'
        ];
    }

    public function getPais(){
        $pais = Pais::where('codigo', $this->cod_pais)->first();
        return $pais->nome;
    }

    public function setCpfCnpjAttribute($value)
    {
        $this->attributes['cpf_cnpj'] = preg_replace('/\D/', '', (string)$value);
    }

    public function setCepAttribute($value)
    {
        $this->attributes['cep'] = preg_replace('/\D/', '', (string)$value);
    }

 	public function tabelaPreco()
    {
        return $this->belongsTo(\App\Models\TabelaPreco::class, 'tabela_preco_id');
    }

}
