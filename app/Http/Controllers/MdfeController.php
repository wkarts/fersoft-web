<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Mdfe;
use App\Models\MunicipioCarregamento;
use App\Models\Percurso;
use App\Models\Ciot;
use App\Models\Cidade;
use App\Models\ValePedagio;
use App\Models\Motorista;
use App\Models\InfoDescarga;
use App\Models\NFeDescarga;
use App\Models\CTeDescarga;
use App\Models\UnidadeCarga;
use App\Models\LacreTransporte;
use App\Models\LacreUnidadeCarga;
use App\Models\Veiculo;
use App\Models\Empresa;
use App\Models\Venda;
use App\Models\ConfigNota;
use App\Models\MdfePagamento;
use App\Models\MdfePagamentoComponente;
use App\Models\MdfePagamentoParcela;
use Illuminate\Support\Facades\DB;
use App\Services\MDFeService;

class MdfeController extends Controller
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

	public function teste(){
		$nProt = '41240708543628000145580020000005581443496362';

		$mdfe = Mdfe::findOrFail(2);

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$isFilial = $mdfe->filial_id;
		if($mdfe->filial_id != null){
			$config = Filial::findOrFail($mdfe->filial_id);
			if($config->arquivo_certificado == null){
				echo "Necessário o certificado para realizar esta ação!";
				die;
			}
		}

		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

		$mdfe_service = new MDFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => (int)$config->ambiente,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->UF,
			"cnpj" => $cnpj,
			"inscricaomunicipal" => $config->inscricao_municipal,
			"codigomunicipio" => $config->codMun,
			"schemes" => "PL_MDFe_300a",
			"is_filial" => $isFilial,
			"versao" => '3.00'
		]);

		$mdfe_service->teste($nProt);

	}

	public function index(){

		$permissaoAcesso = __getLocaisUsarioLogado();
		$local_padrao = __get_local_padrao();
		if($local_padrao == -1){
			$local_padrao = null;
		}
		$mdfes = Mdfe::
		where('empresa_id', $this->empresa_id)
		->where('estado', 'NOVO')
		->where(function($query) use ($permissaoAcesso){
			if($permissaoAcesso != null){
				foreach ($permissaoAcesso as $value) {
					if($value == -1){
						$value = null;
					}
					$query->orWhere('filial_id', $value);
				}
			}
		})
		->when($local_padrao != NULL, function ($query) use ($local_padrao) {

			$query->where('filial_id', $local_padrao);
		})
		->paginate(20);

		$menos30 = $this->menos30Dias();
		$date = date('d/m/Y');

		return view("mdfe/list")
		->with('mdfes', $mdfes)
		->with('mdfeEnvioJs', true)
		->with('links', true)
		->with('dataInicial', $menos30)
		->with('dataFinal', $date)
		->with('title', "Lista de MDFe");

	}

	public function filtro(Request $request){

		$dataInicial = $request->data_inicial;
		$dataFinal = $request->data_final;
		$filial_id = $request->filial_id;
		$estado = $request->estado;

		$mdfes = [];

		$permissaoAcesso = __getLocaisUsarioLogado();

		$mdfes = Mdfe::select('mdves.*')
		->when($filial_id, function ($query) use ($filial_id) {
			$filial_id = $filial_id == -1 ? null : $filial_id;
			return $query->where('filial_id', $filial_id);
		})
		->where(function($query) use ($permissaoAcesso){
			if($permissaoAcesso != null){
				foreach ($permissaoAcesso as $value) {
					if($value == -1){
						$value = null;
					}
					$query->orWhere('filial_id', $value);
				}
			}
		})
		->where('empresa_id', $this->empresa_id);

		if(isset($dataInicial) && isset($dataFinal)){
			$mdfes->whereBetween('created_at', [
				$this->parseDate($dataInicial),
				$this->parseDate($dataFinal, true)
			]);
		}

		if($estado != 'TODOS') $mdfes->where('mdves.estado', $estado);
		$mdfes = $mdfes->get();


		// if(isset($dataInicial) && isset($dataFinal)){
		// 	$mdfes = Mdfe::filtroData(
		// 		$this->parseDate($dataInicial),
		// 		$this->parseDate($dataFinal, true),
		// 		$estado
		// 	);
		// }

		return view("mdfe/list")
		->with('mdfes', $mdfes)
		->with('mdfeEnvioJs', true)
		->with('dataInicial', $dataInicial)
		->with('dataFinal', $dataFinal)
		->with('estado', $estado)
		->with('filial_id', $filial_id)
		->with('title', "Filtro de MDFe");
	}


	public function nova(){
		$lastMdfe = Mdfe::lastMdfe();

		$veiculos = Veiculo::
		where('empresa_id', $this->empresa_id)
		->get();

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$ufs = Mdfe::cUF();
		$cidades = Cidade::all();
		$tiposUnidadeTransporte = Mdfe::tiposUnidadeTransporte();

		if($config == null || sizeof($veiculos) == 0){
			return view("mdfe/erro")
			->with('veiculos', $veiculos)
			->with('config', $config)
			->with('clienteCadastrado', true)
			->with('title', "Validação para Emitir");

		}else{

			$motoristas = Motorista::
			where('empresa_id', $this->empresa_id)
			->get();

			return view("mdfe/register")
			->with('mdfeJs', true)
			->with('veiculos', $veiculos)
			->with('motoristas', $motoristas)
			->with('ufs', $ufs)
			->with('cidades', $cidades)
			->with('tiposUnidadeTransporte', $tiposUnidadeTransporte)
			->with('lastMdfe', $lastMdfe)
			->with('title', "Nova MDFe");
		}
	}

	private function menos30Dias(){
		return date('d/m/Y', strtotime("-30 days",strtotime(str_replace("/", "-",
			date('Y-m-d')))));
	}

	private function parseDate($date, $plusDay = false){
		if($plusDay == false)
			return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
		else
			return date('Y-m-d', strtotime("+1 day",strtotime(str_replace("/", "-", $date))));
	}

	private function criarLog($objeto, $tipo = 'criar'){
		if(isset(session('user_logged')['log_id'])){
			$record = [
				'tipo' => $tipo,
				'usuario_log_id' => session('user_logged')['log_id'],
				'tabela' => 'mdves',
				'registro_id' => $objeto->id,
				'empresa_id' => $this->empresa_id
			];
			__saveLog($record);
		}
	}

	public function salvar(Request $request){
		try{
			$result = DB::transaction(function () use ($request) {
				$data = $request->data;
				$infoDescarga = $data['infoDescarga'];
				$municipiosCarregamento = $data['municipios_carregamento'];
				$ciot = isset($data['ciot']) ? $data['ciot'] : null;
				$valePedagio = isset($data['vale_pedagio']) ? $data['vale_pedagio'] : null ;
				$percurso = $data['percurso'] ?? null;
				$veiculoTracao = $data['veiculo_tracao'];
				$veiculoReboque = $data['veiculo_reboque'];
				$veiculoReboque2 = $data['veiculo_reboque2'];
				$veiculoReboque3 = $data['veiculo_reboque3'];
				$ufInicio = $data['uf_inicio'];
				$ufFim = $data['uf_fim'];
				$dataInicioViagem = $data['data_inicio_viagem'];
				$cargaPosterior = $data['carga_posteior'];
				$cnpjContratante = $data['cnpj_contratante'];
				$seguradoraNome = $data['seguradora_nome'] ?? '';
				$seguradraNumeroApolice = $data['seguradora_numero_apolice'] ?? '';
				$seguradoNumeroAverbacao = $data['seguradora_numero_averbacao'] ?? '';
				$seguradoraCnpj = $data['seguradora_cnpj'] ?? '';
				$valorCarga = str_replace(",", ".", $data['valor_carga']);
				$qtdCarga = str_replace(",", ".", $data['qtd_carga']);
				$infoComplementar = $data['info_complementar'] ?? '';
				$infoFisco = $data['info_fisco'] ?? '';

				$pagamento = $data['pagamento'] ?? null;

				$condutorNome = $data['condutor_nome'];
				$condutorCpf = $data['condutor_cpf'];

				$motorista = Motorista::where('empresa_id', $this->empresa_id)
				->where('cpf', $condutorCpf)->first();
				if($motorista == null){
					Motorista::create([
						'empresa_id' => $this->empresa_id,
						'nome' => $condutorNome,
						'cpf' => $condutorCpf
					]);
				}

				$tpEmit = $data['tp_emit'];
				$tpTransp = $data['tp_transp'] ?? '';
				$lacreRodo = $data['lacre_rodo'] ?? '';
				$produto_pred_nome = $data['produto_pred_nome'] ?? '';
				$ncm = str_replace(".", "", $data['produto_pred_ncm']);
				$produto_pred_ncm = $ncm ?? '';

				$produto_pred_cod_barras = $data['produto_pred_cod_barras'] ?? '';
				$cep = str_replace("-", "", $data['cep_carrega']);
				$cep_carrega = $cep ?? '';

				$cep = str_replace("-", "", $data['cep_descarrega']);
				$cep_descarrega = $cep ?? '';
				$tp_carga = $data['tp_carga'] ?? '';

				$latitude_carrega = $data['latitude_carrega'] ?? '';
				$longitude_carrega = $data['longitude_carrega'] ?? '';
				$latitude_descarrega = $data['latitude_descarrega'] ?? '';
				$longitude_descarrega = $data['longitude_descarrega'] ?? '';

				$mdfe = Mdfe::create([
					'uf_inicio' => $ufInicio,
					'uf_fim' => $ufFim,
					'encerrado' => false,
					'data_inicio_viagem' => $this->parseDate($dataInicioViagem),
					'carga_posterior' => $cargaPosterior,
					'veiculo_tracao_id' => $veiculoTracao,
					'veiculo_reboque_id' => $veiculoReboque ?? null,

					'veiculo_reboque2_id' => $veiculoReboque2 ?? null,
					'veiculo_reboque3_id' => $veiculoReboque3 ?? null,


					'estado' => 'NOVO',
					'seguradora_nome' => $seguradoraNome,
					'seguradora_cnpj' => $seguradoraCnpj,
					'numero_apolice' => $seguradraNumeroApolice,
					'numero_averbacao' => $seguradoNumeroAverbacao,
					'valor_carga' => $valorCarga,
					'quantidade_carga' => $qtdCarga,
					'info_complementar' => $infoComplementar,
					'info_adicional_fisco' => $infoFisco,
					'cnpj_contratante' => $cnpjContratante,
					'mdfe_numero' => 0,
					'condutor_nome' => $condutorNome,
					'condutor_cpf' => $condutorCpf,
					'tp_emit' => $tpEmit,
					'tp_transp' => $tpTransp,
					'lac_rodo' => $lacreRodo,
					'encerrado' => false,
					'chave' => '',
					'protocolo' => '',
					'produto_pred_nome' => $produto_pred_nome,
					'produto_pred_ncm' => $produto_pred_ncm,
					'produto_pred_cod_barras' => $produto_pred_cod_barras,
					'cep_carrega' => $cep_carrega,
					'cep_descarrega' => $cep_descarrega,
					'tp_carga' => $tp_carga,

					'latitude_carregamento' => $latitude_carrega,
					'longitude_carregamento' => $longitude_carrega,
					'latitude_descarregamento' => $latitude_descarrega,
					'longitude_descarregamento' => $longitude_descarrega,
					'empresa_id' => $this->empresa_id,
					'filial_id' => (!isset($data['filial_id']) || $data['filial_id'] == -1) ? null : $data['filial_id']

				]);

				$this->salvarPagamentoMdfe($mdfe, $pagamento);

				foreach($municipiosCarregamento as $m){
					MunicipioCarregamento::create([
						'cidade_id' => $m['id'],
						'mdfe_id' => $mdfe->id
					]);
				}

				if($percurso != null){
					foreach($percurso as $p){
						Percurso::create([
							'uf' => $p,
							'mdfe_id' => $mdfe->id
						]);
					}
				}

				if($valePedagio != null){
					foreach($valePedagio as $v){
						ValePedagio::create([
							'mdfe_id' => $mdfe->id,
							'cnpj_fornecedor' => $v['cnpj_fornecedor'],
							'cnpj_fornecedor_pagador' => $v['doc_pagador'],
							'numero_compra' => $v['numero_compra'],
							'valor' => $v['valor']
						]);
					}
				}

				if($ciot != null){
					foreach($ciot as $c){
						Ciot::create([
							'mdfe_id' => $mdfe->id,
							'cpf_cnpj' => $c['documento'],
							'codigo' => $c['codigo']

						]);
					}
				}

				foreach($infoDescarga as $i){
					$info = InfoDescarga::create([
						'mdfe_id' => $mdfe->id,
						'tp_unid_transp' => $i['tpTransp'],
						'id_unid_transp' => $i['idUnidTransp'],
						'quantidade_rateio' => $i['qtdRateioTransp'],
						'cidade_id' => (int)explode("-", $i['municipio'])[0]
					]);

					if($i['chaveNFe'] || $i['segCodNFe']){
						NFeDescarga::Create([
							'info_id' => $info->id,
							'chave' => str_replace(" ", "", $i['chaveNFe']),
							'seg_cod_barras' => str_replace(" ", "", $i['segCodNFe'])
						]);
					}

					if($i['chaveCTe'] || $i['segCodCTe']){
						CTeDescarga::Create([
							'info_id' => $info->id,
							'chave' => str_replace(" ", "", $i['chaveCTe']),
							'seg_cod_barras' => str_replace(" ", "", $i['segCodCTe'])
						]);
					}

					if(isset($i['lacresUnidCarga'])){
						foreach($i['lacresUnidCarga'] as $l){
							LacreUnidadeCarga::create([
								'info_id' => $info->id,
								'numero' => $l
							]);
						}
					}

					if(isset($i['lacresUnidTransp'])){

						foreach($i['lacresUnidTransp'] as $l){
							LacreTransporte::create([
								'info_id' => $info->id,
								'numero' => $l
							]);
						}
					}

					UnidadeCarga::create([
						'info_id' => $info->id,
						'id_unidade_carga' => $i['idUnidCarga'],
						'quantidade_rateio' => $i['qtdRateioUnidCarga']
					]);
				}

				$this->criarLog($mdfe);
				return $mdfe;
			});
return response()->json($result, 200);

}catch(\Exception $e){
	__saveError($e, $this->empresa_id);
	return response()->json($e->getMessage(), 400);
}
}

