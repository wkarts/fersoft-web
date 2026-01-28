<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EscritorioContabil;
use App\Models\Empresa;
use App\Models\Cidade;
use App\Models\Contador;

class EscritorioController extends Controller
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

	function sanitizeString($str){
		return preg_replace('{\W}', ' ', preg_replace('{ +}', ' ', strtr(
			utf8_decode(html_entity_decode($str)),
			utf8_decode('ÀÁÃÂÉÊÍÓÕÔÚÜÇÑàáãâéêíóõôúüçñ'),
			'AAAAEEIOOOUUCNaaaaeeiooouucn')));
	}

	public function index(){
		$escritorio = EscritorioContabil::
		where('empresa_id', $this->empresa_id)
		->first();

		$empresa = Empresa::find($this->empresa_id);
		$cidades = Cidade::all();
		return view('escritorio/index')
		->with('escritorio', $escritorio)
		->with('cidades', $cidades)
		->with('apiSieg', $empresa->planoEmpresa ? $empresa->planoEmpresa->plano->api_sieg : 0)
		->with('title', 'Configurar Contador');
	}


	public function save(Request $request){
		$this->_validate($request);
		if($request->id == 0){
			$result = EscritorioContabil::create([
				'razao_social' => $this->sanitizeString($request->razao_social),
				'nome_fantasia' => $this->sanitizeString($request->nome_fantasia),
				'cnpj' => $request->cnpj,
				'ie' => $request->ie,
				'fone' => $request->fone,
				'logradouro' => $this->sanitizeString($request->logradouro),
				'numero' => $this->sanitizeString($request->numero),
				'bairro' => $this->sanitizeString($request->bairro),
				'cep' => $request->cep,
				'email' => $request->email,
				'cidade_id' => $request->cidade_id,
				'envio_automatico_xml_contador' => $request->envio_automatico_xml_contador ? true : false,
				'token_sieg' => $request->token_sieg ?? '',
				'crc' => $request->crc ?? '',
				'cpf' => $request->cpf ?? '',
				'empresa_id' => $request->empresa_id
			]);

			$this->cadastraContadorSuper($result);
		}else{
			$config = EscritorioContabil::
			where('empresa_id', $this->empresa_id)
			->first();

			$config->razao_social = $this->sanitizeString($request->razao_social);
			$config->nome_fantasia = $this->sanitizeString($request->nome_fantasia);
			$config->cnpj = $request->cnpj;
			$config->ie = $request->ie;
			$config->fone = $request->fone;
			$config->logradouro = $this->sanitizeString($request->logradouro);
			$config->numero = $this->sanitizeString($request->numero);
			$config->bairro = $this->sanitizeString($request->bairro);
			$config->cep = $request->cep;
			$config->token_sieg = $request->token_sieg ?? '';
			$config->crc = $request->crc ?? '';
			$config->cpf = $request->cpf ?? '';
			$config->email = $request->email;
			$config->cidade_id = $request->cidade_id;

			$config->envio_automatico_xml_contador = $request->envio_automatico_xml_contador ? true : false;
			$result = $config->save();

			$config = EscritorioContabil::
			where('empresa_id', $this->empresa_id)
			->first();
			
			$this->cadastraContadorSuper($config);
		}

		if($result){
			session()->flash("mensagem_sucesso", "Configurado com sucesso!");
		}else{
			session()->flash('mensagem_erro', 'Erro ao configurar!');
		}

		return redirect('/escritorio');
	}

	private function cadastraContadorSuper($escritorio){

		$contador = Contador::
		where('cnpj', $escritorio->cnpj)
		->orWhere('razao_social', $escritorio->razao_social)
		->first();

		if($contador == null){
			Contador::create([
				'razao_social' => $escritorio->razao_social,
				'nome_fantasia' => $escritorio->nome_fantasia,
				'cnpj' => $escritorio->cnpj,
				'ie' => $escritorio->ie,
				'fone' => $escritorio->fone,
				'cidade_id' => $escritorio->cidade_id,
				'logradouro' => $escritorio->logradouro,
				'numero' => $escritorio->numero,
				'bairro' => $escritorio->bairro,
				'cep' => $escritorio->cep,
				'email' => $escritorio->email,
				'cadastrado_por_cliente' => 1
			]);
		}

	}

	private function _validate(Request $request){
		$rules = [
			'razao_social' => 'required|max:100',
			'nome_fantasia' => 'required|max:80',
			'cnpj' => 'required',
			'ie' => 'required',
			'logradouro' => 'required|max:80',
			'numero' => 'required|max:10',
			'bairro' => 'required|max:50',
			'fone' => 'required|max:20',
			'cep' => 'required',
			'email' => 'required|email|max:80'
			
		];

		$messages = [
			'razao_social.required' => 'O Razão social nome é obrigatório.',
			'razao_social.max' => '100 caracteres maximos permitidos.',
			'nome_fantasia.required' => 'O campo Nome Fantasia é obrigatório.',
			'nome_fantasia.max' => '80 caracteres maximos permitidos.',
			'cnpj.required' => 'O campo CNPJ é obrigatório.',
			'logradouro.required' => 'O campo Logradouro é obrigatório.',
			'ie.required' => 'O campo Inscrição Estadual é obrigatório.',
			'logradouro.max' => '80 caracteres maximos permitidos.',
			'numero.required' => 'O campo Numero é obrigatório.',
			'cep.required' => 'O campo CEP é obrigatório.',
			'municipio.required' => 'O campo Municipio é obrigatório.',
			'numero.max' => '10 caracteres maximos permitidos.',
			'bairro.required' => 'O campo Bairro é obrigatório.',
			'bairro.max' => '50 caracteres maximos permitidos.',
			'fone.required' => 'O campo Telefone é obrigatório.',
			'fone.max' => '20 caracteres maximos permitidos.',
			'email.required' => 'O campo email é obrigatório.',
			'email.email' => 'Informe um email valido.',
			'email.max' => '80 caracteres maximos permitidos.'

		];
		$this->validate($request, $rules, $messages);
	}
}
