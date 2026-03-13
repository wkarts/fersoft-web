<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Certificado;
use App\Services\Nfse\NFSeService;
use NFePHP\Common\Certificate;
use App\Models\ConfigNota;
use App\Models\Cliente;
use App\Models\Servico;
use App\Models\NfseServico;
use App\Models\OrdemServico;
use App\Models\Nfse as NotaServico;

use Webmaniabr\Nfse\Api\Connection;
use Webmaniabr\Nfse\Api\Exceptions\APIException;
use Webmaniabr\Nfse\Models\NFSe;
use Webmaniabr\Nfse\Interfaces\APIResponse;
use Illuminate\Support\Facades\DB;
use Mail;

class NfseController extends Controller
{
	protected $empresa_id = null;
	public function __construct(){
		if(!is_dir(public_path('nfse_pdf'))){
			mkdir(public_path('nfse_pdf'), 0777, true);
		}
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

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		if($config == null){
			session()->flash('mensagem_erro', 'Realize a configuração do emitente!');
			return redirect()->back();
		}

		$data = NotaServico::
		where('empresa_id', $this->empresa_id)
		->orderBy('id', 'desc')
		->paginate(30);

		$total = 0;
		foreach($data as $item){
			$total += $item->valor_total;
		}

		$certificado = Certificado::
		where('empresa_id', $this->empresa_id)
		->first();

		$clientes = Cliente::
		where('empresa_id', $this->empresa_id)
		->get();


		$estado = 'TODOS';
		return view('nfse.index', compact('data', 'certificado', 'config', 'estado', 'total'))
		->with('links', true)
		->with('clientes', $clientes)
		->with('title', 'NFSe');
	}

	public function filtro(Request $request){
		$dataInicial = $request->data_inicial;
		$dataFinal = $request->data_final;
		$estado = $request->estado;
		$cliente_id = $request->cliente_id;

		$data = NotaServico::
		where('nfses.empresa_id', $this->empresa_id)
		->select('nfses.*');

		if(($dataInicial) && ($dataFinal)){
			$data->whereBetween('created_at', [
				$this->parseDate($dataInicial), 
				$this->parseDate($dataFinal, true)
			]);
		}

		if($estado){
			$data->where('estado', $estado);
		}

		if($cliente_id != 'null'){
			$data->where('cliente_id', $cliente_id);
		}

		$data = $data->get();

		$total = 0;
		foreach($data as $item){
			$total += $item->valor_total;
		}

		$certificado = Certificado::
		where('empresa_id', $this->empresa_id)
		->first();

		$clientes = Cliente::
		where('empresa_id', $this->empresa_id)
		->get();

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		return view('nfse.index', compact('data', 'certificado', 'config'))
		->with('dataInicial', $dataInicial)
		->with('dataFinal', $dataFinal)
		->with('estado', $estado)
		->with('total', $total)
		->with('cliente_id', $cliente_id)
		->with('clientes', $clientes)
		->with('tipoPesquisa', $request->tipo_pesquisa)
		->with('title', 'NFSe');
	}

	private function parseDate($date, $plusDay = false){
		if($plusDay == false)
			return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
		else
			return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
	}

	public function create(){
		$clientes = Cliente::
		where('empresa_id', $this->empresa_id)
		->orderBy('razao_social', 'asc')
		->where('inativo', false)
		->get();

		$servicos = Servico::
		where('empresa_id', $this->empresa_id)
		->orderBy('nome', 'desc')
		->get();

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		return view('nfse.create', compact('clientes', 'config', 'servicos'))
		->with('title', 'Nova NFSe');
	}

	public function clone($id){
		$item = NotaServico::findOrFail($id);
		$clientes = Cliente::
		where('empresa_id', $this->empresa_id)
		->orderBy('razao_social', 'desc')
		->where('inativo', false)
		->get();

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$servicos = Servico::
		where('empresa_id', $this->empresa_id)
		->orderBy('nome', 'desc')
		->get();

		return view('nfse.create', compact('clientes', 'config', 'item', 'servicos'))
		->with('clone', 1)
		->with('title', 'Clonar NFSe');
	}