public function edit($id){
	$mdfe = Mdfe::find($id);

	$lastMdfe = Mdfe::lastMdfe();
	$veiculos = Veiculo::
	where('empresa_id', $this->empresa_id)
	->get();

	$config = ConfigNota::
	where('empresa_id', $this->empresa_id)
	->first();

	$ufs = Mdfe::cUF();
	$tiposUnidadeTransporte = Mdfe::tiposUnidadeTransporte();

	$municipiosDeCarregamento = $this->getMunicipiosCarregamento($mdfe);
	$percurso = $this->getPercurso($mdfe);
	$ciots = $this->getCiots($mdfe);
	$valesPedagio = $this->getValesPedagio($mdfe);
	$infoDescarga = $this->getInfoDescarga($mdfe);
	$pagamento = $this->getPagamento($mdfe);
	$cidades = Cidade::all();

	return view("mdfe/register")
	->with('mdfeJs', true)
	->with('veiculos', $veiculos)
	->with('ufs', $ufs)
	->with('cidades', $cidades)
	->with('tiposUnidadeTransporte', $tiposUnidadeTransporte)
	->with('lastMdfe', $lastMdfe->mdfe_numero ?? 'Nulo')
	->with('mdfe', $mdfe)
	->with('municipiosDeCarregamento', $municipiosDeCarregamento)
	->with('percurso', $percurso)
	->with('ciots', $ciots)
	->with('valesPedagio', $valesPedagio)
	->with('infoDescarga', $infoDescarga)
	->with('pagamento', $pagamento)
	->with('title', "Editar MDF-e");

}

