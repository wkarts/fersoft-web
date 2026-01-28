<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mesa;
use Dompdf\Dompdf;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
class MesaController extends Controller
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
		$mesas = Mesa::
		where('empresa_id', $this->empresa_id)
		->get();
		return view('mesas/list')
		->with('mesas', $mesas)
		->with('title', 'Mesas');
	}

	public function new(){
		return view('mesas/register')
		->with('title', 'Cadastrar Mesa');
	}

	public function save(Request $request){
		$mesa = new Mesa();
		$this->_validate($request);
		try{
			$request->merge([
				'token' => Str::random(25)
			]);

			$result = $mesa->create($request->all());

			session()->flash("mensagem_sucesso", "Mesa cadastrada com sucesso.");

		}catch(\Exception $e){
			session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
		}

		return redirect('/mesas');
	}

	public function gerarToken($id){
		$item = Mesa::findOrFail($id);
		if(valida_objeto($item)){
			$item->token = Str::random(25);
			$item->save();

			session()->flash("mensagem_sucesso", "Token gerado.");
			return redirect('/mesas');

		}else{
			return redirect('/403');
		}
	}

	public function edit($id){
		$mesa = new Mesa(); 

		$resp = $mesa
		->where('id', $id)->first();  

		if(valida_objeto($resp)){
			return view('mesas/register')
			->with('mesa', $resp)
			->with('title', 'Editar Mesa');
		}else{
			return redirect('/403');
		}

	}

	public function update(Request $request){
		$this->_validate($request);
		$mesa = new Mesa();
		try{
			$id = $request->input('id');
			$item = $mesa->where('id', $id)->first(); 

			$item->nome = $request->input('nome');

			if($item->token == ""){
				$item->token = Str::random(25);
			}
			$item->save();
			session()->flash("mensagem_sucesso", "Mesa atualizada com sucesso.");
		}catch(\Exception $e){
			session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
		}

		return redirect('/mesas'); 
	}

	public function delete($id){

		$mesa = Mesa
		::where('id', $id)
		->first();
		if(valida_objeto($mesa)){
			try{
				$mesa->delete();
				session()->flash("mensagem_sucesso", "Mesa removida com sucesso.");

			}catch(\Exception $e){
				session()->flash('mensagem_erro', 'Algo deu errado: ' . $e->getMessage());
			}

			return redirect('/mesas');
		}else{
			return redirect('/403');
		}

	}


	private function _validate(Request $request){
		$rules = [
			'nome' => 'required|max:50',
		];

		$messages = [
			'nome.required' => 'O campo nome é obrigatório.',
			'nome.max' => '50 caracteres maximos permitidos.',

		];
		$this->validate($request, $rules, $messages);
	}

	public function gerarQrCode(Request $request){
		$url = $request->url;
		$mesas = Mesa::all();

		return view('mesas/qrCode')
		->with('mesas', $mesas)
		->with('url', $url)
		->with('title', 'Mesas QrCode');
	}


	public function imprimirQrCode(Request $request){
		$url = $request->url;

		return view('mesas/verQrCode')
		->with('url', $url)
		->with('title', 'QrCode');
	}
	
}