	public function edit($id){
		$item = NotaServico::findOrFail($id);
		$clientes = Cliente::
		where('empresa_id', $this->empresa_id)
		->orderBy('razao_social', 'desc')
		->where('inativo', false)
		->get();

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$servicos = Servico::
		where('empresa_id', $this->empresa_id)
		->orderBy('nome', 'desc')
		->get();

		return view('nfse.create', compact('clientes', 'config', 'item', 'servicos'))
		->with('title', 'Editar NFSe');
	}

	public function delete($id){
		$item = NotaServico::findOrFail($id);
		try{
			if(valida_objeto($item)){
				$item->servico()->delete();
				$item->delete();
				session()->flash('mensagem_sucesso', 'Nfse removida!');
			}
		}catch(\Exception $e){
			__saveError($e, $this->empresa_id);
			session()->flash('mensagem_erro', 'Algo deu errado!');
		}
		return redirect()->back();
	}

	public function update(Request $request, $id){
		$this->_validate($request);
		$item = NotaServico::findOrFail($id);
		try{
			$result = DB::transaction(function () use ($request, $item) {

				$totalServico = (float)__replace($request->valor_servico);
				$request->merge([
					'valor_total' => $totalServico,
					'cliente_id' => $request->cliente
				]);

				$item->fill($request->all())->update();

				$item->servico->delete();
				NfseServico::create([
					'nfse_id' => $item->id,
					'discriminacao' => $request->discriminacao,
					'valor_servico' => __replace($request->valor_servico),
					'servico_id' => $request->servico_id,
					'codigo_cnae' => $request->codigo_cnae ?? '',
					'codigo_servico' => $request->codigo_servico ?? '',
					'codigo_tributacao_municipio' => $request->codigo_tributacao_municipio ?? '',
					'exigibilidade_iss' => $request->exigibilidade_iss,
					'iss_retido' => $request->iss_retido,
					'data_competencia' => $request->data_competencia ?? null,
					'estado_local_prestacao_servico' => $request->estado_local_prestacao_servico ?? '',
					'cidade_local_prestacao_servico' => $request->cidade_local_prestacao_servico ?? '',
					'valor_deducoes' => $request->valor_deducoes ? __replace($request->valor_deducoes) : 0,
					'desconto_incondicional' => $request->desconto_incondicional ? __replace($request->desconto_incondicional) : 0,
					'desconto_condicional' => $request->desconto_condicional ? __replace($request->desconto_condicional) : 0,
					'outras_retencoes' => $request->outras_retencoes ? __replace($request->outras_retencoes) : 0,
					'aliquota_iss' => $request->aliquota_iss ? __replace($request->aliquota_iss) : 0,
					'aliquota_pis' => $request->aliquota_pis ? __replace($request->aliquota_pis) : 0,
					'aliquota_cofins' => $request->aliquota_cofins ? __replace($request->aliquota_cofins) : 0,
					'aliquota_inss' => $request->aliquota_inss ? __replace($request->aliquota_inss) : 0,
					'aliquota_ir' => $request->aliquota_ir ? __replace($request->aliquota_ir) : 0,
					'aliquota_csll' => $request->aliquota_csll ? __replace($request->aliquota_csll) : 0,
					'intermediador' => $request->intermediador ?? 'n',
					'documento_intermediador' => $request->documento_intermediador ?? '',
					'nome_intermediador' => $request->nome_intermediador ?? '',
					'im_intermediador' => $request->im_intermediador ?? '',
					'responsavel_retencao_iss' => $request->responsavel_retencao_iss ?? 1,

				]);
			});
			session()->flash('mensagem_sucesso', 'Nfse atualizada!');

		}catch(\Exception $e){
			__saveError($e, $this->empresa_id);
			// echo $e->getLine();
			// die;
			session()->flash('mensagem_erro', 'Algo deu errado!');
		}
		return redirect('/nfse');
	}