private function getMunicipiosCarregamento($mdfe){
	$temp = [];
	foreach($mdfe->municipiosCarregamento as $m){
		$arr = [
			'id' => $m->cidade->id,
			'nome' => $m->cidade->nome . "(" . $m->cidade->uf . ")"
		];
		array_push($temp, $arr);
	}
	return $temp;
}

private function getPercurso($mdfe){
	$temp = [];
	foreach($mdfe->percurso as $p){

		array_push($temp, $p->uf);
	}
	return $temp;
}

private function getCiots($mdfe){
	$temp = [];
	foreach($mdfe->ciots as $c){
		$arr = [
			'codigo' => $c->codigo,
			'documento' => $c->cpf_cnpj
		];
		array_push($temp, $arr);
	}
	return $temp;
}

private function getValesPedagio($mdfe){
	$temp = [];
	foreach($mdfe->valesPedagio as $v){
		$arr = [
			'cnpj_fornecedor' => $c->cnpj_fornecedor,
			'cnpj_fornecedor_pagador' => $c->cnpj_fornecedor_pagador,
			'numero_compra' => $c->numero_compra,
			'valor' => $c->valor
		];
		array_push($temp, $arr);
	}
	return $temp;
}

private function getInfoDescarga($mdfe){
	$temp = [];

	foreach($mdfe->infoDescarga as $key => $v){
		$arr = [
			'id' => $key+1,
			'tpTransp' => $v->tp_unid_transp,
			'idUnidTransp' => $v->id_unid_transp ?? '',
			'qtdRateioTransp' => $v->quantidade_rateio,
			'idUnidCarga' => $v->unidadeCarga->id_unidade_carga ?? '',
			'qtdRateioUnidCarga' => $v->unidadeCarga->quantidade_rateio ?? 0,
			'chaveNFe' => $v->nfe ? $v->nfe->chave : '',
			'segCodNFe' => $v->nfe ? $v->nfe->seg_cod_barras : '',
			'chaveCTe' => $v->cte ? $v->cte->chave : '',
			'segCodCTe' => $v->cte ? $v->cte->seg_cod_barras : '',
			'lacresUnidTransp' => $this->getLacresTransp($v),
			'lacresUnidCarga' => $this->getLacresUnidCarga($v),
			'municipio' => $v->cidade->id ." - " . $v->cidade->nome
		];
		array_push($temp, $arr);
	}
	return $temp;
}

