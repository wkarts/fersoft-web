<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NaturezaOperacao extends Model
{
    protected $fillable = [
		'natureza', 'CFOP_entrada_estadual', 'CFOP_entrada_inter_estadual',
		'CFOP_saida_estadual', 'CFOP_saida_inter_estadual', 'empresa_id', 'sobrescreve_cfop',
		'finNFe', 'nao_movimenta_estoque', 'CST_CSOSN', 'categoria_conta_id'
	];

	public static function finalidades(){
		return [
			'1' => '1 - NF-e normal',
			'2' => '2 - NF-e complementar',
			'3' => '3 - NF-e de ajuste',
			'4' => '4 - Devolução de mercadoria'
		];
	}

	public function getFinalidade(){
		$finalidades = NaturezaOperacao::finalidades();
		return $finalidades[$this->finNFe == 0 ? 1 : $this->finNFe];
	}
}