	public function store(Request $request){
		$this->_validate($request);
		try{
			$result = DB::transaction(function () use ($request) {

				$totalServico = (float)__replace($request->valor_servico);
				$nfse = NotaServico::create([
					'empresa_id' => $this->empresa_id,
					'filial_id' => NULL,
					'valor_total' => $totalServico,
					'estado' => 'novo',
					'serie' => '',
					'codigo_verificacao' => '',
					'numero_nfse' => 0,
					'url_xml' => '',
					'url_pdf_nfse' => '',
					'url_pdf_rps' => '',
					'cliente_id' => $request->cliente,
					'natureza_operacao' => $request->natureza_operacao,
					'documento' => $request->documento,
					'razao_social' => $request->razao_social,
					'im' => $request->im ?? '',
					'ie' => $request->ie ?? '',
					'cep' => $request->cep ?? '',
					'rua' => $request->rua,
					'numero' => $request->numero,
					'bairro' => $request->bairro,
					'complemento' => $request->complemento ?? '',
					'cidade_id' => $request->cidade_id,
					'email' => $request->email ?? '',
					'telefone' => $request->telefone ?? ''
				]);

				NfseServico::create([
					'nfse_id' => $nfse->id,
					'discriminacao' => $request->discriminacao,
					'valor_servico' => __replace($request->valor_servico),
					'servico_id' => $request->servico_id,
					'codigo_cnae' => $request->codigo_cnae ?? '',
					'codigo_servico' => $request->codigo_servico ?? '',
					'codigo_tributacao_municipio' => $request->codigo_tributacao_municipio ?? '',
					'exigibilidade_iss' => $request->exigibilidade_iss,
					'iss_retido' => $request->iss_retido,
					'data_competencia' => $request->data_competencia ?? null,
					'estado_local_prestacao_servico' => $request->estado_local_prestacao_servico ?? '',
					'cidade_local_prestacao_servico' => $request->cidade_local_prestacao_servico ?? '',
					'valor_deducoes' => $request->valor_deducoes ? __replace($request->valor_deducoes) : 0,
					'desconto_incondicional' => $request->desconto_incondicional ? __replace($request->desconto_incondicional) : 0,
					'desconto_condicional' => $request->desconto_condicional ? __replace($request->desconto_condicional) : 0,
					'outras_retencoes' => $request->outras_retencoes ? __replace($request->outras_retencoes) : 0,
					'aliquota_iss' => $request->aliquota_iss ? __replace($request->aliquota_iss) : 0,
					'aliquota_pis' => $request->aliquota_pis ? __replace($request->aliquota_pis) : 0,
					'aliquota_cofins' => $request->aliquota_cofins ? __replace($request->aliquota_cofins) : 0,
					'aliquota_inss' => $request->aliquota_inss ? __replace($request->aliquota_inss) : 0,
					'aliquota_ir' => $request->aliquota_ir ? __replace($request->aliquota_ir) : 0,
					'aliquota_csll' => $request->aliquota_csll ? __replace($request->aliquota_csll) : 0,
					'intermediador' => $request->intermediador ?? 'n',
					'documento_intermediador' => $request->documento_intermediador ?? '',
					'nome_intermediador' => $request->nome_intermediador ?? '',
					'im_intermediador' => $request->im_intermediador ?? '',
					'responsavel_retencao_iss' => $request->responsavel_retencao_iss ?? 1,

				]);

				if(isset($request->os_id)){
					$ordem = OrdemServico::findOrFail($request->os_id);
					$ordem->nfse_id = $nfse->id;
					$ordem->save();
				}
			});
			session()->flash('mensagem_sucesso', 'Nfse criada');

		}catch(\Exception $e){
			__saveError($e, $this->empresa_id);
			// echo $e->getMessage();
			// die;
			session()->flash('mensagem_erro', 'Algo deu errado!');
		}
		return redirect('/nfse');
	}