private function getLacresTransp($info){
	$temp = [];
	foreach($info->lacresTransp as $l){

		array_push($temp, $l->numero);
	}
	return $temp;
}

private function getLacresUnidCarga($info){
	$temp = [];
	foreach($info->lacresUnidCarga as $l){
		array_push($temp, $l->numero);
	}
	return $temp;
}

private function getPagamento($mdfe){
	$pagamento = $mdfe->pagamentos()->with(['componentes', 'parcelas'])->first();
	if(!$pagamento){
		return null;
	}

	return [
		'ind_pagamento' => $mdfe->ind_pagamento,
		'valor_contrato' => $mdfe->valor_contrato_pagamento,
		'tipo_doc_pagador' => $pagamento->tipo_doc_pagador,
		'cpf_cnpj_pagador' => $pagamento->cpf_cnpj_pagador,
		'nome_pagador' => $pagamento->nome_pagador,
		'forma_pagamento' => $pagamento->forma_pagamento,
		'valor_pagamento' => $pagamento->valor_pagamento,
		'componentes' => $pagamento->componentes->map(function($item){
			return [
				'tipo_componente' => $item->tipo_componente,
				'descricao' => $item->descricao,
				'valor' => $item->valor
			];
		}),
		'parcelas' => $pagamento->parcelas->map(function($item){
			return [
				'numero_parcela' => $item->numero_parcela,
				'data_vencimento' => $item->data_vencimento,
				'valor' => $item->valor
			];
		})
	];
}

private function salvarPagamentoMdfe($mdfe, $pagamentoData){
	$mdfe->ind_pagamento = null;
	$mdfe->valor_contrato_pagamento = null;

	$existentes = MdfePagamento::where('mdfe_id', $mdfe->id)->get();
	foreach($existentes as $pag){
		$pag->delete();
	}

	if(!$pagamentoData || !isset($pagamentoData['cpf_cnpj_pagador']) || trim($pagamentoData['cpf_cnpj_pagador']) === ''){
		$mdfe->save();
		return;
	}

	$documento = preg_replace('/\D/', '', $pagamentoData['cpf_cnpj_pagador']);
	if(strlen($documento) !== 11 && strlen($documento) !== 14){
		throw new \Exception('Documento do pagador inválido. Informe CPF ou CNPJ válido.');
	}

	$indPagamento = $pagamentoData['ind_pagamento'] ?? '0';
	if(!in_array($indPagamento, ['0', '1'])){
		throw new \Exception('Indicador de pagamento inválido. Use 0 (à vista) ou 1 (a prazo).');
	}

	$valorContrato = isset($pagamentoData['valor_contrato']) ? (float)str_replace(',', '.', $pagamentoData['valor_contrato']) : 0;
	if($valorContrato <= 0){
		throw new \Exception('Valor de contrato do pagamento deve ser maior que zero.');
	}

	$mdfe->ind_pagamento = $indPagamento;
	$mdfe->valor_contrato_pagamento = $valorContrato;
	$mdfe->save();

	$pagamento = MdfePagamento::create([
		'mdfe_id' => $mdfe->id,
		'tipo_doc_pagador' => strlen($documento) === 11 ? 'CPF' : 'CNPJ',
		'cpf_cnpj_pagador' => $documento,
		'nome_pagador' => $pagamentoData['nome_pagador'] ?? null,
		'forma_pagamento' => $pagamentoData['forma_pagamento'] ?? null,
		'valor_pagamento' => isset($pagamentoData['valor_pagamento']) ? (float)str_replace(',', '.', $pagamentoData['valor_pagamento']) : $valorContrato
	]);

	$componentes = $pagamentoData['componentes'] ?? [];
	if(count($componentes) === 0){
		MdfePagamentoComponente::create([
			'mdfe_pagamento_id' => $pagamento->id,
			'tipo_componente' => '01',
			'descricao' => 'Frete',
			'valor' => $valorContrato
		]);
	}else{
		foreach($componentes as $comp){
			$valorComp = (float)str_replace(',', '.', $comp['valor'] ?? 0);
			if($valorComp < 0){
				continue;
			}
			MdfePagamentoComponente::create([
				'mdfe_pagamento_id' => $pagamento->id,
				'tipo_componente' => $comp['tipo_componente'] ?? '99',
				'descricao' => $comp['descricao'] ?? null,
				'valor' => $valorComp
			]);
		}
	}

	$parcelas = $pagamentoData['parcelas'] ?? [];
	if($indPagamento === '1' && count($parcelas) === 0){
		throw new \Exception('Pagamento a prazo exige ao menos uma parcela.');
	}

	foreach($parcelas as $parcela){
		if(empty($parcela['numero_parcela']) || empty($parcela['data_vencimento'])){
			continue;
		}

		$valorParcela = (float)str_replace(',', '.', $parcela['valor'] ?? 0);
		if($valorParcela <= 0){
			continue;
		}

		MdfePagamentoParcela::create([
			'mdfe_pagamento_id' => $pagamento->id,
			'numero_parcela' => $parcela['numero_parcela'],
			'data_vencimento' => date('Y-m-d', strtotime(str_replace('/', '-', $parcela['data_vencimento']))),
			'valor' => $valorParcela
		]);
	}
}

