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
use App\Models\Veiculo;
use Illuminate\Support\Facades\DB;

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
		$filiais = Filial::where('empresa_id', $this->empresa_id)->get();
      
		return view('dfe/index')
		->with('docs', $arrayDocs)
        ->with('filiais', $filiais)
        ->with('status_importacao', 'todos') // <--- ADICIONE ESTA LINHA
        ->with('fornecedor', '')             // Adicione também para o fornecedor
        ->with('nNf', '')                    // Adicione também para a nota
        ->with('tipo', '--')
        ->with('filial_id', '')
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
    $status_importacao = $request->status_importacao;
    $fornecedor = $request->fornecedor;
    $nNf = $request->nNf;

    $config = ConfigNota::where('empresa_id', $this->empresa_id)->first();

    if($config == null){
        session()->flash('mensagem_sucesso', 'Configure o Emitente');
        return redirect('configNF');
    }

    $certificado = Certificado::where('empresa_id', $this->empresa_id)->first();
    if($certificado == null){
        session()->flash('mensagem_erro', 'Configure o Certificado');
        return redirect('configNF');
    }

    // Formata as datas
    $dIni = \Carbon\Carbon::parse(str_replace("/", "-", $dataInicial))->format('Y-m-d 00:00:00');
    $dFim = \Carbon\Carbon::parse(str_replace("/", "-", $dataFinal))->format('Y-m-d 23:59:59');

    // 1. INÍCIO DA QUERY
    $query = ManifestaDfe::where('empresa_id', $this->empresa_id)
        ->whereBetween('data_emissao', [$dIni, $dFim]);

    // 2. FILTRO DE MATRIZ / FILIAL (Movido para antes do ->get())
    if($filial_id == 'matriz'){
        $query->whereNull('filial_id');
    } elseif($filial_id > 0) {
        $query->where('filial_id', $filial_id);
    }

    // 3. FILTRO POR NOME FORNECEDOR
    if($fornecedor){
        $query->where('nome', 'LIKE', "%$fornecedor%");
    }

    // 4. FILTRO POR NÚMERO DA NOTA
    if($nNf){
        $query->where('nNf', $nNf);
    }
      
    // 5. FILTRO DE TIPO
    if($tipo != '--' && $tipo != 'TODOS' && $tipo != null){
        $query->where('tipo', $tipo);
    }

    // 6. FILTRO DE IMPORTAÇÃO
    if($status_importacao == 'importadas'){
        $query->where('compra_id', '>', 0);
    } else if($status_importacao == 'pendentes'){
        $query->where(function($q) {
            $q->where('compra_id', 0)->orWhereNull('compra_id');
        });
    }

    // 7. EXECUÇÃO DA BUSCA (Isso deve ser o último passo da query)
    $docs = $query->orderBy('id', 'desc')->get();

    // 8. BUSCA AS FILIAIS PARA O SELECT DA TELA (Evita erro de variável indefinida)
    $filiais = \App\Models\Filial::where('empresa_id', $this->empresa_id)->get();

    return view('dfe/index')
        ->with('docs', $docs)
        ->with('dfeJS', $docs)
        ->with('filiais', $filiais)
        ->with('filial_id', $filial_id)
        ->with('fornecedor', $fornecedor)
        ->with('nNf', $nNf)
        ->with('busca_automatica', $config->busca_documento_automatico)
        ->with('data_final', $dataFinal)
        ->with('data_inicial', $dataInicial)
        ->with('tipo', $tipo) 
        ->with('status_importacao', $status_importacao)
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
			"schemes" => config('fiscal.default_schemes'),
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
			"schemes" => config('fiscal.default_schemes'),
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

		try {
            // 🛡️ ESCUDO DE PROTEÇÃO: Verifica se a Sefaz realmente devolveu a estrutura correta
            if (!isset($res['retEvento']['infEvento']['cStat'])) {
                
                // Tenta buscar um motivo genérico caso a Sefaz tenha devolvido outro padrão de erro
                $motivoSefaz = $res['xMotivo'] ?? $res['motivo'] ?? 'Resposta inesperada ou Sefaz temporariamente indisponível.';
                
                session()->flash('mensagem_erro', "Falha de comunicação com a Sefaz ao manifestar a nota: " . $motivoSefaz);
                return redirect('/dfe');
            }

            $cStat = $res['retEvento']['infEvento']['cStat'];

            // Se for 135 (Sucesso) ou 573 (Evento em Duplicidade)
            if($cStat == '135' || $cStat == '573'){ 

                $manifesto = ManifestaDfe::
                where('empresa_id', $this->empresa_id)
                ->where('chave', $request->chave)
                ->first();

                // Só atualiza a sequência se for sucesso novo (135). Se for duplicidade, mantém a que já existe.
                if($cStat == '135'){
                    $manifesto->sequencia_evento = $manifestaAnterior != null ? ($manifestaAnterior->sequencia_evento + 1) : 1;
                }

                $manifesto->tipo = $evento;
                $manifesto->save();

                $msg = $cStat == '573' ? "Nota já manifestada anteriormente (Duplicidade)" : $res['retEvento']['infEvento']['xMotivo'];
                session()->flash('mensagem_sucesso', $msg . ": " . $request->chave);

            } else {
                $erro = "[" . $cStat . "] " . $res['retEvento']['infEvento']['xMotivo'];
                session()->flash('mensagem_erro', $erro . " - Chave: ". $request->chave);
            }

            return redirect('/dfe');

        } catch(\Exception $e) {
            // 🛑 TRATAMENTO DE ERRO CORRETO: Em vez de quebrar a tela com echo, devolve a mensagem bonitinha no sistema
            session()->flash('mensagem_erro', 'Erro interno ao processar a manifestação: ' . $e->getMessage());
            return redirect('/dfe');
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
			"schemes" => config('fiscal.default_schemes'),
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
                    $forn = Fornecedor::find($fornecedor['id']);
					$itens = $this->getItensDaNFe($nfe, $fornecedor['id']);
					$infos = $this->getInfosDaNFe($nfe);
					$fatura = $this->getFaturaDaNFe($nfe);
			// echo "<pre>";
			// print_r($fatura);
			// echo "</pre>";

					
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
                  
                   	$veiculos = Veiculo::where('empresa_id', $this->empresa_id)->where('ativo', 1)->orderBy('placa')->get();
					$categoriasConta = CategoriaConta::where('empresa_id', $this->empresa_id)->get();

					return view('dfe/view')
                    ->with('veiculos', $veiculos)                   // <-- Envia os veículos
    				->with('categoriasConta', $categoriasConta)
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
			"schemes" => config('fiscal.default_schemes'),
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
        $emitente = $xml->NFe->infNFe->emit;

        $cnpj = (string)$emitente->CNPJ;
        $cpf = (string)$emitente->CPF;

        // 1. Formata o documento automaticamente
        $documento = '';
        if($cnpj){
            $documento = substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2);
        } else if($cpf){
            $documento = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        }

        // 🛡️ BUSCA A CIDADE NO BANCO PELO NOME DO XML 🛡️
        $nomeCidadeXml = (string)$emitente->enderEmit->xMun;
        $cidadeModel = \App\Models\Cidade::where('nome', 'LIKE', "%$nomeCidadeXml%")->first();
        $cidade_id = $cidadeModel ? $cidadeModel->id : 1; // Se não achar a cidade, amarra no ID 1 (Geral) para não quebrar.

        // 2. Monta o array com as informações e ADICIONA O CIDADE_ID
        $fornecedor = [
            'razaoSocial' => (string)$emitente->xNome,
            'nomeFantasia' => (string)$emitente->xFant,
            'cnpj' => $cnpj,
            'cpf' => $cpf,
            'ie' => (string)$emitente->IE,
            'logradouro' => (string)$emitente->enderEmit->xLgr,
            'numero' => (string)$emitente->enderEmit->nro,
            'bairro' => (string)$emitente->enderEmit->xBairro,
            'cep' => (string)$emitente->enderEmit->CEP,
            'fone' => (string)$emitente->enderEmit->fone,
            'cidade' => $nomeCidadeXml,
            'uf' => (string)$emitente->enderEmit->UF,
            'cidade_id' => $cidade_id, // 🔥 A CHAVE QUE FALTAVA ESTÁ AQUI! 🔥
            'novo_cadastrado' => false,
            'id' => 0
        ];

        // 3. Procura no banco se o Fornecedor existe
        $fornecedorEncontrado = Fornecedor::where('empresa_id', $this->empresa_id)
            ->where(function($q) use ($documento, $cnpj, $cpf){
                $q->where('cpf_cnpj', $documento)
                  ->orWhere('cpf_cnpj', $cnpj ?: $cpf);
            })->first();

        // 4. Lógica de Cadastro Automático
        if($fornecedorEncontrado){
            $fornecedor['novo_cadastrado'] = false;
            $fornecedor['id'] = $fornecedorEncontrado->id;
        }else{
            $fornecedor['novo_cadastrado'] = true;
            
            // Verifica se a função cadastrarFornecedor existe antes de tentar cadastrar
            if(method_exists($this, 'cadastrarFornecedor')) {
                $fornecedor['id'] = $this->cadastrarFornecedor($fornecedor); 
            }
        }

        return $fornecedor;
    }
  
	private function getItensDaNFe($xml, $fornecedor_id = null){
        $itens = [];
        foreach($xml->NFe->infNFe->det as $item) {

            $produto = Produto::verificaCadastrado($item->prod->cEAN, $item->prod->xProd, $item->prod->cProd, $fornecedor_id);
            $produtoNovo = !$produto ? true : false;

            $tp = null;
            $vVenda = 0;

            if($produto != null){
                $tp = ItemDfe::where('produto_id', $produto->id)
                    ->where('numero_nfe', $xml->NFe->infNFe->ide->nNF)
                    ->where('empresa_id', $this->empresa_id)
                    ->first();

                $vVenda = $item->prod->vUnCom + (($item->prod->vUnCom*$produto->percentual_lucro)/100);
            }

            // --- REGRA FISCAL DE/PARA: CFOP ---
            $cfop_xml = (string) $item->prod->CFOP;
            $cfop_entrada = $cfop_xml;
            
            if ($cfop_xml == '5656') {
                $cfop_entrada = '1653';
            } elseif ($cfop_xml == '6656') {
                $cfop_entrada = '2653';
            } else {
                if(substr($cfop_xml, 0, 1) == '5'){
                    $cfop_entrada = '1' . substr($cfop_xml, 1);
                } else if(substr($cfop_xml, 0, 1) == '6'){
                    $cfop_entrada = '2' . substr($cfop_xml, 1);
                }
            }

            // --- EXTRAÇÃO DE IMPOSTOS COM ORIGEM (3 DÍGITOS) ---
            $cst_icms = '';
            if(isset($item->imposto->ICMS)){
                $icms_arr = (array)$item->imposto->ICMS;
                $icms_key = array_key_first($icms_arr); 
                if($icms_key){
                    // 1º: Pega a Origem (0, 1, 2...)
                    $origem = isset($item->imposto->ICMS->$icms_key->orig) ? (string)$item->imposto->ICMS->$icms_key->orig : '';
                    
                    // 2º: Junta Origem + CST (ex: 0 + 60 = 060)
                    if(isset($item->imposto->ICMS->$icms_key->CST)) {
                        $cst_icms = $origem . (string) $item->imposto->ICMS->$icms_key->CST;
                    } elseif(isset($item->imposto->ICMS->$icms_key->CSOSN)) {
                        $cst_icms = $origem . (string) $item->imposto->ICMS->$icms_key->CSOSN;
                    }
                }
            }

            $cst_pis = '';
            if(isset($item->imposto->PIS->PISAliq->CST)) $cst_pis = (string) $item->imposto->PIS->PISAliq->CST;
            else if(isset($item->imposto->PIS->PISOutr->CST)) $cst_pis = (string) $item->imposto->PIS->PISOutr->CST;
            else if(isset($item->imposto->PIS->PISNT->CST)) $cst_pis = (string) $item->imposto->PIS->PISNT->CST;

            $cst_cofins = '';
            if(isset($item->imposto->COFINS->COFINSAliq->CST)) $cst_cofins = (string) $item->imposto->COFINS->COFINSAliq->CST;
            else if(isset($item->imposto->COFINS->COFINSOutr->CST)) $cst_cofins = (string) $item->imposto->COFINS->COFINSOutr->CST;
            else if(isset($item->imposto->COFINS->COFINSNT->CST)) $cst_cofins = (string) $item->imposto->COFINS->COFINSNT->CST;

            $cst_pis_entrada = in_array($cst_pis, ['01', '02', '03', '04', '05']) ? '50' : $cst_pis;
            $cst_cofins_entrada = in_array($cst_cofins, ['01', '02', '03', '04', '05']) ? '50' : $cst_cofins;

            $nomeProduto = str_replace("'", "", $item->prod->xProd);
            if($produto != null && $nomeProduto != $produto->nome) $nomeProduto .= " ($produto->nome)";

            $cod = str_replace([" ", ".", "(", ")"], ["", "_", "", ""], $item->prod->cProd);

            $itemArr = [
                'codigo' => $cod,
                'xProd' => $nomeProduto,
                'NCM' => $item->prod->NCM,
                'CFOP' => $cfop_entrada,         
                'CST_ICMS' => $cst_icms,         
                'CST_PIS' => $cst_pis_entrada,   
                'CST_COFINS' => $cst_cofins_entrada, 
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
            array_push($itens, $itemArr);
        }
        return $itens;
    }
  
	private function getInfosDaNFe($xml){
        $chave = substr($xml->NFe->infNFe->attributes()->Id, 3, 44);
        $vFrete = number_format((double) $xml->NFe->infNFe->total->ICMSTot->vFrete, 2, ",", ".");
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

    // AQUI COMEÇA A FUNÇÃO QUE ESTAVA FALTANDO
    private function getFaturaDaNFe($xml){
        // Se a nota TEM duplicatas no XML (Padrão)
        if (!empty($xml->NFe->infNFe->cobr->dup)) {
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
        // PLANO B: Se a nota NÃO TEM duplicatas
        else {
            $vNF = number_format((double) $xml->NFe->infNFe->total->ICMSTot->vNF, 2, ",", ".");
            $dataEmissao = explode('T', (string) $xml->NFe->infNFe->ide->dhEmi)[0]; 
            $dataEmissao = explode('-', $dataEmissao);
            $dataEmissaoFormatada = $dataEmissao[2]."/".$dataEmissao[1]."/".$dataEmissao[0];

            return [
                [
                    'numero' => '001',
                    'vencimento' => $dataEmissaoFormatada,
                    'valor_parcela' => $vNF,
                    'referencia' => $xml->NFe->infNFe->ide->nNF . "/1"
                ]
            ];
        }
    } // Aqui fecha a função fatura
  

	private function verificaFornecedor($doc){
    $forn = Fornecedor::verificaCadastrado($this->formataDocumento($doc));
    return $forn;
	}

	private function cadastrarFornecedor($fornecedor){
    $documento = $fornecedor['cnpj'] ? $fornecedor['cnpj'] : $fornecedor['cpf'];

    $result = Fornecedor::create([
        'razao_social' => $fornecedor['razaoSocial'],
        'nome_fantasia' => $fornecedor['nomeFantasia'],
        'rua' => $fornecedor['logradouro'],
        'numero' => $fornecedor['numero'],
        'bairro' => $fornecedor['bairro'],
        'cep' => $this->formataCep($fornecedor['cep']),
        'cpf_cnpj' => $this->formataDocumento($documento), // <-- Usa a formatação correta
        'ie_rg' => $fornecedor['ie'] ? $fornecedor['ie'] : '*',
        'celular' => '*',
        'telefone' => $this->formataTelefone($fornecedor['fone']),
        'email' => '*',
        'cidade_id' => $fornecedor['cidade_id'],
        'empresa_id' => $this->empresa_id
    ]);
    return $result->id;
	}

	private function formataDocumento($doc){
    $doc = preg_replace('/[^0-9]/', '', $doc); // Limpa tudo que não for número
    if(strlen($doc) == 14){
        return substr($doc, 0, 2) . "." . substr($doc, 2, 3) . "." . substr($doc, 5, 3) . "/" . substr($doc, 8, 4) . "-" . substr($doc, 12, 2);
    } else if (strlen($doc) == 11){
        return substr($doc, 0, 3) . "." . substr($doc, 3, 3) . "." . substr($doc, 6, 3) . "-" . substr($doc, 9, 2);
    }
    return $doc;
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
    $chave = $request->chave;

    $manifesto = ManifestaDfe::where('empresa_id', $this->empresa_id)
        ->where('chave', $chave)->first();

    // 1. Trava de segurança corrigida
    if($manifesto->fatura_salva){
        session()->flash('mensagem_erro', 'As parcelas desta nota já foram lançadas!');
        return redirect()->back();
    }

    $fatura = json_decode($request->fatura);
    
    // Pega o ID da filial se existir na sessão
    $filial_id = session('filial_id') ?? null;

    foreach($fatura as $fat){
    // 1. Pegamos o valor com segurança
    $vBruto = $fat->valor_parcela ?? $fat->valor ?? '0';
    $vTexto = (string)$vBruto;

    $valor = str_replace(".", "", $vTexto);
    $valor = str_replace(",", ".", $valor);

    // 2. Criamos a conta usando APENAS a variável $fat
    ContaPagar::create([
        'compra_id' => ($manifesto->compra_id > 0) ? $manifesto->compra_id : NULL,
        'data_vencimento' => \Carbon\Carbon::parse(str_replace("/", "-", (string)$fat->vencimento))->format('Y-m-d'),
        'data_emissao' => $manifesto->data_emissao,
        'valor_integral' => $valor,
        'valor_pago' => 0,
        'referencia' => "Parcela " . ($fat->numero ?? '') . " NF: " . ($request->nNf ?? $manifesto->nNf),
        'categoria_id' => $request->categoria_id ?? 1,
        'status' => false,
        'empresa_id' => $this->empresa_id,
        'fornecedor_id' => $request->fornecedor,
        'veiculo_id' => $request->veiculo_id,
        'usuario_id' => get_id_user(),
        'filial_id' => session('filial_id') ?? null
    ]);
}

    // Marca como salvo para não duplicar
    $manifesto->fatura_salva = true;
    $manifesto->save();

    session()->flash('mensagem_sucesso', 'Fatura lançada com sucesso!');
    return redirect()->back();
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
				"schemes" => config('fiscal.default_schemes'),
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
				"schemes" => config('fiscal.default_schemes'),
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
          $notaExistente = ManifestaDfe::where('empresa_id', $this->empresa_id)
              ->where('chave', $d['chave'])
              ->first();

          // TRAVA: Impede que a busca manual "resete" o estado da nota para --
          if (!$notaExistente || $notaExistente->tipo == 0) {
              if($d['valor'] > 0 && $d['nome']){
                  $d['filial_id'] = $local > 0 ? $local : null;
                  ManifestaDfe::updateOrCreate(
                      ['chave' => $d['chave'], 'empresa_id' => $this->empresa_id],
                      $d
                  );
                  array_push($novos, $d);
              }
          }
      }

            ManifestoDia::create([
                'empresa_id' => $this->empresa_id
            ]);
            return response()->json($novos, 200);

        } else {
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

  
  
	public function salvar(Request $request) {
        try {
            \DB::beginTransaction();

            $dfe = ManifestaDfe::find($request->dfe_id);
            $chave = $dfe->chave;

            // Verifica se a nota já foi importada
            $compraExistente = Compra::where('empresa_id', $this->empresa_id)
                ->where('chave', $chave)
                ->first();

            if ($compraExistente) {
                session()->flash('mensagem_erro', 'Esta nota já consta no sistema!');
                return redirect()->back();
            }

            // --- CRIAÇÃO DA COMPRA ---
            $result = Compra::create([
                'fornecedor_id' => $request->fornecedor,
                'usuario_id' => get_id_user(),
                'nf' => $request->nNf,
                'data_emissao' => substr($dfe->data_emissao, 0, 10),
                'valor' => (float) ($request->vProd ?? $dfe->valor), 
                'desconto' => (float) str_replace(',', '.', (string)($request->vDesc ?? 0)), 
                'estado' => 'IMPORTADO',
                'chave' => $chave,
                'xml_path' => $chave . '.xml',
                'empresa_id' => $this->empresa_id,
                'veiculo_id' => $request->veiculo_id,
                'categoria_conta_id' => $request->categoria_id,
                'filial_id' => session('filial_id') ?? null
            ]);

            $dfe->compra_id = $result->id;
            $dfe->save();

            // --- ABRINDO O XML PARA PINÇAR OS IMPOSTOS REAIS ---
            $xmlData = safe_file_get_contents(public_path('xml_dfe/') . $chave . '.xml');
            $nfe = simplexml_load_string($xmlData);
            $xmlItens = $nfe->NFe->infNFe->det;

            // --- GRAVAÇÃO DOS ITENS E ESTOQUE ---
            $itens = json_decode($request->itens);
            foreach($itens as $index => $i){
                
                // VACINA: Força qualquer objeto (stdClass/SimpleXML) a virar Texto Limpo
                $getStr = function($campo) {
                    if (is_object($campo)) {
                        $arr = (array)$campo;
                        return (string) reset($arr);
                    }
                    return (string) $campo;
                };

                // Extraindo dados com segurança absoluta
                $quantidade = (float) $getStr($i->qCom ?? 0);
                $valorUnitario = (float) $getStr($i->vUnCom ?? 0);
                $uComXML = $getStr($i->uCom ?? null);
                $codigoItem = $getStr($i->codigo ?? '');
                $cfopBase = $getStr($i->CFOP ?? '');
                $produtoId = (int) $getStr($i->produtoId ?? 0);

                // Captura a tag do produto exato no XML
                $xmlItem = $xmlItens[$index] ?? null;

                // --- VARIÁVEIS DE IMPOSTOS SPED ---
                $cst_icms_xml = '';
                $vbc_icms = 0; $p_icms = 0; $v_icms = 0;
                $cst_ipi = null; $vbc_ipi = 0; $p_ipi = 0; $v_ipi = 0;
                $vbc_pis = 0; $p_pis = 0; $v_pis = 0;
                $vbc_cofins = 0; $p_cofins = 0; $v_cofins = 0;
                
                if($xmlItem && isset($xmlItem->imposto)){
                    // Extração de ICMS com Origem (3 Dígitos)
                    if(isset($xmlItem->imposto->ICMS)){
                        $icms_arr = (array)$xmlItem->imposto->ICMS;
                        $icms_key = array_key_first($icms_arr);
                        if($icms_key){
                            // Pega Origem
                            $origem = isset($xmlItem->imposto->ICMS->$icms_key->orig) ? (string)$xmlItem->imposto->ICMS->$icms_key->orig : '';
                            
                            // Junta Origem + CST
                            if(isset($xmlItem->imposto->ICMS->$icms_key->CST)){
                                $cst_icms_xml = $origem . (string)$xmlItem->imposto->ICMS->$icms_key->CST;
                            } elseif(isset($xmlItem->imposto->ICMS->$icms_key->CSOSN)){
                                $cst_icms_xml = $origem . (string)$xmlItem->imposto->ICMS->$icms_key->CSOSN;
                            }

                            if(isset($xmlItem->imposto->ICMS->$icms_key->vBC)) $vbc_icms = (float) $xmlItem->imposto->ICMS->$icms_key->vBC;
                            if(isset($xmlItem->imposto->ICMS->$icms_key->pICMS)) $p_icms = (float) $xmlItem->imposto->ICMS->$icms_key->pICMS;
                            if(isset($xmlItem->imposto->ICMS->$icms_key->vICMS)) $v_icms = (float) $xmlItem->imposto->ICMS->$icms_key->vICMS;
                        }
                    }

                    // Extração de IPI
                    if(isset($xmlItem->imposto->IPI)){
                        if(isset($xmlItem->imposto->IPI->IPITrib)){
                            if(isset($xmlItem->imposto->IPI->IPITrib->CST)) $cst_ipi = (string) $xmlItem->imposto->IPI->IPITrib->CST;
                            if(isset($xmlItem->imposto->IPI->IPITrib->vBC)) $vbc_ipi = (float) $xmlItem->imposto->IPI->IPITrib->vBC;
                            if(isset($xmlItem->imposto->IPI->IPITrib->pIPI)) $p_ipi = (float) $xmlItem->imposto->IPI->IPITrib->pIPI;
                            if(isset($xmlItem->imposto->IPI->IPITrib->vIPI)) $v_ipi = (float) $xmlItem->imposto->IPI->IPITrib->vIPI;
                        } elseif(isset($xmlItem->imposto->IPI->IPINT)){
                            if(isset($xmlItem->imposto->IPI->IPINT->CST)) $cst_ipi = (string) $xmlItem->imposto->IPI->IPINT->CST;
                        }
                    }

                    // Extração de PIS
                    if(isset($xmlItem->imposto->PIS)){
                        $pis_node = isset($xmlItem->imposto->PIS->PISAliq) ? $xmlItem->imposto->PIS->PISAliq : (isset($xmlItem->imposto->PIS->PISOutr) ? $xmlItem->imposto->PIS->PISOutr : null);
                        if($pis_node){
                            if(isset($pis_node->vBC)) $vbc_pis = (float) $pis_node->vBC;
                            if(isset($pis_node->pPIS)) $p_pis = (float) $pis_node->pPIS;
                            if(isset($pis_node->vPIS)) $v_pis = (float) $pis_node->vPIS;
                        }
                    }

                    // Extração de COFINS
                    if(isset($xmlItem->imposto->COFINS)){
                        $cofins_node = isset($xmlItem->imposto->COFINS->COFINSAliq) ? $xmlItem->imposto->COFINS->COFINSAliq : (isset($xmlItem->imposto->COFINS->COFINSOutr) ? $xmlItem->imposto->COFINS->COFINSOutr : null);
                        if($cofins_node){
                            if(isset($cofins_node->vBC)) $vbc_cofins = (float) $cofins_node->vBC;
                            if(isset($cofins_node->pCOFINS)) $p_cofins = (float) $cofins_node->pCOFINS;
                            if(isset($cofins_node->vCOFINS)) $v_cofins = (float) $xmlItem->imposto->COFINS->vCOFINS;
                        }
                    }
                }

                // --- TRATAMENTO ESPECÍFICO DE EXCEÇÃO DE CFOP NO SALVAMENTO ---
                $cfopFinal = $request->cfop_entrada[$codigoItem] ?? $cfopBase;
                if ($cfopFinal == '5656' || $cfopFinal == '1656') {
                    $cfopFinal = '1653';
                } elseif ($cfopFinal == '6656' || $cfopFinal == '2656') {
                    $cfopFinal = '2653';
                }

                // --- VALIDAÇÃO FINAL DO CST ---
                $cstInput = $request->cst_icms_entrada[$codigoItem] ?? null;
                $cstFinal = !empty($cstInput) ? $cstInput : $cst_icms_xml;

                ItemCompra::create([
                    'compra_id' => $result->id,
                    'produto_id' => $produtoId,
                    'quantidade' => $quantidade,
                    'valor_unitario' => $valorUnitario,
                    'unidade_compra' => $uComXML,
                    'cfop_entrada' => $cfopFinal,
                    
                    // Tributos ICMS (Agora vai os 3 dígitos garantidos)
                    'cst_icms' => $cstFinal,
                    'vbc_icms' => $vbc_icms,
                    'p_icms' => $p_icms,
                    'v_icms' => $v_icms,

                    // Tributos IPI
                    'cst_ipi' => $cst_ipi,
                    'vbc_ipi' => $vbc_ipi,
                    'p_ipi' => $p_ipi,
                    'v_ipi' => $v_ipi,

                    // Tributos PIS
                    'cst_pis' => $request->cst_pis_entrada[$codigoItem] ?? $getStr($i->CST_PIS ?? null),
                    'vbc_pis' => $vbc_pis,
                    'p_pis' => $p_pis,
                    'v_pis' => $v_pis,

                    // Tributos COFINS
                    'cst_cofins' => $request->cst_cofins_entrada[$codigoItem] ?? $getStr($i->CST_COFINS ?? null),
                    'vbc_cofins' => $vbc_cofins,
                    'p_cofins' => $p_cofins,
                    'v_cofins' => $v_cofins,
                ]);

                // --- MOVIMENTAÇÃO DE ESTOQUE ---
                if ($produtoId > 0) {
                    $produto = \App\Models\Produto::find($produtoId);
                    
                    if ($produto) {
                        $conv = (float)$getStr($i->conversao_unitaria ?? 1);
                        if($conv <= 0) $conv = 1;
                        
                        $quantidadeConvertida = $quantidade * $conv;

                        // 1. Registra no Histórico
                        \App\Models\StockMovement::create([
                            'empresa_id' => $this->empresa_id,
                            'filial_id' => session('filial_id') ?? null,
                            'usuario_id' => get_id_user(),
                            'produto_id' => $produto->id,
                            'contexto' => 'compra',
                            'tipo' => 'entrada',
                            'quantidade' => $quantidadeConvertida,
                            'custo_unitario' => $valorUnitario / $conv,
                            'valor_total' => $quantidade * $valorUnitario,
                            'origem_tipo' => \App\Models\Compra::class,
                            'origem_id' => $result->id,
                            'idempotency_key' => $chave . '_' . $produto->id . '_' . rand(1000, 9999),
                            'movimentado_em' => now(),
                        ]);

                        // 2. Atualiza o custo de compra no cadastro do Produto
                        $produto->valor_compra = $valorUnitario / $conv;
                        $produto->save();

                        // 3. Atualiza fisicamente na tabela ESTOQUES
                        $estoqueLocal = \App\Models\Estoque::where('produto_id', $produto->id)
                            ->where('empresa_id', $this->empresa_id)
                            ->where('filial_id', session('filial_id') ?? null)
                            ->first();

                        if ($estoqueLocal) {
                            $estoqueLocal->quantidade += $quantidadeConvertida;
                            $estoqueLocal->save();
                        } else {
                            \App\Models\Estoque::create([
                                'produto_id' => $produto->id,
                                'empresa_id' => $this->empresa_id,
                                'filial_id' => session('filial_id') ?? null,
                                'quantidade' => $quantidadeConvertida
                            ]);
                        }
                    }
                }
            }

            // --- GRAVAÇÃO DO FINANCEIRO ---
            if ($request->has('fatura')) {
                $faturas = json_decode($request->fatura);
                if ($faturas) {
                    foreach ($faturas as $fat) {
                        // Garantindo que os valores do financeiro também não sejam lidos como stdClass
                        $vBruto = is_object($fat->valor_parcela ?? null) ? (string) collect($fat->valor_parcela)->first() : (string) ($fat->valor_parcela ?? $fat->valor ?? '0');
                        $numFat = is_object($fat->numero ?? null) ? (string) collect($fat->numero)->first() : (string) ($fat->numero ?? '');

                        ContaPagar::create([
                            'compra_id' => $result->id,
                            'data_vencimento' => \Carbon\Carbon::parse(str_replace("/", "-", $fat->vencimento))->format('Y-m-d'),
                            'data_emissao' => $result->data_emissao,
                            'valor_integral' => $this->parseMoeda($vBruto),
                            'valor_pago' => 0,
                            'numero_nota_fiscal' => $result->nf,
                            'referencia' => "Parcela " . $numFat . " NF: " . $result->nf,
                            'categoria_id' => $request->categoria_conta_id ?? $request->categoria_id,
                            'status' => false,
                            'empresa_id' => $this->empresa_id,
                            'fornecedor_id' => $result->fornecedor_id,
                            'veiculo_id' => $request->veiculo_id,
                            'usuario_id' => get_id_user(),
                            'filial_id' => $result->filial_id
                        ]);
                    }
                    $dfe->fatura_salva = true;
                    $dfe->save();
                }
            }

          // --- SALVANDO O XML NA PASTA DE ENTRADA ---
            $caminhoOrigem = public_path('xml_dfe/') . $chave . '.xml';
            $caminhoDestinoDir = public_path('xml_entrada/');
            $caminhoDestino = $caminhoDestinoDir . $chave . '.xml';

            // 1. Garante que a pasta xml_entrada existe (cria se não existir)
            if (!file_exists($caminhoDestinoDir)) {
                mkdir($caminhoDestinoDir, 0755, true);
            }

            // 2. Copia o arquivo da pasta DFe para a pasta Entrada
            if (file_exists($caminhoOrigem)) {
                copy($caminhoOrigem, $caminhoDestino);
                
                // Nota: Se você realmente quiser MOVER (e apagar da pasta dfe), 
                // basta trocar a palavra 'copy' por 'rename' na linha acima.
            }
            // 👆 FIM DO BLOCO ADICIONADO 👆
          
            \DB::commit();
            session()->flash('mensagem_sucesso', 'Compra, Estoque, Financeiro e Impostos importados com sucesso!');
            
            // ALTERADO AQUI: Volta para a tela de listagem de Notas (Manifesto)
            return redirect('/dfe'); 

        } catch (\Exception $e) {
            \DB::rollBack();
            session()->flash('mensagem_erro', 'Erro ao salvar: ' . $e->getMessage());
            return redirect()->back();
        }
    }
  
  
      // Adicione esta função auxiliar no final do DFeController para evitar erros de valor
      private function parseMoeda($valor) {
          $valor = str_replace('.', '', (string)$valor);
          $valor = str_replace(',', '.', $valor);
          return (float)$valor;
      }
  
  
    // FUNÇÃO AUXILIAR
    private function gerarFatura($compra, $request) {
        $fatura = json_decode($request->fatura);

        if($fatura){
            foreach($fatura as $fat){
        // 1. Pegamos o valor bruto
        $vBruto = $fat->valor_parcela ?? $fat->valor ?? '0';

        // 2. O SEGREDO: Forçamos o PHP a entender que isso é um texto (string)
        // Isso evita o erro "Object of class stdClass could not be converted to string"
        $vTexto = (string) (is_object($vBruto) ? collect($vBruto)->first() : $vBruto);

        // 3. Agora sim fazemos o replace com segurança
        $valor = str_replace(".", "", $vTexto);
        $valor = str_replace(",", ".", $valor);

            ContaPagar::create([
                'empresa_id' => $this->empresa_id,
                'fornecedor_id' => $compra->fornecedor_id,
                'valor_integral' => $valor,
                'valor_pago' => 0,
                'data_vencimento' => \Carbon\Carbon::parse(str_replace("/", "-", $f->vencimento))->format('Y-m-d'),
                'data_emissao' => $compra->data_emissao,
                'status' => false,
                'referencia' => "Parcela " . ($f->numero ?? '') . " NF-e: " . $compra->nf,
                'compra_id' => $compra->id,
                'usuario_id' => get_id_user(),
                'veiculo_id' => $request->veiculo_id,
                'filial_id' => session('filial_id') ?? null
            ]);
        }
    }
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
                "schemes" => config('fiscal.default_schemes'),
                "versao" => "4.00",
                "tokenIBPT" => "AAAAAAA",
                "CSC" => $config->csc,
                "CSCid" => $config->csc_id
            ], 55);

            $manifesto = ManifestaDfe::where('empresa_id', $config->empresa_id)
                ->orderBy('nsu', 'desc')->first();

            $nsu = $manifesto == null ? 0 : $manifesto->nsu;
            $docs = $dfe_service->novaConsulta($nsu);

            foreach($docs as $d) {
              // Busca se a nota já existe
              $notaExistente = ManifestaDfe::where('empresa_id', $config->empresa_id)
                  ->where('chave', $d['chave'])
                  ->first();

              // TRAVA: Só atualiza se for nova ou se o tipo atual for 0 (pendente)
              if (!$notaExistente || $notaExistente->tipo == 0) {
                  if($d['valor'] > 0 && $d['nome']){
                      ManifestaDfe::updateOrCreate(
                          ['chave' => $d['chave'], 'empresa_id' => $config->empresa_id],
                          $d
                      );
                      $novos++;
                  }
              }
          }
                
				$cnpj = preg_replace('/[^0-9]/', '', $config->cnpj);

				$dfe_service = new DFeService([
					"atualizacao" => date('Y-m-d h:i:s'),
					"tpAmb" => 1,
					"razaosocial" => $config->razao_social,
					"siglaUF" => $config->UF,
					"cnpj" => $cnpj,
					"schemes" => config('fiscal.default_schemes'),
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

      	$fornecedorXml = $this->getFornecedorXML($nfe);
		$itens = $this->getItensDaNFe($nfe, $fornecedorXml['id']);
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
  
  private function parseDate($date, $plusDay = false){
    if($plusDay == false)
        return date('Y-m-d', strtotime(str_replace('/', '-', $date)));
    else
        return date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $date) . ' +1 day'));
	}
  
}