	public function storeAjax(Request $request){

		try{
			$result = DB::transaction(function () use ($request) {
				$request = (object)$request->data;
				$totalServico = (float)__replace($request->valor_servico);
				$nfse = NotaServico::create([
					'empresa_id' => $this->empresa_id,
					'filial_id' => NULL,
					'valor_total' => $totalServico,
					'estado' => 'novo',
					'serie' => '',
					'codigo_verificacao' => '',
					'numero_nfse' => 0,
					'url_xml' => '',
					'url_pdf_nfse' => '',
					'url_pdf_rps' => '',
					'cliente_id' => $request->cliente,
					'natureza_operacao' => $request->natureza_operacao,
					'documento' => $request->documento,
					'razao_social' => $request->razao_social,
					'im' => $request->im ?? '',
					'ie' => $request->ie ?? '',
					'cep' => $request->cep ?? '',
					'rua' => $request->rua,
					'numero' => $request->numero,
					'bairro' => $request->bairro,
					'complemento' => $request->complemento ?? '',
					'cidade_id' => $request->cidade_id,
					'email' => $request->email ?? '',
					'telefone' => $request->telefone ?? ''
				]);

				NfseServico::create([
					'nfse_id' => $nfse->id,
					'discriminacao' => $request->discriminacao,
					'valor_servico' => __replace($request->valor_servico),
					'servico_id' => $request->servico_id,
					'codigo_cnae' => $request->codigo_cnae ?? '',
					'codigo_servico' => $request->codigo_servico ?? '',
					'codigo_tributacao_municipio' => $request->codigo_tributacao_municipio ?? '',
					'exigibilidade_iss' => $request->exigibilidade_iss,
					'iss_retido' => $request->iss_retido,
					'data_competencia' => $request->data_competencia ?? null,
					'estado_local_prestacao_servico' => $request->estado_local_prestacao_servico ?? '',
					'cidade_local_prestacao_servico' => $request->cidade_local_prestacao_servico ?? '',
					'valor_deducoes' => $request->valor_deducoes ? __replace($request->valor_deducoes) : 0,
					'desconto_incondicional' => $request->desconto_incondicional ? __replace($request->desconto_incondicional) : 0,
					'desconto_condicional' => $request->desconto_condicional ? __replace($request->desconto_condicional) : 0,
					'outras_retencoes' => $request->outras_retencoes ? __replace($request->outras_retencoes) : 0,
					'aliquota_iss' => $request->aliquota_iss ? __replace($request->aliquota_iss) : 0,
					'aliquota_pis' => $request->aliquota_pis ? __replace($request->aliquota_pis) : 0,
					'aliquota_cofins' => $request->aliquota_cofins ? __replace($request->aliquota_cofins) : 0,
					'aliquota_inss' => $request->aliquota_inss ? __replace($request->aliquota_inss) : 0,
					'aliquota_ir' => $request->aliquota_ir ? __replace($request->aliquota_ir) : 0,
					'aliquota_csll' => $request->aliquota_csll ? __replace($request->aliquota_csll) : 0,
					'intermediador' => $request->intermediador ?? 'n',
					'documento_intermediador' => $request->documento_intermediador ?? '',
					'nome_intermediador' => $request->nome_intermediador ?? '',
					'im_intermediador' => $request->im_intermediador ?? '',
					'responsavel_retencao_iss' => $request->responsavel_retencao_iss ?? 1,

				]);

				if(isset($request->os_id)){
					$ordem = OrdemServico::findOrFail($request->os_id);
					$ordem->nfse_id = $nfse->id;
					$ordem->save();
				}
				return $nfse;
			});
			return response()->json($result, 200);

		}catch(\Exception $e){
			__saveError($e, $this->empresa_id);
			// echo $e->getMessage();
			// die;
			return response()->json($e->getMessage(), 403);

		}
	}

	private function _validate(Request $request){
		$rules = [
			'cliente' => 'required',
			'natureza_operacao' => 'required',
			'razao_social' => 'required|max:80',
			'documento' => ['required'],
			'rua' => 'required|max:80',
			'numero' => 'required|max:10',
			'bairro' => 'required|max:50',
			'telefone' => 'max:20',
			'celular' => 'max:20',
			'email' => 'max:40',
			'cep' => 'required',
			'cidade_id' => 'required',
			'discriminacao' => 'required',
			'valor_servico' => 'required',
			'codigo_servico' => 'required',
		];

		$messages = [
			'cliente.required' => 'Selecione',
			'razao_social.required' => 'O campo Razão social é obrigatório.',
			'natureza_operacao.required' => 'O campo Natureza de Operação é obrigatório.',
			'razao_social.max' => '100 caracteres maximos permitidos.',
			'nome_fantasia.required' => 'O campo Nome Fantasia é obrigatório.',
			'nome_fantasia.max' => '80 caracteres maximos permitidos.',
			'documento.required' => 'O campo CPF/CNPJ é obrigatório.',
			'rua.required' => 'O campo Rua é obrigatório.',
			'ie_rg.max' => '20 caracteres maximos permitidos.',
			'rua.max' => '80 caracteres maximos permitidos.',
			'numero.required' => 'O campo Numero é obrigatório.',
			'cep.required' => 'O campo CEP é obrigatório.',
			'cidade_id.required' => 'O campo Cidade é obrigatório.',
			'numero.max' => '10 caracteres maximos permitidos.',
			'bairro.required' => 'O campo Bairro é obrigatório.',
			'bairro.max' => '50 caracteres maximos permitidos.',
			'telefone.required' => 'O campo Celular é obrigatório.',
			'telefone.max' => '20 caracteres maximos permitidos.',
			'celular.required' => 'O campo Celular 2 é obrigatório.',
			'celular.max' => '20 caracteres maximos permitidos.',

			'email.required' => 'O campo Email é obrigatório.',
			'email.max' => '40 caracteres maximos permitidos.',
			'email.email' => 'Email inválido.',
			'discriminacao.required' => 'Campo obrigatório.',
			'valor_servico.required' => 'Campo obrigatório.',
			'codigo_servico.required' => 'Campo obrigatório.',


		];
		$this->validate($request, $rules, $messages);
	}