public function update(Request $request){
	try{
		$result = DB::transaction(function () use ($request) {
			$data = $request->data;
			$infoDescarga = $data['infoDescarga'];
			$municipiosCarregamento = $data['municipios_carregamento'];
			$ciot = isset($data['ciot']) ? $data['ciot'] : null;
			$valePedagio = isset($data['vale_pedagio']) ? $data['vale_pedagio'] : null ;
			$percursos = $data['percurso'] ?? null;
			$veiculoTracao = $data['veiculo_tracao'];
			$veiculoReboque = $data['veiculo_reboque'];
			$veiculoReboque2 = $data['veiculo_reboque2'];
			$veiculoReboque3 = $data['veiculo_reboque3'];
			$ufInicio = $data['uf_inicio'];
			$ufFim = $data['uf_fim'];
			$dataInicioViagem = $data['data_inicio_viagem'];
			$cargaPosterior = $data['carga_posteior'];
			$cnpjContratante = $data['cnpj_contratante'];
			$seguradoraNome = $data['seguradora_nome'] ?? '';
			$seguradraNumeroApolice = $data['seguradora_numero_apolice'] ?? '';
			$seguradoNumeroAverbacao = $data['seguradora_numero_averbacao'] ?? '';
			$seguradoraCnpj = $data['seguradora_cnpj'] ?? '';
			$valorCarga = str_replace(",", ".", $data['valor_carga']);
			$qtdCarga = str_replace(",", ".", $data['qtd_carga']);
			$infoComplementar = $data['info_complementar'] ?? '';
			$infoFisco = $data['info_fisco'] ?? '';

				$pagamento = $data['pagamento'] ?? null;

			$condutorNome = $data['condutor_nome'];
			$condutorCpf = $data['condutor_cpf'];
			$tpEmit = $data['tp_emit'];
			$tpTransp = $data['tp_transp'];
			$lacreRodo = $data['lacre_rodo'];

			$produto_pred_nome = $data['produto_pred_nome'];
			$ncm = str_replace(".", "", $data['produto_pred_ncm']);
			$produto_pred_ncm = $ncm;
			$produto_pred_cod_barras = $data['produto_pred_cod_barras'];

			$cep = str_replace("-", "", $data['cep_carrega']);
			$cep_carrega = $cep;

			$cep = str_replace("-", "", $data['cep_descarrega']);
			$cep_descarrega = $cep;
			$tp_carga = $data['tp_carga'];

			$latitude_carrega = $data['latitude_carrega'] ?? '';
			$longitude_carrega = $data['longitude_carrega'] ?? '';
			$latitude_descarrega = $data['latitude_descarrega'] ?? '';
			$longitude_descarrega = $data['longitude_descarrega'] ?? '';
			$filial_id = $data['filial_id'] != -1 ? $data['filial_id'] : null;

			$mdfe = Mdfe::find($data['id']);

			$mdfe->uf_inicio = $ufInicio;
			$mdfe->uf_fim = $ufFim;
			$mdfe->data_inicio_viagem = $this->parseDate($dataInicioViagem);
			$mdfe->carga_posterior = $cargaPosterior;
			$mdfe->veiculo_tracao_id = $veiculoTracao;
			$mdfe->veiculo_reboque_id = $veiculoReboque;
			$mdfe->veiculo_reboque2_id = $veiculoReboque2;
			$mdfe->veiculo_reboque3_id = $veiculoReboque3;
			$mdfe->seguradora_nome = $seguradoraNome;
			$mdfe->seguradora_cnpj = $seguradoraCnpj;
			$mdfe->filial_id = $filial_id;
			$mdfe->numero_apolice = $seguradraNumeroApolice;
			$mdfe->numero_averbacao = $seguradoNumeroAverbacao;
			$mdfe->valor_carga = $valorCarga;
			$mdfe->quantidade_carga = $qtdCarga;
			$mdfe->info_complementar = $infoComplementar;
			$mdfe->info_adicional_fisco = $infoFisco;
			$mdfe->cnpj_contratante = $cnpjContratante;
			$mdfe->mdfe_numero = 0;
			$mdfe->condutor_nome = $condutorNome;
			$mdfe->condutor_cpf = $condutorCpf;
			$mdfe->tp_emit = $tpEmit;
			$mdfe->tp_transp = $tpTransp;
			$mdfe->lac_rodo = $lacreRodo ?? '';

			$mdfe->produto_pred_nome = $produto_pred_nome ?? '';
			$mdfe->produto_pred_ncm = $produto_pred_ncm ?? '';
			$mdfe->produto_pred_cod_barras = $produto_pred_cod_barras ?? '';
			$mdfe->cep_carrega = $cep_carrega ?? '';
			$mdfe->cep_descarrega = $cep_descarrega ?? '';
			$mdfe->tp_carga = $tp_carga ?? '';
			$mdfe->latitude_carregamento = $latitude_carrega ?? '';
			$mdfe->longitude_carregamento = $longitude_carrega ?? '';
			$mdfe->latitude_descarregamento = $latitude_descarrega ?? '';
			$mdfe->longitude_descarregamento = $longitude_descarrega ?? '';

			$mdfe->save();

				$this->salvarPagamentoMdfe($mdfe, $pagamento);

			$municipiosTemp = MunicipioCarregamento::
			where('mdfe_id', $mdfe->id)
			->get();

			foreach($municipiosTemp as $temp){
				$temp->delete();
			}

			foreach($municipiosCarregamento as $m){
				MunicipioCarregamento::create([
					'cidade_id' => $m['id'],
					'mdfe_id' => $mdfe->id
				]);

			}

			$percursosTemp = Percurso::
			where('mdfe_id', $mdfe->id)
			->get();

			foreach($percursosTemp as $temp){
				$temp->delete();
			}

			if($percursos != null){

				foreach($percursos as $p){
			// return $p;
					Percurso::create([
						'uf' => strval($p),
						'mdfe_id' => $mdfe->id
					]);

				}
			}

			//limpa ValePedagio
			$vales = ValePedagio::
			where('mdfe_id', $mdfe->id)
			->get();
			// add ValePedagio
			foreach($vales as $v){
				$v->delete();
			}

			if($valePedagio != null){
				foreach($valePedagio as $v){
					ValePedagio::create([
						'mdfe_id' => $mdfe->id,
						'cnpj_fornecedor' => $v['cnpj_fornecedor'],
						'cnpj_fornecedor_pagador' => $v['doc_pagador'],
						'numero_compra' => $v['numero_compra'],
						'valor' => $v['valor']
					]);
				}
			}


			$ciots = Ciot::
			where('mdfe_id', $mdfe->id)
			->get();

			foreach($ciots as $c){
				$c->delete();
			}

			if($ciot != null){
				foreach($ciot as $c){
					Ciot::create([
						'mdfe_id' => $mdfe->id,
						'cpf_cnpj' => $c['documento'],
						'codigo' => $c['codigo']

					]);
				}
			}

			$infos = InfoDescarga::
			where('mdfe_id', $mdfe->id)
			->get();

			foreach($infos as $i){
				$i->delete();
			}

			foreach($infoDescarga as $i){

				$info = InfoDescarga::create([
					'mdfe_id' => $mdfe->id,
					'tp_unid_transp' => $i['tpTransp'],
					'id_unid_transp' => $i['idUnidTransp'],
					'quantidade_rateio' => $i['qtdRateioTransp'],
					'cidade_id' => (int)explode("-", $i['municipio'])[0]
				]);

				if($i['chaveNFe'] || $i['segCodNFe']){
					NFeDescarga::Create([
						'info_id' => $info->id,
						'chave' => str_replace(" ", "", $i['chaveNFe']),
						'seg_cod_barras' => str_replace(" ", "", $i['segCodNFe'])
					]);
				}

				if($i['chaveCTe'] || $i['segCodCTe']){
					CTeDescarga::Create([
						'info_id' => $info->id,
						'chave' => str_replace(" ", "", $i['chaveCTe']),
						'seg_cod_barras' => str_replace(" ", "", $i['segCodCTe'])
					]);
				}

				if(isset($i['lacresUnidCarga'])){
					foreach($i['lacresUnidCarga'] as $l){
						LacreUnidadeCarga::create([
							'info_id' => $info->id,
							'numero' => $l
						]);
					}
				}

				if(isset($i['lacresUnidTransp'])){
					foreach($i['lacresUnidTransp'] as $l){
						LacreTransporte::create([
							'info_id' => $info->id,
							'numero' => $l
						]);
					}
				}

				UnidadeCarga::create([
					'info_id' => $info->id,
					'id_unidade_carga' => $i['idUnidCarga'],
					'quantidade_rateio' => $i['qtdRateioUnidCarga']
				]);
			}
			return $mdfe;
		});
$this->criarLog($result, 'atualizar');
return response()->json($result, 200);

}catch(\Exception $e){
	__saveError($e, $this->empresa_id);
	return response()->json($e->getMessage(), 400);
}
}

