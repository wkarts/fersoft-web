<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cotacao;
use App\Models\ItemCotacao;
use App\Models\ConfigNota;

class CotacaoResponseController extends Controller
{	

	public function response($link){

		$cotacao = Cotacao::
		where('link', $link)
		->where('ativa', true)
		->where('resposta', false)
		->first();

		if($cotacao){
			$config = ConfigNota::where('empresa_id', $cotacao->empresa_id)->first();
			$logo = $config->logo;

			return view('cotacao/response')
			->with('config', $config)
			->with('logo', $logo)
			->with('cotacao', $cotacao);
		}else{
			session()->flash("mensagem_erro", "Cotação finalizada!");
			return redirect()->route('catacao.finish');
		}
	}

	public function save(Request $request){
		$cotacao = Cotacao::findOrFail($request->cotacao_id);
		try{
			$total = 0;
			for($i=0; $i<sizeof($request->valor); $i++){
				$total += __replace($request->valor[$i]) * __replace($request->quantidade[$i]);
			}
			$cotacao->valor = $total;
			$cotacao->forma_pagamento = $request->forma_pagamento ?? '';
			$cotacao->responsavel = $request->responsavel ?? '';
			$cotacao->resposta = true;
			$cotacao->save();

			for($i=0; $i<sizeof($request->valor); $i++){
				$item = ItemCotacao::findOrFail($request->item_id[$i]);
				$item->valor_unitario = __replace($request->valor[$i]);
				$item->valor = __replace($request->valor[$i]) * __replace($request->quantidade[$i]);
				$item->save();
			}

			session()->flash("mensagem_sucesso", "Cotação respondida!");
		}catch(\Exception $e){
			session()->flash("mensagem_erro", "Algo deu errado: " . $e->getMessage());
		}
		return redirect()->route('catacao.finish');
	}

	public function finish(){
		return view('cotacao/finish');
	}

	public function responseSave(Request $request){
		$data = $request->js;


		$cotacao = Cotacao::
		where('id', $data['cotacao_id'])
		->first();

		// $total = str_replace(".", "", $data['total']);
		$total = str_replace(",", ".", $data['total']);

		$cotacao->valor = $total;
		$cotacao->forma_pagamento = $data['forma_pagamento'] ?? '';
		$cotacao->responsavel = $data['responsavel'] ?? '';
		$cotacao->resposta = true;
		$result = $cotacao->save();

		foreach($data['itens'] as $i){
			$item = ItemCotacao::
			where('id', $i['id'])
			->first();

			$v = str_replace(".", "", $i['valor']);
			$v = str_replace(",", ".", $v);
			$item->valor = $v;
			$item->save();
		}

		echo json_encode($result);
	}
}