	public function teste(){
		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		Connection::getInstance()->setBearerToken($config->token_nfse);

		$nfse = new NFSe();
		$nfse->Servico->valorServico = 243;
		$nfse->Servico->discriminacao = "Instlacao eletrica";
		$nfse->Servico->codigoServico = "0702";
		$nfse->Servico->naturezaOperacao = "1";
		$nfse->Servico->issRetido = 0;
		$nfse->Servico->exigibilidadeIss = 1;
		// $nfse->Servico->tipoTributacao = 1;
		$nfse->Servico->Impostos->iss = 2;
		// $nfse->Tomador->razaoSocial = "Marcos Bueno";
		$nfse->Tomador->nomeCompleto = "Marcos Bueno";
		$nfse->Tomador->cpf = "09520985980";
		$nfse->Tomador->cep = "84200000";
		$nfse->Tomador->endereco = "Aldo Ribas";
		$nfse->Tomador->numero = "190";
		$nfse->Tomador->complemento = "Casa";
		$nfse->Tomador->bairro = "Cidade Alta";
		$nfse->Tomador->cidade = "Jaguariaiva";
		$nfse->Tomador->uf = "PR";
		
		try {
			$response = $nfse->emitirHomologacao();

			// dd($response);
			// die;
			$object = json_decode($response->getMessage());
			if(isset($object->status)){
				if($object->status == 'reprovado'){
					echo "erro";
				}
				dd($object);
			}else{
				dd($response->getMessage());			
			}
		} catch (\Throwable $th) {
			die;
			dd((object) [ 'exception' => $th->getMessage() ]);
		} catch (APIException $a) {
			die;
			dd((object) [ 'error' => $a->getMessage() ]);
		}
	}

