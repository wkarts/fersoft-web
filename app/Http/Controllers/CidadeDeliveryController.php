<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CidadeDelivery;
class CidadeDeliveryController extends BaseController
{
    /* --- Contrato obrigatório do BaseController --- */
    protected $model = CidadeDelivery::class;
    protected $resource = 'cidadeDelivery';
    protected $formTitle = 'Cidades Delivery';
    protected $listView = 'cidadeDelivery.list';
    protected $registerView = 'cidadeDelivery.register';
    protected $redirectPage = '/cidadeDelivery';

    public function rules(): array
    {
        return [
                    'nome' => 'required|max:50',
                    'cep' => 'required|max:9',
                    'uf' => 'required',
                ];
    }

    public function messages(): array
    {
        return [
                    'nome.required' => 'O campo nome é obrigatório.',
                    'nome.max' => '50 caracteres maximos permitidos.',
                    'cep.required' => 'O campo cep é obrigatório.',
                    'cep.max' => '9 caracteres maximos permitidos.',
                    'uf.required' => 'O campo uf é obrigatório.',
                ];
    }
    /* --- Fim contrato BaseController --- */

	protected $empresa_id = null;
	public function __construct(){
		parent::__construct();
		$this->middleware(function ($request, $next) {
			$this->empresa_id = $request->empresa_id;
			$value = session('user_logged');
			if(!$value){
				return redirect("/login");
			}

			if(!$value['super']){
				return redirect('/graficos');
			}
			return $next($request);
		});
	}

	public function index(){
		$cidades = CidadeDelivery::all();
		return view('cidadeDelivery/list')
		->with('cidades', $cidades)
		->with('title', 'Cidade Delivery');
	}

	public function new(){
		return view('cidadeDelivery/register')
		->with('title', 'Cadastrar Cidade');
	}

	public function save(Request $request){
		$cidade = new CidadeDelivery();
		$this->_validate($request);

		$result = $cidade->create($request->all());

		if($result){
			session()->flash("mensagem_sucesso", "Cidade cadastrada com sucesso.");
		}else{
			session()->flash('mensagem_erro', 'Erro ao cadastrar cidade.');
		}

		return redirect('/cidadeDelivery');
	}

	public function edit($id){
		$cidade = new CidadeDelivery(); 

		$resp = $cidade
		->where('id', $id)->first();  

		return view('cidadeDelivery/register')
		->with('cidade', $resp)
		->with('title', 'Editar Cidade');

	}

	public function update(Request $request, $id = null){
		$cidade = new CidadeDelivery();

		$id = $request->input('id');
		$resp = $cidade
		->where('id', $id)->first(); 

		$this->_validate($request);

		$resp->nome = $request->input('nome');
		$resp->uf = $request->input('uf');
		$resp->cep = $request->input('cep');

		$result = $resp->save();
		if($result){
			session()->flash('mensagem_sucesso', 'Cidade editada com sucesso!');
		}else{
			session()->flash('mensagem_erro', 'Erro ao editar cidade!');
		}

		return redirect('/cidadeDelivery'); 
	}

	public function delete($id){

		$delete = CidadeDelivery
		::where('id', $id)
		->delete();
		if($delete){
			session()->flash('mensagem_sucesso', 'Registro removido!');
		}else{
			session()->flash('mensagem_erro', 'Erro!');
		}
		return redirect('/cidadeDelivery');

	}


	private function _validate(Request $request){
		$rules = [
			'nome' => 'required|max:50',
			'cep' => 'required|max:9',
			'uf' => 'required',
		];

		$messages = [
			'nome.required' => 'O campo nome é obrigatório.',
			'nome.max' => '50 caracteres maximos permitidos.',
			'cep.required' => 'O campo cep é obrigatório.',
			'cep.max' => '9 caracteres maximos permitidos.',
			'uf.required' => 'O campo uf é obrigatório.',
		];
		$this->validate($request, $rules, $messages);
	}
}