public function delete($id){
	$mdfe = MDFe::find($id);

	if(valida_objeto($mdfe)){
		$this->criarLog($mdfe, 'deletar');
		$mdfe->delete();
		session()->flash("mensagem_sucesso", "MDFe removida!");
		return redirect()->back();
	}else{
		return redirect('/403');
	}
}

public function importarXml(Request $request){
	$xml = simplexml_load_file($request->file);
	$docs = $this->preparaNfe($xml);
	$pagamentoAtual = $this->parsePagamentoAtual($request->pagamento_atual);
	if((!isset($docs['pagamento']) || empty($docs['pagamento'])) && $pagamentoAtual){
		$docs['pagamento'] = $pagamentoAtual;
	}

	$lastMdfe = Mdfe::lastMdfe();

	$veiculos = Veiculo::
	where('empresa_id', $this->empresa_id)
	->get();

	$config = ConfigNota::
	where('empresa_id', $this->empresa_id)
	->first();

	$ufs = Mdfe::cUF();
	$cidades = Cidade::all();
	$tiposUnidadeTransporte = Mdfe::tiposUnidadeTransporte();

	if($config == null || sizeof($veiculos) == 0){
		return view("mdfe/erro")
		->with('veiculos', $veiculos)
		->with('config', $config)
		->with('clienteCadastrado', true)
		->with('title', "Validação para Emitir");

	}else{

		$motoristas = Motorista::
		where('empresa_id', $this->empresa_id)
		->get();
		return view("mdfe/register_nfe")
		->with('mdfeJs', true)
		->with('veiculos', $veiculos)
		->with('ufs', $ufs)
		->with('motoristas', $motoristas)
		->with('cidades', $cidades)
		->with('docs', $docs)
		->with('tiposUnidadeTransporte', $tiposUnidadeTransporte)
		->with('lastMdfe', $lastMdfe)
		->with('title', "Nova MDFe");
	}
}

