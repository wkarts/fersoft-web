<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Acessor extends BaseModel
{

	protected $fillable = [
		'razao_social', 'bairro', 'numero', 'rua', 'cpf_cnpj', 'telefone', 
		'celular', 'email', 'cep', 'cidade_id', 'empresa_id', 'data_registro', 
		'percentual_comissao', 'ativo', 'funcionario_id', 'tipo_comissao'
	];

	public function cidade(){
		return $this->belongsTo(Cidade::class, 'cidade_id');
	}

	public function comissoes(){
		return $this->hasMany(ComissaoAssessor::class, 'assessor_id');
	}
}
