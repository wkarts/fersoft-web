<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BairroDelivery;
use App\Models\CidadeDelivery;

class BairroDeliveryController extends Controller
{
	protected $empresa_id = null;
	public function __construct(){
		$this->middleware(function ($request, $next) {
			$this->empresa_id = $request->empresa_id;
			$value = session('user_logged');
			if(!$value){
				return redirect("/login");
			}
			return $next($request);
		});
	}

	public function index(){
		$bairros = BairroDelivery::
		orderBy('id', 'desc')
		->paginate(15);

		$cidades = CidadeDelivery::
		orderBy('nome', 'desc')
		->get();

		$totalRegistros = BairroDelivery::count();
		return view('bairros/list')
		->with('bairros', $bairros)
		->with('cidades', $cidades)
		->with('totalRegistros', $totalRegistros)
		->with('title', 'Bairros');
	}

	public function filtro(Request $request){
		$bairros = BairroDelivery::
		orderBy('id', 'desc');

		if($request->cidade_id){
			$bairros->where('cidade_id', $request->cidade_id);
		}
		if($request->pesquisa){
			$bairros->where('nome', 'LIKE', "%$request->pesquisa%");
		}
		$bairros = $bairros->paginate(1);

		$cidades = CidadeDelivery::
		orderBy('nome', 'desc')
		->get();

		$totalRegistros = BairroDelivery::count();

		return view('bairros/list')
		->with('bairros', $bairros)
		->with('totalRegistros', $totalRegistros)
		->with('cidades', $cidades)
		->with('cidade_id', $request->cidade_id)
		->with('pesquisa', $request->pesquisa)
		->with('title', 'Bairros');
	}

	public function new(){

		$cidades = CidadeDelivery::
		orderBy('nome', 'desc')
		->get();
		return view('bairros/register')
		->with('cidades', $cidades)
		->with('title', 'Cadastrar Bairro');
	}

	public function save(Request $request){
		$bairro = new BairroDelivery();
		$this->_validate($request);

		$request->merge(['valor_entrega' => str_replace(",", ".", $request->valor_entrega)]);

		$result = $bairro->create($request->all());

		if($result){
			session()->flash("mensagem_sucesso", "Bairro cadastrado com sucesso.");
		}else{
			session()->flash('mensagem_erro', 'Erro ao cadastrar bairro.');

		}

		return redirect('/bairrosDelivery');
	}

	public function edit($id){
		$bairro = new BairroDelivery(); 

		$resp = $bairro
		->where('id', $id)->first();

		$cidades = CidadeDelivery::
		orderBy('nome', 'desc')
		->get();

		return view('bairros/register')
		->with('bairro', $resp)
		->with('cidades', $cidades)
		->with('title', 'Editar Bairro');

	}

	public function update(Request $request){
		$bairro = new BairroDelivery();
		$request->merge(['valor_entrega' => str_replace(",", ".", $request->valor_entrega)]);
		
		$id = $request->input('id');
		$resp = $bairro
		->where('id', $id)->first(); 

		$this->_validate($request);


		$resp->nome = $request->input('nome');
		$resp->valor_entrega = $request->input('valor_entrega');

		$result = $resp->save();
		if($result){
			session()->flash('mensagem_sucesso', 'Bairro editado com sucesso!');
		}else{
			session()->flash('mensagem_erro', 'Erro ao editar bairro!');
		}

		return redirect('/bairrosDelivery'); 
	}

	public function delete($id){

		$delete = BairroDelivery
		::where('id', $id)
		->delete();
		if($delete){
			session()->flash('mensagem_sucesso', 'Registro removido!');
		}else{
			session()->flash('mensagem_erro', 'Erro!');
		}
		return redirect('/bairrosDelivery');

	}


	private function _validate(Request $request){
		$rules = [
			'nome' => 'required|max:50',
			'valor_entrega' => 'required',
			'cidade_id' => 'required',
		];

		$messages = [
			'nome.required' => 'O campo nome é obrigatório.',
			'nome.max' => '50 caracteres maximos permitidos.',
			'valor_entrega.required' => 'O campo valor de entrega é obrigatório.',
			'cidade_id.required' => 'O campo cidade é obrigatório.',

		];
		$this->validate($request, $rules, $messages);
	}
}
