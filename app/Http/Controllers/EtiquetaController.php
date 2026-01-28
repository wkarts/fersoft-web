<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Etiqueta;
class EtiquetaController extends Controller
{
	public function index(){
		$etiquetas = Etiqueta::
		where('empresa_id', null)
		->get();
		$title = 'Etiquetas Super';
		return view('etiquetas/list', compact('title', 'etiquetas'));
	}

	public function new(){
		return view('etiquetas/register')
		->with('title', 'Nova etiqueta');
	}

	public function edit($id){
		$etiqueta = Etiqueta::find($id);
		return view('etiquetas/register')
		->with('etiqueta', $etiqueta)
		->with('title', 'Nova etiqueta');
	}

	public function save(Request $request){
		$this->_validate($request);

		try{
			$request->merge([
				'nome_empresa' => $request->nome_empresa ? true : false,
				'nome_produto' => $request->nome_produto ? true : false,
				'valor_produto' => $request->valor_produto ? true : false,
				'codigo_produto' => $request->codigo_produto ? true : false,
				'tipo' => $request->tipo,
				'codigo_barras_numerico' => $request->codigo_barras_numerico ? true : false,
				'observacao' => $request->observacao ?? '',
				'empresa_id' => NULL
			]);
			Etiqueta::create($request->all());
			session()->flash('mensagem_sucesso', "Etiqueta cadastrada");

		}catch(\Exception $e){
			session()->flash('mensagem_erro', $e->getMessage());
		}
		return redirect('/etiquetas');
	}

	public function update(Request $request){
		$this->_validate($request);
		try{
			$etiqueta = Etiqueta::find($request->id);
			$etiqueta->nome_empresa = $request->nome_empresa ? true : false;
			$etiqueta->nome_produto = $request->nome_produto ? true : false;
			$etiqueta->valor_produto = $request->valor_produto ? true : false;
			$etiqueta->codigo_produto = $request->codigo_produto ? true : false;
			$etiqueta->codigo_barras_numerico = $request->codigo_barras_numerico ? true : false;
			$etiqueta->observacao = $request->observacao ?? '';

			$etiqueta->nome = $request->nome;
			$etiqueta->distancia_etiquetas_lateral = $request->distancia_etiquetas_lateral;
			$etiqueta->altura = $request->altura;
			$etiqueta->largura = $request->largura;
			$etiqueta->tipo = $request->tipo;
			$etiqueta->etiquestas_por_linha = $request->etiquestas_por_linha;
			$etiqueta->distancia_etiquetas_topo = $request->distancia_etiquetas_topo;
			$etiqueta->quantidade_etiquetas = $request->quantidade_etiquetas;
			$etiqueta->tamanho_fonte = $request->tamanho_fonte;
			$etiqueta->tamanho_codigo_barras = $request->tamanho_codigo_barras;
			$etiqueta->save();
			session()->flash('mensagem_sucesso', "Etiqueta atualiada!");
		}catch(\Exception $e){
			session()->flash('mensagem_erro', $e->getMessage());
		}
		return redirect('/etiquetas');
	}

	private function _validate(Request $request){
		$rules = [
			'nome' => 'required',
			'altura' => 'required',
			'largura' => 'required',
			'etiquestas_por_linha' => 'required',
			'distancia_etiquetas_lateral' => 'required',
			'distancia_etiquetas_topo' => 'required',
			'quantidade_etiquetas' => 'required',
			'tamanho_fonte' => 'required',
			'tamanho_codigo_barras' => 'required',
		];

		$messages = [
			'nome.required' => 'Campo obrigatório.',
			'altura.required' => 'Campo obrigatório.',
			'largura.required' => 'Campo obrigatório.',
			'etiquestas_por_linha.required' => 'Campo obrigatório.',
			'distancia_etiquetas_lateral.required' => 'Campo obrigatório.',
			'distancia_etiquetas_topo.required' => 'Campo obrigatório.',
			'quantidade_etiquetas.required' => 'Campo obrigatório.',
			'tamanho_fonte.required' => 'Campo obrigatório.',
			'tamanho_codigo_barras.required' => 'Campo obrigatório.',
		];
		$this->validate($request, $rules, $messages);
	}

	public function delete($id){
		try{
			Etiqueta::find($id)->delete();
			session()->flash('mensagem_sucesso', "Etiqueta removida");

		}catch(\Exception $e){
			session()->flash('mensagem_erro', $e->getMessage());
		}
		return redirect('/etiquetas');
	}
}