public function createWithNfe($ids){

	$ids = explode(",", $ids);

	$docs = $this->preparaNfes($ids);

		// die;
	$lastMdfe = Mdfe::lastMdfe();

	$veiculos = Veiculo::
	where('empresa_id', $this->empresa_id)
	->get();

	$config = ConfigNota::
	where('empresa_id', $this->empresa_id)
	->first();

	$ufs = Mdfe::cUF();
	$cidades = Cidade::all();
	$tiposUnidadeTransporte = Mdfe::tiposUnidadeTransporte();

	if($config == null || sizeof($veiculos) == 0){
		return view("mdfe/erro")
		->with('veiculos', $veiculos)
		->with('config', $config)
		->with('clienteCadastrado', true)
		->with('title', "Validação para Emitir");

	}else{

		$motoristas = Motorista::
		where('empresa_id', $this->empresa_id)
		->get();
		return view("mdfe/register_nfe")
		->with('mdfeJs', true)
		->with('veiculos', $veiculos)
		->with('ufs', $ufs)
		->with('motoristas', $motoristas)
		->with('cidades', $cidades)
		->with('docs', $docs)
		->with('tiposUnidadeTransporte', $tiposUnidadeTransporte)
		->with('lastMdfe', $lastMdfe)
		->with('title', "Nova MDFe");
	}
}

private function preparaNfe($xml){
	$ufInicio = '';
	$ufFim = '';
	$cnpjContratante = '';

	$qtdCarga = 0;
	$valorCarga = 0;
	$veiculoTracao = null;
	$municipiosCarregamento = [];
	$infosDescarga = [];

	$infos = [];

	$data = [];


	$chave = $xml->NFe->infNFe->attributes()->Id;
	$chave = substr($chave, 3, 44);
				// array_push($chaves, $chave);


	$ufInicio = $xml->NFe->infNFe->emit->enderEmit->UF;
	$ufFim = $xml->NFe->infNFe->dest->enderDest->UF;
	$cnpjContratante = isset($xml->NFe->infNFe->emit->CNPJ) ? $xml->NFe->infNFe->emit->CNPJ : $xml->NFe->infNFe->emit->CPF;


	$munCarregamento = $xml->NFe->infNFe->emit->enderEmit->cMun;
	$mun = Cidade::where('codigo', (int)$munCarregamento)->first();
	if($this->validaArrayCidade($municipiosCarregamento, $mun)){
		$arr = [
			'id' => $mun->id,
			'nome' => $mun->nome
		];
		array_push($municipiosCarregamento, $arr);
	}

	$valorCarga += (float) $xml->NFe->infNFe->total->ICMSTot->vNF;

	$qtdCarga += $this->somaItens($xml);

	$placa = '';
	if(isset($xml->NFe->infNFe->transp->veicTransp) && $veiculoTracao == null){
		$veiculoTracao = Veiculo::where('placa', (string)$xml->NFe->infNFe->transp->veicTransp->placa)->first();
		$placa = (string)$xml->NFe->infNFe->transp->veicTransp->placa;
	}

	$munDescarregamento = $xml->NFe->infNFe->dest->enderDest->cMun;
	$mun = Cidade::where('codigo', (int)$munDescarregamento)->first();
	$infosDescarga = [
		'placa' => $placa,
		'chave_nfe' => $chave,
		'munDescarga' => $mun->id
	];


	array_push($infos, $infosDescarga);


	$data['infosDescarga'] = $infos;
	$data['ufInicio'] = $ufInicio;
	$data['ufFim'] = $ufFim;
	$data['cnpjContratante'] = $cnpjContratante;
	$data['valorCarga'] = $valorCarga;
	$data['qtdCarga'] = $qtdCarga;
	$data['veiculoTracao'] = $veiculoTracao;
	$data['munCarregamento'] = $municipiosCarregamento;
	$data['pagamento'] = $this->extrairPagamentoNfe($xml);
	return $data;

}

