<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DFeService;
use App\Models\ConfigNota;
use App\Models\ManifestaDfe;
use NFePHP\NFe\Common\Standardize;
use App\Models\Cidade;
use App\Models\Filial;
use App\Models\BuscaDocumentoLog;
use App\Models\Produto;
use App\Models\ItemCompra;
use App\Models\CategoriaConta;
use App\Models\Categoria;
use App\Models\Certificado;
use App\Models\Fornecedor;
use App\Models\Compra;
use App\Models\ContaPagar;
//use NFePHP\DA\NFe\Danfe;
use App\Services\CustomDanfe as Danfe;
use App\Models\ItemDfe;
use App\Models\ManifestoDia;
use App\Models\Usuario;
use App\Models\VendaCaixa;
use App\Models\Cliente;
use App\Models\ConfigCaixa;
use App\Models\Funcionario;
use App\Models\Acessor;
use App\Models\AberturaCaixa;
use App\Models\ItemVendaCaixa;
use App\Models\Pais;
use App\Models\GrupoCliente;

class DFeController extends Controller
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

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		if($config == null){
			session()->flash('mensagem_sucesso', 'Configure o Emitente');
			return redirect('configNF');
		}

		$certificado = Certificado::
		where('empresa_id', $this->empresa_id)
		->first();

		if($certificado == null){
			session()->flash('mensagem_erro', 'Configure o Certificado');
			return redirect('configNF');
		}

		$data_inicial = date('d/m/Y', strtotime("-90 day",strtotime(date("Y-m-d"))));
		$data_final = date('d/m/Y');

		$docs = ManifestaDfe::
		where('empresa_id', $this->empresa_id)
		->orderBy('id', 'desc')->get();
		$arrayDocs = [];
		foreach($docs as $d){
			$dIni = str_replace("/", "-", $data_inicial);
			$dFim = str_replace("/", "-", $data_final);

			$dIni = \Carbon\Carbon::parse($dIni)->format('Y-m-d');
			$dFim = \Carbon\Carbon::parse($dFim)->format('Y-m-d');
			$data_dfe = \Carbon\Carbon::parse($d->data_emissao)->format('Y-m-d');

			if(strtotime($data_dfe) >= strtotime($dIni) && strtotime($data_dfe) <= strtotime($dFim)){
				array_push($arrayDocs, $d);
			}
		}

		return view('dfe/index')
		->with('docs', $arrayDocs)
		->with('dfeJS', $arrayDocs)
		->with('busca_automatica', $config->busca_documento_automatico)
		->with('data_final', $data_final)
		->with('data_inicial', $data_inicial)
		->with('title', 'DF-e');
	}

	public function filtro(Request $request){

		$tipo = $request->tipo;
		$dataInicial = $request->data_inicial;
		$dataFinal = $request->data_final;
		$filial_id = $request->filial_id;

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		if($config == null){

			session()->flash('mensagem_sucesso', 'Configure o Emitente');
			return redirect('configNF');
		}

		$certificado = Certificado::
		where('empresa_id', $this->empresa_id)
		->first();
		if($certificado == null){

			session()->flash('mensagem_erro', 'Configure o Certificado');
			return redirect('configNF');
		}


		$docs = ManifestaDfe::
		where('empresa_id', $this->empresa_id)
		->orderBy('id', 'desc')
		->when($filial_id > 0, function ($query) use ($filial_id) {
			return $query->where('filial_id', $filial_id);
		})
		->get();
		$arrayDocs = [];

		foreach($docs as $d){
			$dIni = str_replace("/", "-", $dataInicial);
			$dFim = str_replace("/", "-", $dataFinal);

			$dIni = \Carbon\Carbon::parse($dIni)->format('Y-m-d');
			$dFim = \Carbon\Carbon::parse($dFim)->format('Y-m-d');
			$data_dfe = \Carbon\Carbon::parse($d->data_emissao)->format('Y-m-d');
			if($tipo != '--'){
				if(strtotime($data_dfe) >= strtotime($dIni) && strtotime($data_dfe) <= strtotime($dFim)){
					if($d->tipo == $tipo){
						array_push($arrayDocs, $d);
					}
				}
			}else{
				if(strtotime($data_dfe) >= strtotime($dIni) && strtotime($data_dfe) <= strtotime($dFim)){
					array_push($arrayDocs, $d);
				}
			}
		}

		return view('dfe/index')
		->with('docs', $arrayDocs)
		->with('dfeJS', $arrayDocs)
		->with('filial_id', $filial_id)
		->with('busca_automatica', $config->busca_documento_automatico)
		->with('data_final', $dataFinal)
		->with('data_inicial', $dataInicial)
		->with('title', 'DF-e');
	}

	public function getDocumentos(Request $request){
		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

		$data_inicial = str_replace("/", "-", $request->data_inicial);
		$data_final = str_replace("/", "-", $request->data_final);

		$dfe_service = new DFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => 1,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->UF,
			"cnpj" => $cnpj,
			"schemes" => "PL_009_V4",
			"versao" => "4.00",
			"tokenIBPT" => "AAAAAAA",
			"CSC" => $config->csc,
			"CSCid" => $config->csc_id
		], 55);

		$docs = $this->validaDocsIncluidos($dfe_service->consulta(
			\Carbon\Carbon::parse($data_inicial)->format('Y-m-d'),
			\Carbon\Carbon::parse($data_final)->format('Y-m-d')
		));

		usort($docs, function ($a, $b) {
			return \Carbon\Carbon::parse($a['data_emissao'])->format('Y-m-d') <
			\Carbon\Carbon::parse($b['data_emissao'])->format('Y-m-d');
		});

		for($aux = 0; $aux < count($docs); $aux++){
			$docs[$aux]['data_emissao'] = \Carbon\Carbon::parse($docs[$aux]['data_emissao'])->format('d/m/Y H:i:s');
		}

		return response()->json($docs, 200);
	}



	private function validaDocsIncluidos($docs){
		for($aux = 0; $aux < count($docs); $aux++){
			if($docs[$aux]){
				$manifesta = ManifestaDfe::
				where('empresa_id', $this->empresa_id)
				->where('chave', $docs[$aux]['chave'])->first();
				if($manifesta != null){
					$docs[$aux]['incluso'] = true;
					$docs[$aux]['tipo'] = $manifesta->tipo;
				}
			}
		}
		return $docs;
	}

	public function manifestar(Request $request){

		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

		$dfe_service = new DFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => 1,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->UF,
			"cnpj" => $cnpj,
			"schemes" => "PL_009_V4",
			"versao" => "4.00",
			"tokenIBPT" => "AAAAAAA",
			"CSC" => $config->csc,
			"CSCid" => $config->csc_id
		], 55);
		$evento = $request->evento;
		$manifestaAnterior = $this->verificaAnterior($request->chave);
		$numEvento = $manifestaAnterior != null ? ((int)$manifestaAnterior->sequencia_evento + 1) : 1;

		if($manifestaAnterior != null && $manifestaAnterior->tipo != $evento){
			$numEvento--;
		}

		if($numEvento == 0) $numEvento++;

		if($evento == 1){
			$res = $dfe_service->manifesta($request->chave,	$numEvento);
		}else if($evento == 2){
			$res = $dfe_service->confirmacao($request->chave, $numEvento);
		}else if($evento == 3){
			$res = $dfe_service->desconhecimento($request->chave, $numEvento, $request->justificativa);
		}else if($evento == 4){

			$res = $dfe_service->operacaoNaoRealizada($request->chave, $numEvento, $request->justificativa);
		}

		try{

			if($res['retEvento']['infEvento']['cStat'] == '135'){ //sucesso

				$manifesto = ManifestaDfe::
				where('empresa_id', $this->empresa_id)
				->where('chave', $request->chave)
				->first();
				$manifesto->sequencia_evento = $manifestaAnterior != null ? ($manifestaAnterior->sequencia_evento + 1) : 1;
				$manifesto->tipo = $evento;
				$manifesto->save();

			// ManifestaDfe::create($manifesta);
				session()->flash('mensagem_sucesso', $res['retEvento']['infEvento']['xMotivo'] . ": " . $request->chave);
			}else{

				// $manifesto = ManifestaDfe::
				// where('empresa_id', $this->empresa_id)
				// ->where('chave', $request->chave)
				// ->first();

				// // $manifesto->tipo = $evento;
				// $manifesto->save();

				$erro = "[" .$res['retEvento']['infEvento']['cStat'] . "] " . $res['retEvento']['infEvento']['xMotivo'];

				session()->flash('mensagem_erro', $erro . " - Chave: ". $request->chave);
			}
			return redirect('/dfe');

		}catch(\Exception $e){
			echo $e->getMessage();
		}

	}

	private function verificaAnterior($chave){
		return ManifestaDfe::
		where('empresa_id', $this->empresa_id)
		->where('chave', $chave)->first();
	}

	public function download($chave){
		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$dfe = ManifestaDfe::where('chave', $chave)
		->first();

		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

		$dfe_service = new DFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => 1,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->UF,
			"cnpj" => $cnpj,
			"schemes" => "PL_009_V4",
			"versao" => "4.00",
			"tokenIBPT" => "AAAAAAA",
			"CSC" => $config->csc,
			"CSCid" => $config->csc_id
		], 55);
		try{

			$public = env('SERVIDOR_WEB') ? 'public/' : '';

			$file_exists = false;
			if(file_exists(public_path('xml_dfe/').$chave.'.xml')){
				$file_exists = true;
			}

			if(!$file_exists){
				$response = $dfe_service->download($chave);
				$stz = new Standardize($response);
				$std = $stz->toStd();
			}else{
				$std = null;
			}

			if($std != null && ($std->cStat != 138)){
				session()->flash("mensagem_erro", "Documento não retornado. [$std->cStat] $std->xMotivo!");
				return redirect()->back();
			}else{
				if(!$file_exists){
					$zip = $std->loteDistDFeInt->docZip;
					$xml = gzdecode(base64_decode($zip));

					safe_file_put_contents(public_path('xml_dfe/').$chave.'.xml', $xml);
				}else{
					$xml = safe_file_get_contents(public_path('xml_dfe/').$chave.'.xml');
				}

				if(strlen($xml) < 1000){
					unlink(public_path('xml_dfe/').$chave.'.xml');
				}

				$nfe = simplexml_load_string($xml);
				$nNF = $nfe->NFe->infNFe->ide->nNF;
				$dfe->nNF = $nNF;
				$dfe->save();

				if(!$nfe) {
					session()->flash('mensagem_erro', 'Erro ao ler XML');
					return redirect('/dfe');
				}else{
					// echo "<pre>";
					// print_r($nfe);
					// echo "</pre>";

					// die;
					if(!isset($nfe->NFe->infNFe->emit->xNome)){
						session()->flash('mensagem_erro', 'Isso não é uma NFe');
						return redirect('/dfe');
					}

					$fornecedor = $this->getFornecedorXML($nfe);
					$itens = $this->getItensDaNFe($nfe);
					$infos = $this->getInfosDaNFe($nfe);
					$fatura = $this->getFaturaDaNFe($nfe);
			// echo "<pre>";
			// print_r($fatura);
			// echo "</pre>";

					$forn = Fornecedor::
					where('cpf_cnpj', $this->formataCnpj($fornecedor['cnpj']))
					->first();
			//caregar view

					$categorias = Categoria::
					where('empresa_id', $this->empresa_id)
					->get();
					$unidadesDeMedida = Produto::unidadesMedida();

					$listaCSTCSOSN = Produto::listaCSTCSOSN();
					$listaCST_PIS_COFINS = Produto::listaCST_PIS_COFINS();
					$listaCST_IPI = Produto::listaCST_IPI();
					$config = ConfigNota::
					where('empresa_id', $this->empresa_id)
					->first();

					$manifesto = ManifestaDfe::
					where('empresa_id', $this->empresa_id)
					->where('chave', $chave)->first();

					$compra = Compra::
					where('chave', $chave)
					->where('empresa_id', $this->empresa_id)
					->first();

					$vDesc = $nfe->NFe->infNFe->total->ICMSTot->vDesc;
					$nNf = $nfe->NFe->infNFe->ide->nNF;

					$anps = Produto::lista_ANP();

					return view('dfe/view')
					->with('fornecedor', $fornecedor)
					->with('itens', $itens)
					->with('vDesc', $vDesc)
					->with('anps', $anps)
					->with('nNf', $nNf)
					->with('infos', $infos)
					->with('forn', $forn)
					->with('dfeJS', true)
					->with('compraFiscal', $compra != null ? true : false)
					->with('fatura', $fatura)
					->with('dfe', $dfe)
					->with('listaCSTCSOSN', $listaCSTCSOSN)
					->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
					->with('listaCST_IPI', $listaCST_IPI)
					->with('categorias', $categorias)
					->with('config', $config)
					->with('fatura_salva', $manifesto == null ? false : $manifesto->fatura_salva)
					->with('unidadesDeMedida', $unidadesDeMedida)
					->with('title', 'Visualizando XML');
				}
			}
		}catch(\Exception $e){
			echo "Erro de soap:<br>";
			echo $e->getMessage();
		}

	}

	public function imprimirDanfe($chave){
		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

		$dfe_service = new DFeService([
			"atualizacao" => date('Y-m-d h:i:s'),
			"tpAmb" => 1,
			"razaosocial" => $config->razao_social,
			"siglaUF" => $config->UF,
			"cnpj" => $cnpj,
			"schemes" => "PL_009_V4",
			"versao" => "4.00",
			"tokenIBPT" => "AAAAAAA",
			"CSC" => $config->csc,
			"CSCid" => $config->csc_id
		], 55);

		// $response = $dfe_service->download($chave);

		$public = env('SERVIDOR_WEB') ? 'public/' : '';

		$file_exists = false;
		if(file_exists(public_path('xml_dfe/').$chave.'.xml')){
			$file_exists = true;
		}

		if(!$file_exists){
			$response = $dfe_service->download($chave);
			$stz = new Standardize($response);
			$std = $stz->toStd();
		}else{
			$std = null;
		}
		// print_r($response);
		try {
			if(!$file_exists){
				$zip = $std->loteDistDFeInt->docZip;
				$xml = gzdecode(base64_decode($zip));

				safe_file_put_contents(public_path('xml_dfe/').$chave.'.xml', $xml);
			}else{
				$xml = safe_file_get_contents(public_path('xml_dfe/').$chave.'.xml');
			}

			if ($std != null && $std->cStat != 138) {
				echo "Documento não retornado. [$std->cStat] $std->xMotivo" . ", aguarde alguns instantes e atualize a pagina!";
				die;
			}
			$dfe = ManifestaDfe::where('chave', $chave)->first();
			$nfe = simplexml_load_string($xml);
			$nNF = $nfe->NFe->infNFe->ide->nNF;
			$dfe->nNF = $nNF;
			$dfe->save();

			$public = env('SERVIDOR_WEB') ? 'public/' : '';

			safe_file_put_contents(public_path('xml_dfe/').$chave.'.xml',$xml);

			$danfe = new Danfe($xml);
			// $id = $danfe->monta();
			$pdf = $danfe->render();
			header('Content-Type: application/pdf');
			// echo $pdf;
			return response($pdf)
			->header('Content-Type', 'application/pdf');
		} catch (InvalidArgumentException $e) {
			echo "Ocorreu um erro durante o processamento :" . $e->getMessage();
		}

	}

	private function getFornecedorXML($xml){

		$cidade = Cidade::getCidadeCod($xml->NFe->infNFe->emit->enderEmit->cMun);
		$fornecedor = [
			'cpf' => $xml->NFe->infNFe->emit->CPF,
			'cnpj' => $xml->NFe->infNFe->emit->CNPJ,
			'razaoSocial' => $xml->NFe->infNFe->emit->xNome,
			'nomeFantasia' => $xml->NFe->infNFe->emit->xFant ?? $xml->NFe->infNFe->emit->xNome,
			'logradouro' => $xml->NFe->infNFe->emit->enderEmit->xLgr,
			'numero' => $xml->NFe->infNFe->emit->enderEmit->nro,
			'bairro' => $xml->NFe->infNFe->emit->enderEmit->xBairro,
			'cep' => $xml->NFe->infNFe->emit->enderEmit->CEP,
			'fone' => $xml->NFe->infNFe->emit->enderEmit->fone,
			'ie' => $xml->NFe->infNFe->emit->IE,
			'cidade_id' => $cidade->id
		];

		$fornecedorEncontrado = $this->verificaFornecedor($xml->NFe->infNFe->emit->CNPJ);
		if($fornecedorEncontrado){
			$fornecedor['novo_cadastrado'] = false;
		}else{
			$fornecedor['novo_cadastrado'] = true;
			$idFornecedor = $this->cadastrarFornecedor($fornecedor);
		}

		return $fornecedor;
	}

	private function getItensDaNFe($xml){
		$itens = [];
		foreach($xml->NFe->infNFe->det as $item) {

			$produto = Produto::verificaCadastrado($item->prod->cEAN,
				$item->prod->xProd, $item->prod->cProd);

			$produtoNovo = !$produto ? true : false;

			$tp = null;
			$vVenda = 0;

			if($produto != null){
				$tp = ItemDfe::
				where('produto_id', $produto->id)
				->where('numero_nfe', $xml->NFe->infNFe->ide->nNF)
				->where('empresa_id', $this->empresa_id)
				->first();

				$vVenda = $item->prod->vUnCom +
				(($item->prod->vUnCom*$produto->percentual_lucro)/100);
			}

			$nomeProduto = $item->prod->xProd;

			if($produto != null && $nomeProduto != $produto->nome){
				$nomeProduto .= " ($produto->nome)";
			}

			$nomeProduto = str_replace("'", "", $nomeProduto);
			$cod = str_replace(" ", "", $item->prod->cProd);
			$cod = str_replace(".", "_", $cod);
			$cod = str_replace("(", "", $cod);
			$cod = str_replace(")", "", $cod);
			$item = [
				'codigo' => $cod,
				'xProd' => $nomeProduto,
				'NCM' => $item->prod->NCM,
				'CFOP' => $item->prod->CFOP,
				'CEST' => $item->prod->CEST,
				'uCom' => $item->prod->uCom,
				'vUnCom' => $item->prod->vUnCom,
				'vUnVenda' => $vVenda,
				'qCom' => $item->prod->qCom,
				'codBarras' => $item->prod->cEAN,
				'produtoNovo' => $produtoNovo,
				'produto_id' => $produtoNovo ? null : $produto->id,
				'produtoSetadoEstoque' => $tp != null ? true : false,
				'produtoId' => $produtoNovo ? '0' : $produto->id,
				'conversao_unitaria' => $produtoNovo ? '' : $produto->conversao_unitaria
			];
			array_push($itens, $item);
			// dd($item);
		}
		return $itens;
	}

	private function getInfosDaNFe($xml){
		$chave = substr($xml->NFe->infNFe->attributes()->Id, 3, 44);
		$vFrete = number_format((double) $xml->NFe->infNFe->total->ICMSTot->vFrete,
			2, ",", ".");
		$vDesc = number_format((double) $xml->NFe->infNFe->total->ICMSTot->vDesc, 2, ",", ".");
		return [
			'chave' => $chave,
			'vProd' => $xml->NFe->infNFe->total->ICMSTot->vProd,
			'vNF' => $xml->NFe->infNFe->total->ICMSTot->vNF,
			'vDesc' => $xml->NFe->infNFe->total->ICMSTot->vDesc,
			'indPag' => $xml->NFe->infNFe->ide->indPag,
			'nNf' => $xml->NFe->infNFe->ide->nNF,
			'vFrete' => $vFrete,
			'vDesc' => $vDesc
		];
	}

	private function getFaturaDaNFe($xml){
		if (!empty($xml->NFe->infNFe->cobr->dup))
		{
			$fatura = [];
			$cont = 1;
			foreach($xml->NFe->infNFe->cobr->dup as $dup) {
				$titulo = $dup->nDup;
				$vencimento = $dup->dVenc;
				$vencimento = explode('-', $vencimento);
				$vencimento = $vencimento[2]."/".$vencimento[1]."/".$vencimento[0];
				$vlr_parcela = number_format((double) $dup->vDup, 2, ",", ".");

				$parcela = [
					'numero' => $titulo,
					'vencimento' => $vencimento,
					'valor_parcela' => $vlr_parcela,
					'referencia' => $xml->NFe->infNFe->ide->nNF . "/" . $cont
				];
				array_push($fatura, $parcela);
				$cont++;
			}
			return $fatura;
		}
		return [];
	}


	private function verificaFornecedor($cnpj){
		$forn = Fornecedor::verificaCadastrado($this->formataCnpj($cnpj));
		return $forn;
	}

	private function cadastrarFornecedor($fornecedor){

		$result = Fornecedor::create([
			'razao_social' => $fornecedor['razaoSocial'],
			'nome_fantasia' => $fornecedor['nomeFantasia'],
			'rua' => $fornecedor['logradouro'],
			'numero' => $fornecedor['numero'],
			'bairro' => $fornecedor['bairro'],
			'cep' => $this->formataCep($fornecedor['cep']),
			'cpf_cnpj' => $this->formataCnpj($fornecedor['cnpj']),
			'ie_rg' => $fornecedor['ie'],
			'celular' => '*',
			'telefone' => $this->formataTelefone($fornecedor['fone']),
			'email' => '*',
			'cidade_id' => $fornecedor['cidade_id'],
			'empresa_id' => $this->empresa_id
		]);
		return $result->id;
	}

	private function formataCnpj($cnpj){
		$temp = substr($cnpj, 0, 2);
		$temp .= ".".substr($cnpj, 2, 3);
		$temp .= ".".substr($cnpj, 5, 3);
		$temp .= "/".substr($cnpj, 8, 4);
		$temp .= "-".substr($cnpj, 12, 2);
		return $temp;
	}

	private function formataCep($cep){
		$temp = substr($cep, 0, 5);
		$temp .= "-".substr($cep, 5, 3);
		return $temp;
	}

	private function formataTelefone($fone){
		$temp = substr($fone, 0, 2);
		$temp .= " ".substr($fone, 2, 4);
		$temp .= "-".substr($fone, 4, 4);
		return $temp;
	}

	public function salvarFatura(Request $request){
		$chave =  $request->chave;

		$manifesto = ManifestaDfe::
		where('empresa_id', $this->empresa_id)
		->where('chave', $chave)->first();
		$manifesto->fatura_salva = true;
		$manifesto->save();
		// die;

		$fatura = json_decode($request->fatura);
		foreach($fatura as $fat){
			$valor = str_replace(".", "", $fat->valor_parcela);
			$valor = str_replace(",", ".", $valor);

			$conta = [
				'compra_id' => NULL,
				'data_vencimento' => \Carbon\Carbon::parse(str_replace("/", "-", $fat->vencimento))->format('Y-m-d'),
				'data_pagamento' => \Carbon\Carbon::parse(str_replace("/", "-", $fat->vencimento))->format('Y-m-d'),
				'valor_integral' => $valor,
				'valor_pago' => 0,
				'referencia' => $fat->referencia,
				'categoria_id' => CategoriaConta::where('empresa_id', $this->empresa_id)->first()->id,
				'status' => false,
				'empresa_id' => $this->empresa_id,
				'fornecedor_id' => $request->fornecedor,
			];

			ContaPagar::create($conta);
		}


		session()->flash('mensagem_sucesso', 'Fatura salva!');
		return redirect('/dfe/download/'.$chave);
	}

	public function novaConsulta(){
		$d1 = date("Y-m-d");
		$d2 = date('Y-m-d', strtotime('+1 day'));
		$maximoConsultaDia = env("CONSULTAS_MANIFESTO_DIA");
		$consultas = ManifestoDia::
		whereBetween('created_at', [$d1,
			$d2])
		->where('empresa_id', $this->empresa_id)
		->get();

		if(sizeof($consultas) < $maximoConsultaDia){
			return view('dfe/nova_consulta')
			->with('dfeJS', true)
			->with('title', 'Nova Consulta');
		}else{
			session()->flash('mensagem_erro', 'Você pode realizar até ' . $maximoConsultaDia . ' por dia!!');
			return redirect('/dfe');
		}
	}

	public function getDocumentosNovosTeste(){
		try{
			$config = ConfigNota::
			where('empresa_id', $this->empresa_id)
			->first();

			$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

			$dfe_service = new DFeService([
				"atualizacao" => date('Y-m-d h:i:s'),
				"tpAmb" => 1,
				"razaosocial" => $config->razao_social,
				"siglaUF" => $config->UF,
				"cnpj" => $cnpj,
				"schemes" => "PL_009_V4",
				"versao" => "4.00",
				"tokenIBPT" => "AAAAAAA",
				"CSC" => $config->csc,
				"CSCid" => $config->csc_id
			], 55);

			$manifesto = ManifestaDfe::
			where('empresa_id', $this->empresa_id)
			->orderBy('nsu', 'desc')->first();

			if($manifesto == null) $nsu = 0;
			else $nsu = $manifesto->nsu;

			$docs = $dfe_service->novaConsulta($nsu);
			echo "<pre>";
			print_r($docs);
			echo "</pre>";

			// if(!isset($docs['erro'])){

			// 	$novos = [];
			// 	foreach($docs as $d) {
			// 		print_r($d);
			// 		// if($this->validaNaoInserido($d['chave'])){
			// 		// 	if($d['valor'] > 0 && $d['nome']){
			// 		// 		ManifestaDfe::create($d);
			// 		// 		array_push($novos, $d);
			// 		// 	}
			// 		// }
			// 	}
			// 	die();
			// 	// ManifestoDia::create([
			// 	// 	'empresa_id' => $this->empresa_id
			// 	// ]);
			// 	// return response()->json($novos, 200);
			// }else{
			// 	return response()->json($docs, 401);
			// }

		}catch(Exception $e){
			echo "err: " . $e->getMessage();
			return response()->json($e->getMessage(), 403);
		}

	}

	public function getDocumentosNovos(Request $request){
		try{
			$local = $request->local;
			$config = ConfigNota::
			where('empresa_id', $this->empresa_id)
			->first();
			$isFilial = null;
			if($local > 0){
				$config = Filial::findOrFail($local);
				$isFilial = $local;
			}

			$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

			$dfe_service = new DFeService([
				"atualizacao" => date('Y-m-d h:i:s'),
				"tpAmb" => 1,
				"razaosocial" => $config->razao_social,
				"siglaUF" => $config->UF,
				"cnpj" => $cnpj,
				"schemes" => "PL_009_V4",
				"versao" => "4.00",
				"tokenIBPT" => "AAAAAAA",
				"CSC" => $config->csc,
				"CSCid" => $config->csc_id,
				"is_filial" => $isFilial
			], 55);

			$manifesto = ManifestaDfe::
			where('empresa_id', $this->empresa_id)
			->when($local > 0, function ($query) use ($local) {
				return $query->where('filial_id', $local);
			})
			->orderBy('nsu', 'desc')->first();

			if($manifesto == null) $nsu = 0;
			else $nsu = $manifesto->nsu;
			$docs = $dfe_service->novaConsulta($nsu);

			$novos = [];

			if(!isset($docs['erro'])){

				if($docs == null){
					return response()->json("Erro ao buscar", 403);
				}
				$novos = [];
				foreach($docs as $d) {
					if($this->validaNaoInserido($d['chave'])){
						if($d['valor'] > 0 && $d['nome']){
							$d['filial_id'] = $local > 0 ? $local : null;
							ManifestaDfe::create($d);
							array_push($novos, $d);
						}
					}
				}

				ManifestoDia::create([
					'empresa_id' => $this->empresa_id
				]);
				return response()->json($novos, 200);
			}else{
				return response()->json($docs, 401);
			}

		}catch(Exception $e){
			return response()->json($e->getMessage(), 403);
		}

	}

	private function validaNaoInserido($chave){
		$m = ManifestaDfe::
		where('empresa_id', $this->empresa_id)
		->where('chave', $chave)->first();
		if($m == null) return true;
		else return false;
	}

	public function downloadXml($chave){
		$dfe = ManifestaDfe::
		where('empresa_id', $this->empresa_id)
		->where('chave', $chave)->first();
		$chave = $dfe->chave;
		$public = env('SERVIDOR_WEB') ? 'public/' : '';
		if(file_exists(public_path('xml_dfe/').$chave.'.xml'))
			return response()->download(public_path('xml_dfe/').$chave.'.xml');
		else echo "Erro ao baixar XML, arquivo não encontrado!";
	}

	public function salvar(Request $request){
		try{
			$fornecedor = Fornecedor::find($request->fornecedor);
			$dfe = ManifestaDfe::find($request->dfe_id);
			$itens = json_decode($request->itens);

			$result = Compra::create([
				'fornecedor_id' => $fornecedor->id,
				'usuario_id' => get_id_user(),
				'nf' => $request->nNf,
				'observacao' => '',
				'valor' => $dfe->valor,
				'desconto' => $request->vDesc,
				'xml_path' => '',
				'estado' => 'APROVADO',
				'numero_emissao' => 0,
				'chave' => $dfe->chave,
				'empresa_id' => $this->empresa_id
			]);

			$dfe->compra_id = $result->id;
			$dfe->save();

			foreach($itens as $i){
				$qCom = get_object_vars($i->qCom)[0];
				$vUnCom = get_object_vars($i->vUnCom)[0];
				$uCom = get_object_vars($i->uCom)[0];
				$CFOP = get_object_vars($i->CFOP)[0];

				ItemCompra::create([
					'compra_id' => $result->id,
					'produto_id' => (int) $i->produtoId,
					'quantidade' =>  str_replace(",", ".", $qCom),
					'valor_unitario' => str_replace(",", ".", $vUnCom),
					'unidade_compra' => $uCom,
					'cfop_entrada' => $CFOP,
					'codigo_siad' => ''
				]);
			}

			session()->flash('mensagem_sucesso', 'Salvo em compras');
		}catch(\Exception $e){
			session()->flash('mensagem_sucesso', 'Erro: ' . $e->getMessage());
		}
		return redirect()->back();
	}

	public function logs(){
		$data = BuscaDocumentoLog::where('empresa_id', $this->empresa_id)
		->orderBy('id', 'desc')->paginate(30);

		return view('dfe/logs', compact('data'))
		->with('title', 'Logs de consulta');
	}

	public function teste(){
		$configs = ConfigNota::where('busca_documento_automatico', 1)->get();

		foreach($configs as $config){
			$certificado = Certificado::where('empresa_id', $config->empresa_id)->first();
			if($certificado != null){
				$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

				$dfe_service = new DFeService([
					"atualizacao" => date('Y-m-d h:i:s'),
					"tpAmb" => 1,
					"razaosocial" => $config->razao_social,
					"siglaUF" => $config->UF,
					"cnpj" => $cnpj,
					"schemes" => "PL_009_V4",
					"versao" => "4.00",
					"tokenIBPT" => "AAAAAAA",
					"CSC" => $config->csc,
					"CSCid" => $config->csc_id,
					"is_filial" => null
				], 55, $config->empresa_id);

				$manifesto = ManifestaDfe::
				where('empresa_id', $config->empresa_id)
				->orderBy('nsu', 'desc')->first();

				if($manifesto == null) $nsu = 0;
				else $nsu = $manifesto->nsu;

				$docs = $dfe_service->novaConsulta($nsu, $config->empresa_id);

				if(!isset($docs['erro'])){
					$novos = 0;
					try{
						foreach($docs as $d) {
							if($this->validaNaoInserido($d['chave'])){
								if($d['valor'] > 0 && $d['nome']){
									ManifestaDfe::create($d);
									$novos++;
								}
							}
						}
						$resultado = "Busca realizada com sucesso, foram encontrados $novos documentos";
						BuscaDocumentoLog::create([
							'empresa_id' => $config->empresa_id,
							'resultado' => $resultado,
							'sucesso' => 1
						]);
					}catch(\Exception $e){
						echo $e->getFile();
						die;
					}
				}else{
					BuscaDocumentoLog::create([
						'empresa_id' => $config->empresa_id,
						'resultado' => "algo errado: " . $docs['message'] . " - ult nsu " . $nsu,
						'sucesso' => 0
					]);
				}
			}
		}
	}

	public function gerarVenda($id){
		$item = ManifestaDfe::findOrFail($id);
		if(!file_exists(public_path('xml_dfe/').$item->chave.'.xml')){
			session()->flash('mensagem_erro', 'Arquivo XML não encontrado!');
			return redirect()->back();
		}

		$xml = safe_file_get_contents(public_path('xml_dfe/').$item->chave.'.xml');
		$nfe = simplexml_load_string($xml);

		$itens = $this->getItensDaNFe($nfe);
		$atributes = [];
		foreach($itens as $i){

			$produto = Produto::where('empresa_id', $this->empresa_id)
			->where('id', $i['produto_id'])->first();

			array_push($atributes, [
				'produto' => $produto,
				'quantidade' => (float)$i['qCom']
			]);
		}

		$atributes = $this->addAtributes($atributes);

		$usuario = Usuario::find(get_id_user());
		$tiposPagamento = VendaCaixa::tiposPagamento();
		$config = ConfigNota::
		where('empresa_id', $this->empresa_id)
		->first();

		$produtosGroup = Produto::
		where('empresa_id', $this->empresa_id)
		->where('inativo', false)
		->where('valor_venda', '>', 0)
		->groupBy('referencia_grade')
		->get();

		$certificado = Certificado::
		where('empresa_id', $this->empresa_id)
		->first();

		$tiposPagamentoMulti = VendaCaixa::tiposPagamentoMulti();
		$produtos = Produto::
		where('empresa_id', $this->empresa_id)
		->where('inativo', false)
		->orderBy('nome')->get();

		$categorias = Categoria::
		where('empresa_id', $this->empresa_id)
		->get();

		$clientes = Cliente::where('empresa_id', $this->empresa_id)
		->where('inativo', false)
		->orderBy('razao_social')->get();

		$atalhos = ConfigCaixa::
		where('usuario_id', get_id_user())
		->first();

		$funcionarios = Funcionario::
		where('funcionarios.empresa_id', $this->empresa_id)
		->select('funcionarios.*')
		->join('usuarios', 'usuarios.id', '=', 'funcionarios.usuario_id')
		->get();

		$view = 'main3';
    // if($atalhos != null && $atalhos->modelo_pdv == 1){
    //   $view = 'main2';
    // }

		$rascunhos = $this->getRascunhos();
		$consignadas = $this->getConsignadas();
		$acessores = Acessor::where('empresa_id', $this->empresa_id)->get();
		$produtosMaisVendidos = $this->produtosMaisVendidos();
		$vendedores = [];

		$usuarios = Usuario::where('empresa_id', $this->empresa_id)
		->where('ativo', 1)
		->orderBy('nome', 'asc')
		->get();

		foreach($usuarios as $u){
			if($u->funcionario){
				array_push($vendedores, $u);
			}
		}

		$estados = Cliente::estados();
		$cidades = Cidade::all();
		$pais = Pais::all();
		$grupos = GrupoCliente::get();
		$acessores = Acessor::where('empresa_id', $this->empresa_id)->get();
		$funcionarios = Funcionario::where('empresa_id', $this->empresa_id)->get();
    // return view('frontBox/main')
		$abertura = AberturaCaixa::where('empresa_id', $this->empresa_id)
		->where('usuario_id', get_id_user())
		->where('status', 0)
		->orderBy('id', 'desc')
		->first();
		$filial = $abertura != null ? $abertura->filial : null;

		return view('frontBox/'.$view)
		->with('itens', $atributes)
		->with('dfe', $item)
		->with('atalhos', $atalhos)
		->with('filial', $filial)
		->with('estados', $estados)
		->with('cidades', $cidades)
		->with('pais', $pais)
		->with('grupos', $grupos)
		->with('vendedores', $vendedores)
		->with('usuarios', $usuarios)
		->with('acessores', $acessores)
		->with('produtosMaisVendidos', $produtosMaisVendidos)
		->with('rascunhos', $rascunhos)
		->with('consignadas', $consignadas)
		->with('funcionarios', $funcionarios)
		->with('produtosGroup', $produtosGroup)
		->with('frenteCaixa', true)
		->with('tiposPagamento', $tiposPagamento)
		->with('tiposPagamentoMulti', $tiposPagamentoMulti)
		->with('config', $config)
		->with('usuario', $usuario)
		->with('clientes', $clientes)
		->with('produtos', $produtos)
		->with('categorias', $categorias)
		->with('certificado', $certificado)

		->with('title', 'Gerando Venda DFe '.$id);

	}

	private function addAtributes($itens){
		$temp = [];

		foreach($itens as $i){
			$obj = new \stdClass();

			$obj->produto = $i['produto'];

			$obj->produto_id = $obj->produto->id;
			$obj->imagem = $obj->produto->imagem;
			$obj->quantidade = $i['quantidade'];
			array_push($temp, $obj);
		}
        // echo json_encode($temp);
		return $temp;
	}

	private function getRascunhos(){
		return VendaCaixa::
		where('rascunho', 1)
		->where('empresa_id', $this->empresa_id)
		->limit(20)
		->orderBy('id', 'desc')
		->get();
	}

	private function getConsignadas(){
		return VendaCaixa::
		where('consignado', 1)
		->where('empresa_id', $this->empresa_id)
		->limit(20)
		->orderBy('id', 'desc')
		->get();
	}

	private function produtosMaisVendidos(){

		$abertura = AberturaCaixa::where('empresa_id', $this->empresa_id)
		->where('usuario_id', get_id_user())
		->where('status', 0)
		->orderBy('id', 'desc')
		->first();
		$filial = -1;

		if($abertura){
			$filial = $abertura->filial_id;
			if($filial == null){
				$filial = -1;
			}
		}
		$itens = ItemVendaCaixa::
		selectRaw('item_venda_caixas.*, count(quantidade) as qtd')
		->join('venda_caixas', 'venda_caixas.id', '=', 'item_venda_caixas.venda_caixa_id')
		->join('produtos', 'produtos.id', '=', 'item_venda_caixas.produto_id')
		->where('venda_caixas.empresa_id', $this->empresa_id)
		->groupBy('item_venda_caixas.produto_id')
		->orderBy('qtd')
		->when(empresaComFilial(), function ($q) use ($filial) {
			return $q->where(function($query) use ($filial){
				$query->where('produtos.locais', 'like', "%{$filial}%");
			});
		})
		->limit(21)
		->get();

		$produtos = [];
		foreach($itens as $i){
			$p = Produto::find($i->produto_id);
			if(!$p->inativo){
				array_push($produtos, $p);
			}
		}
		return $produtos;
	}
}
