<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplementoDelivery extends Model
{
	protected $fillable = [
		'nome', 'valor', 'categoria', 'empresa_id', 'tipo'
	];

	public function nome(){
		$nome = explode('>', $this->nome);
		if(sizeof($nome) > 1) return $nome[1];
		return $this->nome;
	}

	public function setCategoriaAttribute($value)
	{
		$this->attributes['categoria'] = json_encode($value);
	}

	public function _categorias(){
		$cat = "";
		foreach(json_decode($this->categoria) as $c){
			$categoria = CategoriaProdutoDelivery::find($c);
			if($categoria != null){
				$cat .= $categoria->nome . " | ";
			}
		}
		$cat = substr($cat, 0, strlen($cat)-2);
		return $cat;
	}

    /**
     * Get the categories
     *
     */
    
}