	public function enviar(Request $request){

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		Connection::getInstance()->setBearerToken($config->token_nfse);

		if(!is_dir(public_path('nfse_doc'))){
			mkdir(public_path('nfse_doc'), 0777, true);
		}
		$item = NotaServico::findOrFail($request->id);
		$nfse = new NFSe();

		$servico = $item->servico;
		$nfse->Servico->valorServico = $servico->valor_servico;
		$nfse->Servico->discriminacao = $this->retiraAcentos($servico->discriminacao);
		$nfse->Servico->codigoServico = $servico->codigo_servico;
		$nfse->Servico->naturezaOperacao = $item->natureza_operacao;

		if($servico->iss_retido == 1){

			$nfse->Servico->issRetido = $servico->iss_retido;

			if($servico->iss_retido == 1){
				// $nfse->Servico->ResponsavelRetencao = $servico->responsavel_retencao_iss;
				$nfse->Servico->Intermediario->nomeCompleto = $config->razao_social;
				$nfse->Servico->Intermediario->nomeCompleto = $config->razao_social;
				$doc = preg_replace('/[^0-9]/', '', $config->cnpj);
				if(strlen($doc) == 11){
					$nfse->Servico->Intermediario->cpf = $doc;					
				}else{
					$nfse->Servico->Intermediario->cnpj = $doc;
				}
			}
		}
		if($servico->codigo_tributacao_municipio){
			$nfse->Servico->codigoTributacaoMunicipio = $servico->codigo_tributacao_municipio;
		}if($servico->codigo_cnae){
			$nfse->Servico->codigoCnae = $servico->codigo_cnae;
		}
		$nfse->Servico->exigibilidadeIss = $servico->exigibilidade_iss;
		if($servico->aliquota_iss){
			$nfse->Servico->Impostos->iss = $servico->aliquota_iss;
		}

			// $nfse->Servico->tipoTributacao = 1;

		$doc = preg_replace('/[^0-9]/', '', $item->documento);
		$nfse->Tomador->nomeCompleto = $item->razao_social;

		if(strlen($doc) == 11){
			$nfse->Tomador->cpf = $doc;
		}else{
			$nfse->Tomador->razaoSocial = $this->retiraAcentos($item->razao_social);
			$nfse->Tomador->cnpj = $doc;
		}

		if($item->ie != ''){
			$nfse->Tomador->ie = preg_replace('/[^0-9]/', '', $item->ie);
		}

		if($item->im != ''){
			$nfse->Tomador->im = preg_replace('/[^0-9]/', '', $item->im);
		}

		$nfse->Tomador->cep = preg_replace('/[^0-9]/', '', $item->cep);
		$nfse->Tomador->endereco = $this->retiraAcentos($item->rua);
		$nfse->Tomador->numero = $item->numero;
		if($item->complemento){
			$nfse->Tomador->complemento = $this->retiraAcentos($item->complemento);
		}
		$nfse->Tomador->bairro = $this->retiraAcentos($item->bairro);
		$nfse->Tomador->cidade = $this->retiraAcentos($item->cidade->nome);
		$nfse->Tomador->uf = $item->cidade->uf;

		try {
			// $config->ambiente = 1;
			if($config->ambiente == 2){
				$response = $nfse->emitirHomologacao();
			}else{
				$response = $nfse->emitir();
			}

			// dd($response);
			// die;
			$object = json_decode($response->getMessage());
			if(isset($object->status)){

				if($object->status == 'reprovado'){
					$item->estado = 'rejeitado';
					$item->save();
					return response()->json($object, 401);
				}elseif($object->status == 'processado'){
					$object = $object->info_nfse[0];

					$item->codigo_verificacao = $object->codigo_verificacao;
					$item->url_pdf_nfse = $object->pdf_nfse;
					$item->url_pdf_rps = $object->pdf_rps;
					$item->url_xml = $object->xml;
					$item->numero_nfse = $object->numero;
					$item->uuid = $object->uuid;
					$item->estado = 'aprovado';

					$item->save();

					$xml = safe_file_get_contents($item->url_xml);
					safe_file_put_contents(public_path('nfse_doc/')."$item->uuid.xml", $xml);
					return response()->json($object, 200);
				}elseif($object->status == 'processando'){
					
					$item->estado = 'processando';
					$item->uuid = $object->uuid;
					$item->save();
					return response()->json($object, 401);
				}else{	
					// return response()->json($object, 401);

					$item->codigo_verificacao = $object->codigo_verificacao;
					$item->url_pdf_nfse = $object->pdf_nfse;
					$item->url_pdf_rps = $object->pdf_rps;
					$item->url_xml = $object->xml;
					$item->numero_nfse = $object->numero;
					$item->uuid = $object->uuid;
					$item->estado = 'aprovado';
					
					$item->save();

					$xml = safe_file_get_contents($item->url_xml);
					safe_file_put_contents(public_path('nfse_doc/')."$item->uuid.xml", $xml);
					return response()->json($object, 200);
				}
				// dd($object);
			}else{
				$stringResp = substr($response->getMessage(), 0, 44);
				if($stringResp == 'Nota Fiscal já se encontra em processamento'){
					$item->estado = 'processando';
					$item->save();
				}
				return response()->json($response->getMessage(), 403);		
			}
		} catch (\Throwable $th) {
			// dd((object) [ 'exception' => $th->getMessage() ]);
			return response()->json($th->getMessage() . ", linha: " . $th->getLine(), 407);

		} catch (APIException $a) {
			// dd((object) [ 'error' => $a->getMessage() ]);

			return response()->json($a->getMessage(), 404);
		}

	}

	private function retiraAcentos($texto){
		return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/", "/(ç)/", "/(&)/"),explode(" ","a A e E i I o O u U n N c e"),$texto);
	}