private function preparaNfes($ids){
	$ufInicio = '';
	$ufFim = '';
	$cnpjContratante = '';

	$qtdCarga = 0;
	$valorCarga = 0;
	$veiculoTracao = null;
	$municipiosCarregamento = [];
	$infosDescarga = [];

	$infos = [];

	$data = [];
	foreach($ids as $key => $id){
		$venda = Venda::find($id);
		if(file_exists(public_path('xml_nfe/').$venda->chave.'.xml')){
			$xml = file_get_contents(public_path('xml_nfe/').$venda->chave.'.xml');
			$xml = simplexml_load_string($xml);


			$chave = $xml->NFe->infNFe->attributes()->Id;
			$chave = substr($chave, 3, 44);
				// array_push($chaves, $chave);

			if($key == 0){
				$ufInicio = $xml->NFe->infNFe->emit->enderEmit->UF;
				$ufFim = $xml->NFe->infNFe->dest->enderDest->UF;
				$cnpjContratante = isset($xml->NFe->infNFe->emit->CNPJ) ? $xml->NFe->infNFe->emit->CNPJ : $xml->NFe->infNFe->emit->CPF;
			}

			$munCarregamento = $xml->NFe->infNFe->emit->enderEmit->cMun;
			$mun = Cidade::where('codigo', (int)$munCarregamento)->first();
			if($this->validaArrayCidade($municipiosCarregamento, $mun)){
				$arr = [
					'id' => $mun->id,
					'nome' => $mun->nome
				];
				array_push($municipiosCarregamento, $arr);
			}

			$valorCarga += (float) $xml->NFe->infNFe->total->ICMSTot->vNF;

			$qtdCarga += $this->somaItens($xml);

			$placa = '';
			if(isset($xml->NFe->infNFe->transp->veicTransp) && $veiculoTracao == null){
				$frete = $venda->frete;
				$veiculoTracao = Veiculo::where('placa', $frete->placa)->first();
				$placa = $frete->placa;
			}

			$munDescarregamento = $xml->NFe->infNFe->dest->enderDest->cMun;
			$mun = Cidade::where('codigo', (int)$munDescarregamento)->first();
			$infosDescarga = [
				'placa' => $placa,
				'chave_nfe' => $chave,
				'munDescarga' => $mun->id
			];


				// echo "<pre>";
				// // print_r($xml);
				// echo "</pre>";

			array_push($infos, $infosDescarga);
		}

	}

	$data['infosDescarga'] = $infos;
	$data['ufInicio'] = $ufInicio;
	$data['ufFim'] = $ufFim;
	$data['cnpjContratante'] = $cnpjContratante;
	$data['valorCarga'] = $valorCarga;
	$data['qtdCarga'] = $qtdCarga;
	$data['veiculoTracao'] = $veiculoTracao;
	$data['munCarregamento'] = $municipiosCarregamento;
	$data['pagamento'] = [];
	return $data;

}


private function parsePagamentoAtual($pagamentoAtual){
	if(!$pagamentoAtual){
		return null;
	}

	if(is_string($pagamentoAtual)){
		$decoded = json_decode($pagamentoAtual, true);
		if(json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)){
			return null;
		}
		$pagamentoAtual = $decoded;
	}

	$doc = preg_replace('/\D/', '', $pagamentoAtual['cpf_cnpj_pagador'] ?? '');
	if($doc === ''){
		return null;
	}

	$pagamentoAtual['cpf_cnpj_pagador'] = $doc;
	$pagamentoAtual['tipo_doc_pagador'] = strlen($doc) == 11 ? 'CPF' : 'CNPJ';
	$pagamentoAtual['componentes'] = $pagamentoAtual['componentes'] ?? [];
	$pagamentoAtual['parcelas'] = $pagamentoAtual['parcelas'] ?? [];

	return $pagamentoAtual;
}

private function extrairPagamentoNfe($xml){
	$retorno = [];
	$detPag = $xml->NFe->infNFe->pag->detPag ?? null;
	if(!$detPag){
		return $retorno;
	}

	$tpPag = (string)($detPag->tPag ?? '99');
	$vPag = (float)($detPag->vPag ?? 0);
	if($vPag <= 0){
		$vPag = (float)($xml->NFe->infNFe->total->ICMSTot->vNF ?? 0);
	}

	$doc = isset($xml->NFe->infNFe->emit->CNPJ) ? (string)$xml->NFe->infNFe->emit->CNPJ : (string)$xml->NFe->infNFe->emit->CPF;
	$doc = preg_replace('/\D/', '', $doc);

	$retorno = [
		'ind_pagamento' => '0',
		'forma_pagamento' => $tpPag ?: '99',
		'tipo_doc_pagador' => strlen($doc) == 11 ? 'CPF' : 'CNPJ',
		'cpf_cnpj_pagador' => $doc,
		'nome_pagador' => (string)($xml->NFe->infNFe->emit->xNome ?? ''),
		'valor_contrato' => $vPag,
		'valor_pagamento' => $vPag,
		'componentes' => [[
			'tipo_componente' => '01',
			'descricao' => 'Frete',
			'valor' => $vPag
		]],
		'parcelas' => []
	];

	return $retorno;
}

private function validaArrayCidade($municipiosCarregamento, $mun){
	foreach($municipiosCarregamento as $m){
		if($mun->id == $m['id']){
			return false;
		}
	}
	return true;
}

private function somaItens($xml){
	$soma = 0;
	foreach($xml->NFe->infNFe->det as $item) {
		$soma += $item->prod->qCom;
	}
	return $soma;
}

public function estadoFiscal($id){
	$mdfe = Mdfe::findOrFail($id);

	if(valida_objeto($mdfe)){

		$value = session('user_logged');

		return view("mdfe/alterar_estado_fiscal")
		->with('adm', $value['adm'])
		->with('mdfe', $mdfe)
		->with('title', "Alterar Estado #$id");
	}else{
		return redirect('/403');
	}
}

public function estadoFiscalStore(Request $request){
	try{
		$mdfe = Mdfe::findOrFail($request->mdfe_id);

		$estado = $request->estado;

		$mdfe->estado = $estado;
		if ($request->hasFile('file')){
			$file = $request->file;
			$xml = simplexml_load_file($request->file);

			$chave = substr((string)$xml->MDFe->infMDFe->attributes()->Id, 4, 44);
			$file->move(public_path('xml_mdfe'), $chave.'.xml');
			$mdfe->chave = $chave;
			$mdfe->mdfe_numero = (string)$xml->MDFe->infMDFe->ide->nMDF;

		}

		$mdfe->save();
		session()->flash("mensagem_sucesso", "Estado alterado");

	}catch(\Exception $e){
		session()->flash("mensagem_erro", "Erro: " . $e->getMessage());

	}
	return redirect()->back();
}
}
