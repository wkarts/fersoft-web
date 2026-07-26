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

	public function manifestar(Request $request)
    {
        $dados = $request->validate([
            'chave' => ['required', 'digits:44'],
            'evento' => ['required', 'integer', 'in:1,2,3,4'],
            'justificativa' => ['nullable', 'string', 'min:15', 'max:255'],
        ]);

        $evento = (int) $dados['evento'];
        if (in_array($evento, [3, 4], true) && trim((string) ($dados['justificativa'] ?? '')) === '') {
            return redirect()->back()->with('mensagem_erro', 'Informe uma justificativa para este tipo de manifestação.');
        }

        try {
            $manifesto = ManifestaDfe::query()
                ->where('empresa_id', $this->empresa_id)
                ->where('chave', $dados['chave'])
                ->firstOrFail();

            $configMatriz = ConfigNota::where('empresa_id', $this->empresa_id)->firstOrFail();
            $filial = null;

            if ($manifesto->filial_id) {
                $filial = Filial::query()
                    ->where('empresa_id', $this->empresa_id)
                    ->findOrFail($manifesto->filial_id);
            }

            $emitente = $filial ?: $configMatriz;
            $cnpj = preg_replace('/\D+/', '', (string) ($emitente->cnpj ?? $emitente->cpf_cnpj ?? ''));

            if (strlen($cnpj) !== 14) {
                throw new \RuntimeException('O CNPJ do emitente vinculado ao documento está inválido.');
            }

            $dfeService = new DFeService([
                'atualizacao' => now()->format('Y-m-d H:i:s'),
                'tpAmb' => 1,
                'razaosocial' => (string) ($emitente->razao_social ?? $configMatriz->razao_social),
                'siglaUF' => (string) ($emitente->UF ?? $configMatriz->UF),
                'cnpj' => $cnpj,
                'schemes' => config('fiscal.default_schemes'),
                'versao' => '4.00',
                'tokenIBPT' => 'AAAAAAA',
                'CSC' => (string) ($emitente->csc ?? $configMatriz->csc),
                'CSCid' => (string) ($emitente->csc_id ?? $configMatriz->csc_id),
                'is_filial' => $filial?->id,
            ], 55, $this->empresa_id);

            $sequenciaAtual = max(0, (int) ($manifesto->sequencia_evento ?? 0));
            $numeroEvento = $sequenciaAtual + 1;

            // Mantém compatibilidade com a regra antiga: mudança de tipo reutiliza a sequência atual.
            if ($sequenciaAtual > 0 && (int) ($manifesto->tipo ?? 0) !== $evento) {
                $numeroEvento = $sequenciaAtual;
            }
            $numeroEvento = max(1, $numeroEvento);

            $resposta = match ($evento) {
                1 => $dfeService->manifesta($dados['chave'], $numeroEvento),
                2 => $dfeService->confirmacao($dados['chave'], $numeroEvento),
                3 => $dfeService->desconhecimento($dados['chave'], $numeroEvento, trim((string) $dados['justificativa'])),
                4 => $dfeService->operacaoNaoRealizada($dados['chave'], $numeroEvento, trim((string) $dados['justificativa'])),
            };

            $infEvento = data_get($resposta, 'retEvento.infEvento');
            if (!is_array($infEvento) || !isset($infEvento['cStat'])) {
                $motivo = data_get($resposta, 'xMotivo')
                    ?? data_get($resposta, 'motivo')
                    ?? 'Resposta inesperada ou SEFAZ temporariamente indisponível.';

                throw new \RuntimeException('Falha de comunicação com a SEFAZ: ' . $motivo);
            }

            $cStat = (string) $infEvento['cStat'];
            $xMotivo = (string) ($infEvento['xMotivo'] ?? 'Manifestação processada.');

            if (in_array($cStat, ['135', '573'], true)) {
                if ($cStat === '135') {
                    $manifesto->sequencia_evento = $numeroEvento;
                }
                $manifesto->tipo = $evento;
                $manifesto->save();

                $mensagem = $cStat === '573'
                    ? 'A manifestação já havia sido registrada na SEFAZ.'
                    : $xMotivo;

                return redirect()->back()->with('mensagem_sucesso', $mensagem . ' Chave: ' . $dados['chave']);
            }

            return redirect()->back()->with(
                'mensagem_erro',
                sprintf('[%s] %s - Chave: %s', $cStat, $xMotivo, $dados['chave'])
            );
        } catch (\Throwable $e) {
            \Log::error('Erro ao manifestar DF-e', [
                'empresa_id' => $this->empresa_id,
                'chave' => $dados['chave'] ?? $request->chave,
                'evento' => $dados['evento'] ?? $request->evento,
                'erro' => $e->getMessage(),
            ]);

            return redirect()->back()->with(
                'mensagem_erro',
                'Não foi possível processar a manifestação: ' . $e->getMessage()
            );
        }
    }

	private function verificaAnterior($chave){
		return ManifestaDfe::
		where('empresa_id', $this->empresa_id)
		->where('chave', $chave)->first();
	}

	public function download($chave){
        $dfe = ManifestaDfe::where('empresa_id', $this->empresa_id)
            ->where('chave', $chave)
            ->firstOrFail();
        $configMatriz = ConfigNota::where('empresa_id', $this->empresa_id)->firstOrFail();
        $filial = $dfe->filial_id
            ? Filial::where('empresa_id', $this->empresa_id)->find($dfe->filial_id)
            : null;
        $configDfe = $filial ?: $configMatriz;
        $cnpj = preg_replace('/[^0-9]/', '', (string) ($configDfe->cnpj ?? $configDfe->cpf_cnpj ?? ''));

		$dfe_service = new DFeService([
			"atualizacao" => now()->format('Y-m-d H:i:s'),
			"tpAmb" => 1,
			"razaosocial" => $configDfe->razao_social ?? $configMatriz->razao_social,
			"siglaUF" => $configDfe->UF ?? $configMatriz->UF,
			"cnpj" => $cnpj,
			"schemes" => config('fiscal.default_schemes'),
			"versao" => "4.00",
			"tokenIBPT" => "AAAAAAA",
			"CSC" => $configMatriz->csc,
			"CSCid" => $configMatriz->csc_id,
            "is_filial" => $filial?->id,
		], 55, $this->empresa_id);
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
                    $contasEmpresa = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)
                        ->where('status', 1)
                        ->orderBy('nome')
                        ->get();

					return view('dfe/view')
                    ->with('veiculos', $veiculos)                   // <-- Envia os veículos
    				->with('categoriasConta', $categoriasConta)
                    ->with('contasEmpresa', $contasEmpresa)
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
		}catch(\Throwable $e){
            \Log::error('Erro ao baixar/visualizar DF-e', [
                'empresa_id' => $this->empresa_id,
                'chave' => $chave,
                'erro' => $e->getMessage(),
            ]);
            return redirect('/dfe')->with('mensagem_erro', 'Erro ao baixar o documento: ' . $e->getMessage());
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

            $codigoItem = str_replace([" ", ".", "(", ")"], ["", "_", "", ""], $item->prod->cProd);
            $produto = null;

            // 1. PRIMEIRA BUSCA: Tabela de Relacionamento de Fornecedores
            if ($fornecedor_id > 0) {
                $vinculoFornecedor = \DB::table('produto_fornecedors')
                    ->where('fornecedor_id', $fornecedor_id)
                    ->where('codigo_fornecedor', $codigoItem)
                    ->first();

                if ($vinculoFornecedor) {
                    $produto = \App\Models\Produto::find($vinculoFornecedor->produto_id);
                }
            }

            // 2. SEGUNDA BUSCA (Suplente): Código de barras global CORRIGIDO
            if (!$produto && !empty($item->prod->cEAN) && $item->prod->cEAN != 'SEM GTIN') {
                $produto = \App\Models\Produto::where('empresa_id', $this->empresa_id)
                    ->where('codBarras', $item->prod->cEAN) // 🟢 Alterado aqui para 'codBarras'
                    ->first();
            }

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
  
	private function getInfosDaNFe($xml)
    {
        $chave = substr((string) $xml->NFe->infNFe->attributes()->Id, 3, 44);
        $vFrete = number_format((float) ($xml->NFe->infNFe->total->ICMSTot->vFrete ?? 0), 2, ',', '.');
        $vDesc = number_format((float) ($xml->NFe->infNFe->total->ICMSTot->vDesc ?? 0), 2, ',', '.');

        return [
            'chave' => $chave,
            'vProd' => (float) ($xml->NFe->infNFe->total->ICMSTot->vProd ?? 0),
            'vNF' => (float) ($xml->NFe->infNFe->total->ICMSTot->vNF ?? 0),
            'indPag' => (string) ($xml->NFe->infNFe->ide->indPag ?? ''),
            'nNf' => (string) ($xml->NFe->infNFe->ide->nNF ?? ''),
            'vFrete' => $vFrete,
            'vDesc' => $vDesc,
            'infCpl' => (string) ($xml->NFe->infNFe->infAdic->infCpl ?? ''),
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

	public function getDocumentosNovos(Request $request)
    {
        try {
            $local = (int) $request->input('local', 0);
            $configMatriz = ConfigNota::where('empresa_id', $this->empresa_id)->firstOrFail();
            $filial = null;

            if ($local > 0) {
                $filial = Filial::where('empresa_id', $this->empresa_id)->findOrFail($local);
            }

            $configDfe = $filial ?: $configMatriz;
            $cnpjDfe = preg_replace('/[^0-9]/', '', (string) ($configDfe->cnpj ?? $configDfe->cpf_cnpj ?? ''));

            if ($cnpjDfe === '') {
                return response()->json(['message' => 'CNPJ não configurado para a unidade selecionada.'], 422);
            }

            $dfeService = new DFeService([
                'atualizacao' => now()->format('Y-m-d H:i:s'),
                'tpAmb' => 1,
                'razaosocial' => $configDfe->razao_social ?? $configMatriz->razao_social,
                'siglaUF' => $configDfe->UF ?? $configMatriz->UF,
                'cnpj' => $cnpjDfe,
                'schemes' => config('fiscal.default_schemes'),
                'versao' => '4.00',
                'tokenIBPT' => 'AAAAAAA',
                'CSC' => $configMatriz->csc,
                'CSCid' => $configMatriz->csc_id,
                'is_filial' => $filial?->id,
            ], 55, $this->empresa_id);

            $ultimoManifesto = ManifestaDfe::where('empresa_id', $this->empresa_id)
                ->when($filial, fn ($query) => $query->where('filial_id', $filial->id))
                ->when(!$filial, fn ($query) => $query->whereNull('filial_id'))
                ->orderByDesc('nsu')
                ->first();

            $docs = $dfeService->novaConsulta((int) ($ultimoManifesto->nsu ?? 0), $this->empresa_id);

            if (isset($docs['erro'])) {
                return response()->json($docs, 422);
            }

            if ($docs === null) {
                return response()->json(['message' => 'Nenhum retorno obtido da SEFAZ.'], 422);
            }

            $novos = [];
            foreach ($docs as $documento) {
                if (empty($documento['chave']) || (float) ($documento['valor'] ?? 0) <= 0 || empty($documento['nome'])) {
                    continue;
                }

                $existente = ManifestaDfe::where('empresa_id', $this->empresa_id)
                    ->where('chave', $documento['chave'])
                    ->first();

                // Não sobrescreve manifestação já processada.
                if ($existente && (int) $existente->tipo !== 0) {
                    continue;
                }

                $codigoSituacao = (string) ($documento['cSitNFe'] ?? '1');
                $documento['situacao_sefaz'] = match ($codigoSituacao) {
                    '2' => 'DENEGADA',
                    '3' => 'CANCELADA',
                    default => 'AUTORIZADA',
                };
                $documento['filial_id'] = $filial?->id;
                $documento['empresa_id'] = $this->empresa_id;

                unset($documento['cSitNFe']);

                ManifestaDfe::updateOrCreate(
                    ['chave' => $documento['chave'], 'empresa_id' => $this->empresa_id],
                    $documento
                );

                $novos[] = $documento;
            }

            ManifestoDia::create([
                'empresa_id' => $this->empresa_id,
                'filial_id' => $filial?->id,
            ]);

            return response()->json($novos);
        } catch (\Throwable $e) {
            \Log::error('Erro ao buscar documentos DF-e', [
                'empresa_id' => $this->empresa_id,
                'local' => $request->input('local'),
                'erro' => $e->getMessage(),
            ]);

            return response()->json(['message' => $e->getMessage()], 422);
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

  
  
	public function salvar(Request $request)
    {
        $request->validate([
            'dfe_id' => ['required', 'integer'],
            'fornecedor' => ['required', 'integer'],
            'itens' => ['required'],
            'categoria_id' => ['nullable', 'integer'],
            'categoria_conta_id' => ['nullable', 'integer'],
            'conta_empresa_id' => ['nullable', 'integer'],
        ]);

        try {
            $compra = DB::transaction(function () use ($request) {
                $dfe = ManifestaDfe::query()
                    ->where('empresa_id', $this->empresa_id)
                    ->lockForUpdate()
                    ->findOrFail((int) $request->dfe_id);

                if ((int) ($dfe->compra_id ?? 0) > 0) {
                    throw new \RuntimeException('Este DF-e já está vinculado a uma compra.');
                }

                $chave = preg_replace('/\D+/', '', (string) $dfe->chave);
                if (strlen($chave) !== 44) {
                    throw new \RuntimeException('A chave de acesso do DF-e está inválida.');
                }

                if (Compra::where('empresa_id', $this->empresa_id)->where('chave', $chave)->exists()) {
                    throw new \RuntimeException('Esta nota já consta no sistema.');
                }

                $fornecedor = Fornecedor::query()
                    ->where('empresa_id', $this->empresa_id)
                    ->findOrFail((int) $request->fornecedor);

                $filialId = $this->normalizarFilialDfe($dfe->filial_id ?? $request->filial_id ?? null);
                if ($filialId !== null) {
                    Filial::where('empresa_id', $this->empresa_id)->findOrFail($filialId);
                }

                $categoriaId = (int) ($request->categoria_id ?: $request->categoria_conta_id);
                if ($categoriaId > 0) {
                    CategoriaConta::where('empresa_id', $this->empresa_id)->findOrFail($categoriaId);
                } else {
                    $categoriaId = null;
                }

                $dataEmissao = \Carbon\Carbon::parse((string) $dfe->data_emissao)->toDateString();
                $valorProdutos = $this->parseMoeda($request->vProd ?? $dfe->valor ?? 0);
                $valorNota = $this->parseMoeda($request->vNF ?? $dfe->valor ?? $valorProdutos);

                $compra = Compra::create([
                    'fornecedor_id' => $fornecedor->id,
                    'usuario_id' => get_id_user(),
                    'nf' => (string) ($request->nNf ?? $dfe->nNf),
                    'data_emissao' => $dataEmissao,
                    'data_retroativa' => $dataEmissao,
                    'valor' => $valorNota > 0 ? $valorNota : $valorProdutos,
                    'desconto' => $this->parseMoeda($request->vDesc ?? 0),
                    'estado' => 'IMPORTADO',
                    'xml_importado' => 1,
                    'chave' => $chave,
                    'xml_path' => $chave . '.xml',
                    'empresa_id' => $this->empresa_id,
                    'veiculo_id' => $request->filled('veiculo_id') ? (int) $request->veiculo_id : null,
                    'categoria_conta_id' => $categoriaId,
                    'filial_id' => $filialId,
                    'tipo_pagamento' => $request->input('tipo_pagamento'),
                    'observacao' => trim((string) ($request->infCpl ?? '')),
                ]);

                $xmlPath = public_path('xml_dfe/' . $chave . '.xml');
                if (!is_file($xmlPath)) {
                    throw new \RuntimeException('O XML do DF-e não foi encontrado para importação.');
                }

                $xmlData = safe_file_get_contents($xmlPath);
                $nfe = simplexml_load_string($xmlData);
                if ($nfe === false || !isset($nfe->NFe->infNFe)) {
                    throw new \RuntimeException('O XML do DF-e está inválido ou incompleto.');
                }

                $xmlItens = $nfe->NFe->infNFe->det;
                $itens = is_string($request->itens) ? json_decode($request->itens) : $request->itens;
                if (!is_iterable($itens)) {
                    throw new \RuntimeException('Os itens informados para importação são inválidos.');
                }

                $getStr = static function ($valor): string {
                    if ($valor instanceof \SimpleXMLElement) {
                        return trim((string) $valor);
                    }
                    if (is_object($valor)) {
                        $array = (array) $valor;
                        return trim((string) (reset($array) ?: ''));
                    }
                    return trim((string) $valor);
                };

                foreach ($itens as $index => $item) {
                    $produtoId = (int) $getStr($item->produtoId ?? 0);
                    if ($produtoId <= 0) {
                        throw new \RuntimeException('Todos os itens devem estar vinculados a um produto cadastrado.');
                    }

                    $produto = Produto::query()
                        ->where('empresa_id', $this->empresa_id)
                        ->lockForUpdate()
                        ->findOrFail($produtoId);

                    $quantidade = $this->parseMoeda($getStr($item->qCom ?? 0));
                    $valorUnitario = $this->parseMoeda($getStr($item->vUnCom ?? 0));
                    if ($quantidade <= 0 || $valorUnitario < 0) {
                        throw new \RuntimeException('Quantidade ou valor unitário inválido no item ' . ($index + 1) . '.');
                    }

                    $codigoItem = $getStr($item->codigo ?? '');
                    $cfopBase = preg_replace('/\D+/', '', $getStr($item->CFOP ?? ''));
                    $cfopFinal = preg_replace('/\D+/', '', (string) data_get($request->input('cfop_entrada', []), $codigoItem, $cfopBase));

                    if (in_array($cfopFinal, ['5656', '1656'], true)) {
                        $cfopFinal = '1653';
                    } elseif (in_array($cfopFinal, ['6656', '2656'], true)) {
                        $cfopFinal = '2653';
                    }

                    $xmlItem = $xmlItens[$index] ?? null;
                    $tributos = $this->extrairTributosItemDfe($xmlItem);
                    $cstIcmsInformado = trim((string) data_get($request->input('cst_icms_entrada', []), $codigoItem, ''));
                    if ($cstIcmsInformado !== '') {
                        $tributos['cst_icms'] = $cstIcmsInformado;
                    }

                    $cstPisInformado = trim((string) data_get($request->input('cst_pis_entrada', []), $codigoItem, ''));
                    $cstCofinsInformado = trim((string) data_get($request->input('cst_cofins_entrada', []), $codigoItem, ''));

                    ItemCompra::create(array_merge([
                        'compra_id' => $compra->id,
                        'produto_id' => $produto->id,
                        'quantidade' => $quantidade,
                        'valor_unitario' => $valorUnitario,
                        'unidade_compra' => $getStr($item->uCom ?? null),
                        'cfop_entrada' => $cfopFinal ?: null,
                        'cst_pis' => $cstPisInformado !== '' ? $cstPisInformado : ($tributos['cst_pis'] ?: $getStr($item->CST_PIS ?? null)),
                        'cst_cofins' => $cstCofinsInformado !== '' ? $cstCofinsInformado : ($tributos['cst_cofins'] ?: $getStr($item->CST_COFINS ?? null)),
                    ], $tributos));

                    DB::table('produto_fornecedors')->updateOrInsert(
                        [
                            'empresa_id' => $this->empresa_id,
                            'produto_id' => $produto->id,
                            'fornecedor_id' => $fornecedor->id,
                            'codigo_fornecedor' => $codigoItem,
                        ],
                        [
                            'filial_id' => $filialId,
                            'descricao_fornecedor' => $getStr($item->xProd ?? ''),
                            'codigo_barras_fornecedor' => $getStr($item->codBarras ?? $item->cEAN ?? ''),
                            'usuario_id' => get_id_user(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                    $conversao = $this->parseMoeda($getStr($item->conversao_unitaria ?? 1));
                    $conversao = $conversao > 0 ? $conversao : 1;
                    $quantidadeConvertida = $quantidade * $conversao;
                    $custoConvertido = $conversao > 0 ? $valorUnitario / $conversao : $valorUnitario;
                    $idempotencyKey = sprintf('dfe:%s:item:%d:produto:%d', $chave, $index + 1, $produto->id);

                    $movimento = \App\Models\StockMovement::firstOrCreate(
                        ['idempotency_key' => $idempotencyKey],
                        [
                            'empresa_id' => $this->empresa_id,
                            'filial_id' => $filialId,
                            'usuario_id' => get_id_user(),
                            'produto_id' => $produto->id,
                            'contexto' => 'compra',
                            'tipo' => 'entrada',
                            'quantidade' => $quantidadeConvertida,
                            'custo_unitario' => $custoConvertido,
                            'valor_total' => $quantidade * $valorUnitario,
                            'origem_tipo' => Compra::class,
                            'origem_id' => $compra->id,
                            'movimentado_em' => $dataEmissao,
                            'metadata' => [
                                'origem' => 'dfe',
                                'chave' => $chave,
                                'item' => $index + 1,
                            ],
                        ]
                    );

                    if ($movimento->wasRecentlyCreated) {
                        $estoque = \App\Models\Estoque::query()
                            ->where('empresa_id', $this->empresa_id)
                            ->where('produto_id', $produto->id)
                            ->where(function ($query) use ($filialId) {
                                $filialId === null
                                    ? $query->whereNull('filial_id')
                                    : $query->where('filial_id', $filialId);
                            })
                            ->lockForUpdate()
                            ->first();

                        if (!$estoque) {
                            $estoque = new \App\Models\Estoque([
                                'empresa_id' => $this->empresa_id,
                                'filial_id' => $filialId,
                                'produto_id' => $produto->id,
                                'quantidade' => 0,
                                'valor_compra' => $custoConvertido,
                            ]);
                        }

                        $estoque->quantidade = (float) $estoque->quantidade + $quantidadeConvertida;
                        $estoque->valor_compra = $custoConvertido;
                        $estoque->save();
                    }

                    $produto->valor_compra = $custoConvertido;
                    $produto->save();
                }

                $faturaSalva = $this->registrarFinanceiroDfe($request, $compra);

                $dfe->compra_id = $compra->id;
                $dfe->fatura_salva = $faturaSalva;
                $dfe->save();

                $destinoDir = public_path('xml_entrada');
                if (!is_dir($destinoDir) && !mkdir($destinoDir, 0755, true) && !is_dir($destinoDir)) {
                    throw new \RuntimeException('Não foi possível criar o diretório de XMLs de entrada.');
                }

                $destino = $destinoDir . DIRECTORY_SEPARATOR . $chave . '.xml';
                if (!copy($xmlPath, $destino)) {
                    throw new \RuntimeException('Não foi possível copiar o XML para o diretório de entradas.');
                }

                return $compra;
            }, 3);

            return redirect('/dfe')->with(
                'mensagem_sucesso',
                'Compra #' . $compra->id . ', estoque, financeiro e tributos importados com sucesso.'
            );
        } catch (\Throwable $e) {
            \Log::error('Erro ao importar DF-e como compra', [
                'empresa_id' => $this->empresa_id,
                'dfe_id' => $request->dfe_id,
                'erro' => $e->getMessage(),
            ]);

            return redirect()->back()->withInput()->with(
                'mensagem_erro',
                'Não foi possível importar o DF-e: ' . $e->getMessage()
            );
        }
    }

    private function normalizarFilialDfe($filialId): ?int
    {
        if ($filialId === null || $filialId === '' || $filialId === 'matriz' || (int) $filialId <= 0) {
            return null;
        }

        return (int) $filialId;
    }

    private function extrairTributosItemDfe($xmlItem): array
    {
        $dados = [
            'cst_icms' => null,
            'vbc_icms' => 0,
            'p_icms' => 0,
            'v_icms' => 0,
            'cst_ipi' => null,
            'vbc_ipi' => 0,
            'p_ipi' => 0,
            'v_ipi' => 0,
            'cst_pis' => null,
            'vbc_pis' => 0,
            'p_pis' => 0,
            'v_pis' => 0,
            'cst_cofins' => null,
            'vbc_cofins' => 0,
            'p_cofins' => 0,
            'v_cofins' => 0,
            'cst_ibs_cbs' => null,
            'class_trib_ibs_cbs' => null,
            'bc_ibs_cbs' => 0,
            'aliq_ibs_uf' => 0,
            'aliq_cbs' => 0,
            'valor_ibs' => 0,
            'valor_cbs' => 0,
            'valor_ibs_mono' => 0,
            'valor_cbs_mono' => 0,
            'adrem_ibs_reten' => 0,
            'adrem_cbs_reten' => 0,
            'adrem_ibs_ret' => 0,
            'adrem_cbs_ret' => 0,
            'valor_ibs_reten' => 0,
            'valor_cbs_reten' => 0,
            'valor_ibs_ret' => 0,
            'valor_cbs_ret' => 0,
        ];

        if (!$xmlItem || !isset($xmlItem->imposto)) {
            return $dados;
        }

        $imposto = $xmlItem->imposto;

        if (isset($imposto->ICMS)) {
            foreach ($imposto->ICMS->children() as $grupo) {
                $origem = (string) ($grupo->orig ?? '');
                $cst = isset($grupo->CST) ? (string) $grupo->CST : (string) ($grupo->CSOSN ?? '');
                $dados['cst_icms'] = $origem . $cst;
                $dados['vbc_icms'] = (float) ($grupo->vBC ?? 0);
                $dados['p_icms'] = (float) ($grupo->pICMS ?? 0);
                $dados['v_icms'] = (float) ($grupo->vICMS ?? 0);
                break;
            }
        }

        if (isset($imposto->IPI)) {
            $grupoIpi = $imposto->IPI->IPITrib ?? $imposto->IPI->IPINT ?? null;
            if ($grupoIpi) {
                $dados['cst_ipi'] = (string) ($grupoIpi->CST ?? '');
                $dados['vbc_ipi'] = (float) ($grupoIpi->vBC ?? 0);
                $dados['p_ipi'] = (float) ($grupoIpi->pIPI ?? 0);
                $dados['v_ipi'] = (float) ($grupoIpi->vIPI ?? 0);
            }
        }

        if (isset($imposto->PIS)) {
            foreach ($imposto->PIS->children() as $grupoPis) {
                $dados['cst_pis'] = (string) ($grupoPis->CST ?? '');
                $dados['vbc_pis'] = (float) ($grupoPis->vBC ?? 0);
                $dados['p_pis'] = (float) ($grupoPis->pPIS ?? 0);
                $dados['v_pis'] = (float) ($grupoPis->vPIS ?? 0);
                break;
            }
        }

        if (isset($imposto->COFINS)) {
            foreach ($imposto->COFINS->children() as $grupoCofins) {
                $dados['cst_cofins'] = (string) ($grupoCofins->CST ?? '');
                $dados['vbc_cofins'] = (float) ($grupoCofins->vBC ?? 0);
                $dados['p_cofins'] = (float) ($grupoCofins->pCOFINS ?? 0);
                $dados['v_cofins'] = (float) ($grupoCofins->vCOFINS ?? 0);
                break;
            }
        }

        if (isset($imposto->IBSCBS)) {
            $ibscbs = $imposto->IBSCBS;
            $dados['cst_ibs_cbs'] = (string) ($ibscbs->CST ?? '');
            $dados['class_trib_ibs_cbs'] = (string) ($ibscbs->cClassTrib ?? '');

            if (isset($ibscbs->gIBSCBS)) {
                $grupo = $ibscbs->gIBSCBS;
                $dados['bc_ibs_cbs'] = (float) ($grupo->vBC ?? 0);
                $dados['aliq_ibs_uf'] = (float) ($grupo->gIBSUF->pIBSUF ?? 0);
                $dados['valor_ibs'] = (float) ($grupo->vIBS ?? $grupo->gIBSUF->vIBSUF ?? 0);
                $dados['aliq_cbs'] = (float) ($grupo->gCBS->pCBS ?? 0);
                $dados['valor_cbs'] = (float) ($grupo->gCBS->vCBS ?? 0);
            }

            if (isset($ibscbs->gIBSCBSMono)) {
                $mono = $ibscbs->gIBSCBSMono;
                $dados['valor_ibs_mono'] = (float) ($mono->vTotIBSMonoItem ?? $mono->vIBSMono ?? 0);
                $dados['valor_cbs_mono'] = (float) ($mono->vTotCBSMonoItem ?? $mono->vCBSMono ?? 0);
                $dados['valor_ibs'] = $dados['valor_ibs_mono'];
                $dados['valor_cbs'] = $dados['valor_cbs_mono'];
                $dados['adrem_ibs_reten'] = (float) ($mono->adRemIBSReten ?? 0);
                $dados['adrem_cbs_reten'] = (float) ($mono->adRemCBSReten ?? 0);
                $dados['adrem_ibs_ret'] = (float) ($mono->adRemIBSRet ?? 0);
                $dados['adrem_cbs_ret'] = (float) ($mono->adRemCBSRet ?? 0);
                $dados['valor_ibs_reten'] = (float) ($mono->vIBSMonoReten ?? 0);
                $dados['valor_cbs_reten'] = (float) ($mono->vCBSMonoReten ?? 0);
                $dados['valor_ibs_ret'] = (float) ($mono->vIBSMonoRet ?? 0);
                $dados['valor_cbs_ret'] = (float) ($mono->vCBSMonoRet ?? 0);
            }
        }

        return $dados;
    }

    private function registrarFinanceiroDfe(Request $request, Compra $compra): bool
    {
        $faturas = $this->normalizarFaturasDfe($request);
        if ($faturas === []) {
            return false;
        }

        $contaEmpresaId = $request->filled('conta_empresa_id') ? (int) $request->conta_empresa_id : null;
        $conta = null;
        if ($contaEmpresaId) {
            $conta = \App\Models\ContaEmpresa::query()
                ->where('empresa_id', $this->empresa_id)
                ->where('status', 1)
                ->lockForUpdate()
                ->findOrFail($contaEmpresaId);
        }

        foreach ($faturas as $fatura) {
            $valor = (float) $fatura['valor'];
            if ($valor <= 0) {
                continue;
            }

            $formaPagamento = strtolower(trim((string) ($fatura['forma_pagamento'] ?? 'boleto')));
            $isAdiantamento = $formaPagamento === 'adiantamento';
            $isPagamentoImediato = !$isAdiantamento
                && $conta
                && in_array($formaPagamento, ['dinheiro', 'pix', 'transferencia', 'cartao_combustivel', 'cartao', 'debito'], true);

            $valorAdiantamento = 0.0;
            if ($isAdiantamento) {
                $valorAdiantamento = min($valor, $this->saldoAdiantamentoFornecedorDfe((int) $compra->fornecedor_id));
            }

            $valorPago = $isPagamentoImediato ? $valor : $valorAdiantamento;
            $statusPago = $valorPago + 0.00001 >= $valor;

            $contaPagar = ContaPagar::create([
                'compra_id' => $compra->id,
                'data_vencimento' => $fatura['vencimento'],
                'data_emissao' => $compra->data_emissao,
                'data_emissao_nfe' => $compra->data_emissao,
                'valor_integral' => $valor,
                'valor_original' => $valor,
                'valor_pago' => $valorPago,
                'data_pagamento' => $valorPago > 0 ? $compra->data_emissao : null,
                'numero_nota_fiscal' => $compra->nf,
                'referencia' => 'Parcela ' . $fatura['numero'] . ' NF: ' . $compra->nf,
                'categoria_id' => $compra->categoria_conta_id,
                'status' => $statusPago,
                'tipo_pagamento' => $formaPagamento ?: 'boleto',
                'conta_id_origem' => $isPagamentoImediato ? $conta?->id : null,
                'empresa_id' => $this->empresa_id,
                'fornecedor_id' => $compra->fornecedor_id,
                'veiculo_id' => $fatura['veiculo_id'] ?: $compra->veiculo_id,
                'usuario_id' => get_id_user(),
                'usuario_baixa_id' => $valorPago > 0 ? get_id_user() : null,
                'filial_id' => $compra->filial_id,
            ]);

            if ($valorAdiantamento > 0) {
                \App\Http\Controllers\AdiantamentoController::baixarAdiantamento(
                    $compra->fornecedor_id,
                    'fornecedor',
                    $valorAdiantamento,
                    $this->empresa_id,
                    $contaPagar->id,
                    get_id_user(),
                    $compra->filial_id
                );
            }

            if ($isPagamentoImediato && $conta) {
                $itemConta = \App\Models\ItemContaEmpresa::create([
                    'conta_id' => $conta->id,
                    'descricao' => 'Pagamento NF ' . $compra->nf . ' - parcela ' . $fatura['numero'],
                    'valor' => $valor,
                    'tipo_pagamento' => $formaPagamento,
                    'tipo' => 'saida',
                    'data_pagamento' => $compra->data_emissao,
                    'user_id' => get_id_user(),
                    'empresa_id' => $this->empresa_id,
                    'origem' => 'Conta Pagar',
                    'categoria_id' => $compra->categoria_conta_id,
                    'conta_pagar_id' => $contaPagar->id,
                ]);

                app(\App\Utils\ContaEmpresaUtil::class)->atualizaSaldo($itemConta);
                $conta->refresh();
            }
        }

        return true;
    }

    private function normalizarFaturasDfe(Request $request): array
    {
        $faturas = [];

        $numeros = (array) $request->input('fatura_num', []);
        $vencimentos = (array) $request->input('fatura_venc', []);
        $valores = (array) $request->input('fatura_val', []);
        $formas = (array) $request->input('forma_pagamento', []);
        $veiculos = (array) $request->input('fatura_veiculo', []);

        foreach ($numeros as $indice => $numero) {
            $valor = $this->parseMoeda($valores[$indice] ?? 0);
            if ($valor <= 0) {
                continue;
            }

            $vencimento = $vencimentos[$indice] ?? $request->data_emissao ?? now()->toDateString();
            $faturas[] = [
                'numero' => trim((string) $numero) ?: str_pad((string) ($indice + 1), 3, '0', STR_PAD_LEFT),
                'vencimento' => \Carbon\Carbon::parse(str_replace('/', '-', (string) $vencimento))->toDateString(),
                'valor' => $valor,
                'forma_pagamento' => $formas[$indice] ?? 'boleto',
                'veiculo_id' => !empty($veiculos[$indice]) ? (int) $veiculos[$indice] : null,
            ];
        }

        if ($faturas !== []) {
            return $faturas;
        }

        $legado = $request->input('fatura');
        $legado = is_string($legado) ? json_decode($legado) : $legado;
        if (!is_iterable($legado)) {
            return [];
        }

        foreach ($legado as $indice => $parcela) {
            $valorBruto = $parcela->valor_parcela ?? $parcela->valor ?? 0;
            if (is_object($valorBruto)) {
                $array = (array) $valorBruto;
                $valorBruto = reset($array);
            }
            $valor = $this->parseMoeda($valorBruto);
            if ($valor <= 0) {
                continue;
            }

            $vencimento = $parcela->vencimento ?? now()->toDateString();
            $faturas[] = [
                'numero' => trim((string) ($parcela->numero ?? ($indice + 1))),
                'vencimento' => \Carbon\Carbon::parse(str_replace('/', '-', (string) $vencimento))->toDateString(),
                'valor' => $valor,
                'forma_pagamento' => $parcela->forma_pagamento ?? 'boleto',
                'veiculo_id' => isset($parcela->veiculo_id) ? (int) $parcela->veiculo_id : null,
            ];
        }

        return $faturas;
    }

    private function saldoAdiantamentoFornecedorDfe(int $fornecedorId): float
    {
        return (float) \App\Models\Adiantamento::query()
            ->where('empresa_id', $this->empresa_id)
            ->where('fornecedor_id', $fornecedorId)
            ->where('status', 'aberto')
            ->selectRaw('COALESCE(SUM(valor_total - valor_utilizado), 0) as saldo')
            ->value('saldo');
    }
  
  
      // Adicione esta função auxiliar no final do DFeController para evitar erros de valor
      private function parseMoeda($valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }

        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        if (is_object($valor)) {
            $array = (array) $valor;
            $valor = reset($array);
        }

        $texto = trim((string) $valor);
        $texto = preg_replace('/[^0-9,.-]/', '', $texto);

        if (str_contains($texto, ',') && str_contains($texto, '.')) {
            if (strrpos($texto, ',') > strrpos($texto, '.')) {
                $texto = str_replace('.', '', $texto);
                $texto = str_replace(',', '.', $texto);
            } else {
                $texto = str_replace(',', '', $texto);
            }
        } elseif (str_contains($texto, ',')) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        } elseif (substr_count($texto, '.') > 1) {
            $partes = explode('.', $texto);
            $decimal = array_pop($partes);
            $texto = implode('', $partes) . '.' . $decimal;
        }

        return is_numeric($texto) ? (float) $texto : 0.0;
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

    public function salvarProdutoAjax(Request $request)
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'ncm' => ['nullable', 'string', 'max:10'],
            'cest' => ['nullable', 'string', 'max:10'],
            'unidade_compra' => ['nullable', 'string', 'max:10'],
            'unidade_venda' => ['nullable', 'string', 'max:10'],
            'codBarras' => ['nullable', 'string', 'max:50'],
        ]);
        $produto = new Produto();
        $permitidos = array_intersect_key($request->all(), array_flip($produto->getFillable()));
        $permitidos['empresa_id'] = $this->empresa_id;
        $permitidos['usuario_id'] = $this->usuario_id ?? (session('user_logged')['id'] ?? null);
        $permitidos['nome'] = mb_strtoupper($dados['nome']);
        $permitidos['unidade_compra'] = $dados['unidade_compra'] ?? 'UN';
        $permitidos['unidade_venda'] = $dados['unidade_venda'] ?? 'UN';
        $permitidos['ncm'] = $dados['ncm'] ?? '00000000';
        $produto->fill($permitidos);
        $produto->save();
        return response()->json(['id'=>$produto->id,'nome'=>$produto->nome]);
    }

    public function sincronizarCompras()
    {
        $atualizadas = 0;
        ManifestaDfe::query()->where('empresa_id', $this->empresa_id)
            ->where(fn ($q) => $q->whereNull('compra_id')->orWhere('compra_id', 0))
            ->orderBy('id')->chunkById(200, function ($documentos) use (&$atualizadas): void {
                foreach ($documentos as $dfe) {
                    $compra = Compra::where('empresa_id', $this->empresa_id)->where('chave', $dfe->chave)->first();
                    if (!$compra) continue;
                    $dfe->compra_id = $compra->id;
                    $dfe->fatura_salva = ContaPagar::where('empresa_id', $this->empresa_id)->where('compra_id', $compra->id)->exists();
                    $dfe->save();
                    $atualizadas++;
                }
            });
        return redirect()->back()->with('mensagem_sucesso', "Sincronização concluída: {$atualizadas} documento(s) atualizado(s).");
    }
}