	public function baixarXml($id){
		$item = NotaServico::findOrFail($id);
		if(valida_objeto($item)){
			if(file_exists(public_path('nfse_doc/')."$item->uuid.xml")){

				return response()->download(public_path('nfse_doc/')."$item->uuid.xml");
			}elseif(file_exists(public_path('nfse_doc/')."$item->chave.xml")){

				return response()->download(public_path('nfse_doc/')."$item->chave.xml");
			}else{
				echo "Arquivo XML não encontrado!!";
			}
		}else{
			return redirect('/403');
		}
	}

	public function imprimir($id){
		$item = NotaServico::findOrFail($id);
		if(valida_objeto($item)){
			if($item->url_pdf_nfse){
				return redirect($item->url_pdf_nfse);
			}else{
				if(file_exists(public_path('nfse_pdf/').$item->chave.".pdf")){
					$pdf = safe_file_get_contents(public_path('nfse_pdf/').$item->chave.".pdf");
					return response($pdf)
					->header('Content-Type', 'application/pdf');
				}
			}
		}else{
			return redirect('/403');
		}
	}

	public function consultar(Request $request){
		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		Connection::getInstance()->setBearerToken($config->token_nfse);
		$item = NotaServico::findOrFail($request->id);
		$nfse = new NFSe();

		$nfse->uuid = $item->uuid;
		try {
			$response = $nfse->consultar();
			$object = json_decode($response->getMessage());

			if(isset($object->info_nfse)){
				$object = $object->info_nfse[0];
			}

			if(isset($object->codigo_verificacao)){
				$item->codigo_verificacao = $object->codigo_verificacao;
				$item->url_pdf_nfse = $object->pdf_nfse;
				$item->url_pdf_rps = $object->pdf_rps;
				$item->url_xml = $object->xml;
				$item->numero_nfse = $object->numero;
				$item->uuid = $object->uuid;
				$item->estado = 'aprovado';
				$item->save();
				$xml = safe_file_get_contents($item->url_xml);
				safe_file_put_contents(public_path('nfse_doc/')."$item->uuid.xml", $xml);
			}

			if($object->status == "reprovado"){
				$item->estado = 'rejeitado';
				$item->save();
				
				return response()->json($object->motivo[0], 401);
			}

			if($object->status == "cancelado"){
				$item->estado = 'cancelado';
				$item->save();
			}

			return response()->json($response->getMessage(), 200);

		} catch (\Throwable $th) {
			// response((object) [ 'exception' => $th->getMessage() ]);
			return response()->json($th->getMessage(), 401);
		} catch (APIException $a) {
			// response((object) [ 'error' => $a->getMessage() ]);
			return response()->json($a->getMessage(), 401);
		}

	}

	public function cancelar(Request $request){
		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		Connection::getInstance()->setBearerToken($config->token_nfse);
		$item = NotaServico::findOrFail($request->id);
		$nfse = new NFSe();

		$nfse->uuid = $item->uuid;

		try {
			$response = $nfse->cancelar($request->motivo);
			$message = $response->getMessage();
			if(isset($message->status)){
				if($message->status == 'cancelado'){
					$item->estado = 'cancelado';
					$item->save();
				}
			}
			// response($response->getMessage());
			return response()->json($response->getMessage(), 200);
		} catch (\Throwable $th) {
			// response((object) [ 'exception' => $th->getMessage() ]);
			return response()->json($th->getMessage(), 401);
		} catch (APIException $a) {
			// response((object) [ 'error' => $a->getMessage() ]);
			return response()->json($a->getMessage(), 401);
		}

	}

	public function enviarXml(Request $request){
		$email = $request->email;
		$id = $request->id;
		$item = NotaServico::findOrFail($id);
		if(valida_objeto($item)){
			$value = session('user_logged');
			Mail::send('mail.xml_send_nfse', ['nfse' => $item, 'usuario' => $value['nome']], function($m) use ($item, $email){
				$public = env('SERVIDOR_WEB') ? 'public/' : '';
				$nomeEmpresa = env('SMS_NOME_EMPRESA');
				$nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
				$nomeEmpresa = str_replace("_", " ",  $nomeEmpresa);
				$emailEnvio = env('MAIL_USERNAME');

				$m->from($emailEnvio, $nomeEmpresa);
				$m->subject('Envio de XML NFse ' . $item->nuero_emissao);
				$m->attach($public.'nfse_doc/'.$item->uuid . '.xml');
				$m->to($email);
			});
			return "ok";
		}else{
			return redirect('/403');
		}
	}

}
