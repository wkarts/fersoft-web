<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\ItemCompra;
use App\Models\Fornecedor;
use App\Models\Compra;
use App\Helpers\StockMove;
use App\Models\Cidade;
use App\Models\CategoriaConta;
use App\Models\ConfigNota;
use App\Models\ManifestaDfe;
use App\Models\NaturezaOperacao;
use App\Models\Empresa;
use App\Models\Filial;
use App\Services\DFeService;
use App\Models\Marca;
use App\Models\SubCategoria;
use App\Models\Veiculo;
use App\Models\ContaPagar;
use App\Models\Adiantamento;
use Illuminate\Support\Facades\DB;

class CompraFiscalController extends BaseController
{
    // Funções exigidas pelo BaseController
    protected function rules(): array { return []; }
    protected function messages(): array { return []; }

    protected $empresa_id = null;
    protected $usuario_id = null;
    protected $filial_id  = null;

    // =========================================================
    // VARIÁVEIS OBRIGATÓRIAS EXIGIDAS PELO BASECONTROLLER
    // =========================================================
    protected $redirectPage = '/compraFiscal';
    protected $formTitle = 'Compra Fiscal';
    protected $model = Compra::class;

	public function __construct(){
        parent::__construct();

		$this->middleware(function ($request, $next) {
			$this->empresa_id = $request->empresa_id;

            $this->usuario_id = session('user_logged')['id'] ?? null;
            if (!$this->usuario_id) {
                return redirect('/login');
            }

            $this->filial_id = $request->get('filial_id')
                ?? session('user_logged.local_padrao')
                ?? null;

			return $next($request);
		});
	}

	public function index(){
        $veiculos = Veiculo::where('empresa_id', $this->empresa_id)->get();
		$natureza = Produto::firstNatureza($this->empresa_id);
		if($natureza == null){
			session()->flash('mensagem_erro', 'Cadastre uma natureza de operação!');
			return redirect('/naturezaOperacao');
		}

		$categoria = Categoria::where('empresa_id', $this->empresa_id)->first();
		if($categoria == null){
			session()->flash('mensagem_erro', 'Cadastre uma categoria de produto!');
			return redirect('/categorias');
		}

		$config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
		if($config == null){
			session()->flash('mensagem_erro', 'Configure o emitente!');
			return redirect('/configNF');
		}

		return view('compraFiscal/new')
            ->with('veiculos', $veiculos)
            ->with('title', 'Compra Fiscal');
	}

	private function validaChave($chave){
		$msg = "";
		$chave = substr($chave, 3, 44);

		$cp = Compra::where('chave', $chave)->where('empresa_id', $this->empresa_id)->first();
		if($cp != null) $msg = "XML já importado na compra fiscal";
		return $msg;
	}

	public function new(Request $request){
		if ($request->hasFile('file')){
			$arquivo = $request->hasFile('file');
			$xml = simplexml_load_file($request->file);

			if($xml->NFe->infNFe == null){
				session()->flash('mensagem_erro', 'Este XML parece inválido!');
				return redirect("/compraFiscal");
			}

            // ==============================================================================
            // TRAVA DE SEGURANÇA: Verifica se o CNPJ do XML pertence à Empresa ou Filial
            // ==============================================================================
            $cnpjDestino = (string)$xml->NFe->infNFe->dest->CNPJ;
            if(empty($cnpjDestino)) {
                $cnpjDestino = (string)$xml->NFe->infNFe->dest->CPF;
            }

            $empresa = Empresa::find($this->empresa_id);
            $cnpjEmpresa = $empresa ? preg_replace('/[^0-9]/', '', $empresa->cnpj ?? $empresa->cpf_cnpj ?? '') : '';
            
            $valido = false;
            if($cnpjEmpresa != '' && $cnpjDestino == $cnpjEmpresa) $valido = true;
            
            if($this->filial_id){
                $filial = Filial::find($this->filial_id);
                $cnpjFilial = $filial ? preg_replace('/[^0-9]/', '', $filial->cnpj ?? $filial->cpf_cnpj ?? '') : '';
                if($cnpjFilial != '' && $cnpjDestino == $cnpjFilial) $valido = true;
            }

            if(!$valido){
                session()->flash('mensagem_erro', "O CNPJ/CPF do destinatário na NF ({$cnpjDestino}) não pertence à sua Empresa ou Filial ativa!");
                return redirect("/compraFiscal");
            }

			$msgImport = $this->validaChave($xml->NFe->infNFe->attributes()->Id);
			if($msgImport == ""){
				$cidade = Cidade::getCidadeCod($xml->NFe->infNFe->emit->enderEmit->cMun);
				$dadosEmitente = [
					'cpf' => (string)$xml->NFe->infNFe->emit->CPF,
					'cnpj' => (string)$xml->NFe->infNFe->emit->CNPJ,
					'razaoSocial' => (string)$xml->NFe->infNFe->emit->xNome,
					'nomeFantasia' => (string)$xml->NFe->infNFe->emit->xFant,
					'logradouro' => (string)$xml->NFe->infNFe->emit->enderEmit->xLgr,
					'numero' => (string)$xml->NFe->infNFe->emit->enderEmit->nro,
					'bairro' => (string)$xml->NFe->infNFe->emit->enderEmit->xBairro,
					'cep' => (string)$xml->NFe->infNFe->emit->enderEmit->CEP,
					'fone' => (string)$xml->NFe->infNFe->emit->enderEmit->fone,
					'ie' => (string)$xml->NFe->infNFe->emit->IE,
					'cidade_id' => $cidade->id,
					'cidade' => $cidade->info
				];

				$vFrete = number_format((double) $xml->NFe->infNFe->total->ICMSTot->vFrete,
					2, ",", ".");

				$vDesc = $xml->NFe->infNFe->total->ICMSTot->vDesc;

				$idFornecedor = 0;
				$fornecedorEncontrado = $this->verificaFornecedor($dadosEmitente['cnpj'] ?: $dadosEmitente['cpf']);
				$dadosAtualizados = [];
				if($fornecedorEncontrado){
					$idFornecedor = $fornecedorEncontrado->id;
					$dadosAtualizados = $this->verificaAtualizacao($fornecedorEncontrado, $dadosEmitente);
				}else{
					array_push($dadosAtualizados, "Fornecedor cadastrado com sucesso");
					$idFornecedor = $this->cadastrarFornecedor($dadosEmitente);
				}

                // ============================================================
                // BUSCA O SALDO DE ADIANTAMENTO PARA MANDAR PARA A VIEW
                // ============================================================
                $saldo_credito = 0;
                if ($idFornecedor > 0) {
                    $saldo_credito = Adiantamento::where('empresa_id', $this->empresa_id)
                        ->where('fornecedor_id', $idFornecedor)
                        ->where('status', 'aberto')
                        ->sum(DB::raw('valor_total - valor_utilizado'));
                }

				$seq = 0;
				$itens = [];
				$contSemRegistro = 0;
				foreach($xml->NFe->infNFe->det as $item) {

					$produto = Produto::where('nome', (string)$item->prod->xProd)
					->where('empresa_id', $this->empresa_id)
					->first();

					if($produto == null){
						$produto = Produto::where('codBarras', (string)$item->prod->cEAN)
						->where('codBarras', '!=', 'SEM GTIN')
						->where('empresa_id', $this->empresa_id)
						->first();
					}

					$produtoNovo = !$produto ? true : false;
					$codSiad = 0;
					if($produtoNovo){
						$contSemRegistro++;
					}
					else{
						$i = ItemCompra::where('produto_id', $produto->id)->first();
						if($i != null){ $codSiad = $i->codigo_siad ?? 0; }
					}
					$codigo = preg_replace('/[^a-zA-Z0-9]/', '', (string)$item->prod->cProd);

					$vIpi = 0;
					$vICMSST = 0;
					if(isset($item->imposto->IPI)){
						$valor = (float)$item->imposto->IPI->IPITrib->vIPI;
						if($valor > 0)
							$vIpi = $valor/(float)$item->prod->qCom;
					}

					if(isset($item->imposto->ICMS)){
						$arr = (array_values((array)$item->imposto->ICMS));
						$valor = (float)$arr[0]->vICMSST ?? 0;
						if($valor > 0)
							$vICMSST = $valor/$item->prod->qCom;
					}

                    // Extração segura do ICMS, preservando origem + CST/CSOSN em três dígitos.
                    $icmsNode = isset($item->imposto->ICMS) ? $item->imposto->ICMS->children()[0] : null;
                    $origemIcms = $icmsNode ? (string) ($icmsNode->orig ?? '0') : '0';
                    $codigoIcms = $icmsNode ? (string) ($icmsNode->CST ?? $icmsNode->CSOSN ?? '90') : '90';
                    $cst_icms = $origemIcms . str_pad(preg_replace('/[^0-9]/', '', $codigoIcms), 2, '0', STR_PAD_LEFT);
                    $vbc_icms = (float) ($icmsNode->vBC ?? 0);
                    $p_icms = (float) ($icmsNode->pICMS ?? 0);
                    $v_icms = (float) ($icmsNode->vICMS ?? 0);

                    // Reforma Tributária: suporta operação normal e monofásica.
                    $cst_ibs_cbs = '';
                    $bc_ibs_cbs = 0.0;
                    $aliq_ibs = 0.0;
                    $valor_ibs = 0.0;
                    $aliq_cbs = 0.0;
                    $valor_cbs = 0.0;
                    $class_trib = '';

                    if (isset($item->imposto->IBSCBS)) {
                        $ibscbs = $item->imposto->IBSCBS;
                        $cst_ibs_cbs = (string) ($ibscbs->CST ?? '');
                        $class_trib = (string) ($ibscbs->cClassTrib ?? '');

                        if (isset($ibscbs->gIBSCBS)) {
                            $bc_ibs_cbs = (float) ($ibscbs->gIBSCBS->vBC ?? 0);
                            $aliq_ibs = (float) ($ibscbs->gIBSCBS->gIBSUF->pIBSUF ?? 0);
                            $valor_ibs = (float) ($ibscbs->gIBSCBS->vIBS ?? 0);
                            $aliq_cbs = (float) ($ibscbs->gIBSCBS->gCBS->pCBS ?? 0);
                            $valor_cbs = (float) ($ibscbs->gIBSCBS->gCBS->vCBS ?? 0);
                        } elseif (isset($ibscbs->gIBSCBSMono)) {
                            $valor_ibs = (float) ($ibscbs->gIBSCBSMono->vTotIBSMonoItem ?? 0);
                            $valor_cbs = (float) ($ibscbs->gIBSCBSMono->vTotCBSMonoItem ?? 0);
                        }
                    }
                  
                  	// Dentro do loop de itens no método new()
                    $cfopOriginal = (string)$item->prod->CFOP;
                    $cfopEntrada = $this->getCfopEntrada($cfopOriginal);

                    // Regra específica de CFOP solicitada
                    if($cfopOriginal == '5656') $cfopEntrada = '1653';
                    if($cfopOriginal == '6656') $cfopEntrada = '2653';

                    $sugestoes = $this->aplicarRegrasFiscais($item, $cfopEntrada);

					$nomeProduto = str_replace("'", "", (string)$item->prod->xProd);

					$itemAdd = [
						'id' => !$produtoNovo ? $produto->id : 0,
						'codigo' => $codigo,
						'xProd' => $produto == null ? $nomeProduto : $produto->nome,
						'NCM' => (string)$item->prod->NCM,
						'CEST' => (string)$item->prod->CEST,
						'CFOP' => (string)$item->prod->CFOP,
						'CFOP_entrada' => $cfopEntrada,
						'uCom' => (string)$item->prod->uCom,
						'vUnCom' => number_format((float)$item->prod->vUnCom + $vIpi + $vICMSST, 2, '.', ''),
						'qCom' => (string)$item->prod->qCom,
						'codBarras' => (string)$item->prod->cEAN,
						'produtoNovo' => $produtoNovo,
						'codSiad' => $codSiad,
						'produtoId' => $produtoNovo ? '0' : $produto->id,
						'conversao_unitaria' => $produtoNovo ? '' : $produto->conversao_unitaria,
						'valor_venda' => $produtoNovo ? 0 : $produto->valor_venda,
						'valor_compra' => $produtoNovo ? 0 : $produto->valor_compra,
                        'cst_icms' => $cst_icms,
                        'cst_pis' => $sugestoes['cst_pis'],
                        'cst_cofins' => $sugestoes['cst_cofins'], 
                        'vbc_icms' => $vbc_icms, 
                        'p_icms' => $p_icms, 
                        'v_icms' => $v_icms,
                      
                     	 // Valores de PIS/COFINS do XML
                        'vbc_pis' => (float)($item->imposto->PIS->children()[0]->vBC ?? 0),
                        'p_pis' => (float)($item->imposto->PIS->children()[0]->pPIS ?? 0),
                        'v_pis' => (float)($item->imposto->PIS->children()[0]->vPIS ?? 0),
                        'vbc_cofins' => (float)($item->imposto->COFINS->children()[0]->vBC ?? 0),
                        'p_cofins' => (float)($item->imposto->COFINS->children()[0]->pCOFINS ?? 0),
                        'v_cofins' => (float)($item->imposto->COFINS->children()[0]->vCOFINS ?? 0),
                        
                    // IBS / CBS (Reforma Tributária)
                        'cst_ibs_cbs' => $cst_ibs_cbs,
                        'bc_ibs_cbs' => $bc_ibs_cbs,
                        'aliq_ibs' => $aliq_ibs,
                        'valor_ibs' => $valor_ibs,
                        'aliq_cbs' => $aliq_cbs,
                        'valor_cbs' => $valor_cbs,
                        'class_trib' => $class_trib
					];

					array_push($itens, $itemAdd);
				}

                $infCpl = isset($xml->NFe->infNFe->infAdic->infCpl)
                    ? (string) $xml->NFe->infNFe->infAdic->infCpl
                    : '';

				$chave = substr($xml->NFe->infNFe->attributes()->Id, 3, 44);
				$dadosNf = [
					'chave' => $chave,
                    'vProd' => (float) $xml->NFe->infNFe->total->ICMSTot->vProd,
                    'vNF' => (float) $xml->NFe->infNFe->total->ICMSTot->vNF,
					'indPag' => (string)$xml->NFe->infNFe->ide->indPag,
					'nNf' => (string)$xml->NFe->infNFe->ide->nNF,
					'vFrete' => $vFrete,
					'vDesc' => $vDesc,
					'contSemRegistro' => $contSemRegistro,
					'data_emissao' => substr($xml->NFe->infNFe->ide->dhEmi[0], 0, 16),
                    'vbc_icms' => (float)$xml->NFe->infNFe->total->ICMSTot->vBC,
                    'v_icms' => (float)$xml->NFe->infNFe->total->ICMSTot->vICMS,
                    'v_ipi' => (float)$xml->NFe->infNFe->total->ICMSTot->vIPI,
                    'v_pis' => (float)$xml->NFe->infNFe->total->ICMSTot->vPIS,
                    'v_cofins' => (float)$xml->NFe->infNFe->total->ICMSTot->vCOFINS,
                    'infCpl' => $infCpl,
				];

				$fatura = [];
				if (!empty($xml->NFe->infNFe->cobr->dup))
				{
					foreach($xml->NFe->infNFe->cobr->dup as $dup) {
						$vencimento = explode('-', (string)$dup->dVenc);
						$parcela = [
							'numero' => (int)$dup->nDup,
							'vencimento' => $vencimento[2]."/".$vencimento[1]."/".$vencimento[0],
							'valor_parcela' => number_format((double) $dup->vDup, 2, ".", ""),
							'rand' => rand(0, 10000)
						];
						array_push($fatura, $parcela);
					}
				}else{
					$vencimento = explode('-', substr($xml->NFe->infNFe->ide->dhEmi[0], 0,10));
					$parcela = [
						'numero' => 1,
						'vencimento' => $vencimento[2]."/".$vencimento[1]."/".$vencimento[0],
						'valor_parcela' => (float)$xml->NFe->infNFe->total->ICMSTot->vProd,
						'rand' => rand(0, 10000)
					];
					array_push($fatura, $parcela);
				}

				$file = $request->file;
				$nameArchive = $chave . ".xml" ;
				$pathXml = $file->move(public_path('xml_entrada'), $nameArchive);

				$categorias = Categoria::where('empresa_id', $this->empresa_id)->get();
				$unidadesDeMedida = Produto::unidadesMedida();
				$listaCSTCSOSN = Produto::listaCSTCSOSN();
				$listaCST_PIS_COFINS = Produto::listaCST_PIS_COFINS();
				$listaCST_IPI = Produto::listaCST_IPI();
				$config = ConfigNota::where('empresa_id', $this->empresa_id)->first();
				$anps = Produto::lista_ANP();
				$marcas = Marca::where('empresa_id', $this->empresa_id)->get();
				$subs = SubCategoria::select('sub_categorias.*')->join('categorias', 'categorias.id', '=', 'sub_categorias.categoria_id')->where('empresa_id', $request->empresa_id)->get();
				$categoriasDeConta = CategoriaConta::where('empresa_id', $this->empresa_id)->where('tipo', 'pagar')->orderBy('nome', 'asc')->get();
                $contasEmpresa = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)
                    ->where('status', 1)
                    ->orderBy('nome')
                    ->get();

				return view('compraFiscal/visualizaNota')
				->with('title', 'Nota Fiscal')
				->with('itens', $itens)
                ->with('saldo_credito', $saldo_credito) // MANDANDO SALDO PRA VIEW
                ->with('veiculos', Veiculo::where('empresa_id', $this->empresa_id)->get())
				->with('subs', $subs)->with('marcas', $marcas)->with('categoriasDeConta', $categoriasDeConta)
				->with('fatura', $fatura)
				->with('anps', $anps)
				->with('pathXml', $nameArchive)
				->with('compraFiscalJs', true)
				->with('idFornecedor', $idFornecedor)
				->with('dadosNf', $dadosNf)
				->with('listaCSTCSOSN', $listaCSTCSOSN)
				->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
				->with('listaCST_IPI', $listaCST_IPI)
				->with('config', $config)
				->with('unidadesDeMedida', $unidadesDeMedida)
				->with('categorias', $categorias)
				->with('dadosEmitente', $dadosEmitente)
                ->with('contasEmpresa', $contasEmpresa)
				->with('dadosAtualizados', $dadosAtualizados);
			}else{
				session()->flash('mensagem_erro', $msgImport);
				return redirect("/compraFiscal");
			}
		}
	}

	private function getCfopEntrada($cfop){
		$natureza = NaturezaOperacao::
		where('empresa_id', $this->empresa_id)
		->where('CFOP_saida_estadual', $cfop)
		->first();

		if($natureza != null){
			return $natureza->CFOP_entrada_inter_estadual;
		}

		$natureza = NaturezaOperacao::
		where('empresa_id', $this->empresa_id)
		->where('CFOP_saida_inter_estadual', $cfop)
		->first();

		if($natureza != null){
			return $natureza->CFOP_entrada_inter_estadual;
		}

		$digito = substr($cfop, 0, 1);
		if($digito == '5'){
			return '1'. substr($cfop, 1, 4);
		}else{
			return '2'. substr($cfop, 1, 4);
		}
	}

	private function verificaFornecedor($cnpj){
        $cnpjApenasNumeros = preg_replace('/[^0-9]/', '', $cnpj);
        $forn = Fornecedor::where('cpf_cnpj', $this->formataCnpj($cnpj))
                      ->orWhere('cpf_cnpj', $cnpjApenasNumeros)
                      ->where('empresa_id', $this->empresa_id)
                      ->first();
        return $forn;
    }

	private function verificaAtualizacao($fornecedorEncontrado, $dadosEmitente){
		$dadosAtualizados = [];
		$verifica = $this->dadosAtualizados('Razao Social', $fornecedorEncontrado->razao_social, $dadosEmitente['razaoSocial']);
		if($verifica) array_push($dadosAtualizados, $verifica);
		$verifica = $this->dadosAtualizados('Nome Fantasia', $fornecedorEncontrado->nome_fantasia, $dadosEmitente['nomeFantasia']);
		if($verifica) array_push($dadosAtualizados, $verifica);
		$verifica = $this->dadosAtualizados('Rua', $fornecedorEncontrado->rua, $dadosEmitente['logradouro']);
		if($verifica) array_push($dadosAtualizados, $verifica);
		$verifica = $this->dadosAtualizados('Numero', $fornecedorEncontrado->numero, $dadosEmitente['numero']);
		if($verifica) array_push($dadosAtualizados, $verifica);
		$verifica = $this->dadosAtualizados('Bairro', $fornecedorEncontrado->bairro, $dadosEmitente['bairro']);
		if($verifica) array_push($dadosAtualizados, $verifica);
		$verifica = $this->dadosAtualizados('IE', $fornecedorEncontrado->ie_rg, $dadosEmitente['ie']);
		if($verifica) array_push($dadosAtualizados, $verifica);

		$this->atualizar($fornecedorEncontrado, $dadosEmitente);
		return $dadosAtualizados;
	}

	private function atualizar($fornecedor, $dadosEmitente){
		$fornecedor->razao_social = $dadosEmitente['razaoSocial'];
		$fornecedor->nome_fantasia = $dadosEmitente['nomeFantasia'];
		$fornecedor->rua = $dadosEmitente['logradouro'];
		$fornecedor->ie_rg = $dadosEmitente['ie'];
		$fornecedor->bairro = $dadosEmitente['bairro'];
		$fornecedor->numero = $dadosEmitente['numero'];
		$fornecedor->save();
	}

	private function dadosAtualizados($campo, $anterior, $atual){
		if($anterior != $atual){ return $campo . " atualizado"; }
		return false;
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

	public function salvarNfFiscal(Request $request){
    $nf = $request->nf;
    $veiculo_id = (isset($nf['veiculo_id']) && $nf['veiculo_id'] > 0) ? $nf['veiculo_id'] : null;
    $data_emissao = isset($nf['data_emissao']) ? substr($nf['data_emissao'], 0, 10) : date('Y-m-d');

    $result = Compra::create([
        'fornecedor_id' => $nf['fornecedor_id'],
        'usuario_id' => $this->usuario_id,
        'nf' => $nf['nNf'],
        'data_emissao' => $data_emissao,
        'observacao' => '',
        'lote' => $nf['lote'] ?? '',
        // CORREÇÃO: Usando parseMoeda para resolver o problema do 2.152,00 virar 2,15
        'valor' => $this->parseMoeda($nf['valor_nf'] ?? 0),
        'desconto' => $this->parseMoeda($nf['desconto']),
        'xml_path' => $nf['xml_path'],
        'veiculo_id' => $veiculo_id,
        'estado' => 'IMPORTADO',
        'numero_emissao' => 0,
        'xml_importado' => 1,
        'categoria_conta_id' => $nf['categoria_conta_id'] ?? null,
        'chave' => $nf['chave'],
        'empresa_id' => $this->empresa_id,
        'vbc_icms' => $this->parseMoeda($nf['vbc_icms'] ?? 0),
        'v_icms'   => $this->parseMoeda($nf['v_icms'] ?? 0),
        'v_ipi'    => $this->parseMoeda($nf['v_ipi'] ?? 0),
        'v_pis'    => $this->parseMoeda($nf['v_pis'] ?? 0),
        'v_cofins' => $this->parseMoeda($nf['v_cofins'] ?? 0),
        'filial_id' => (isset($nf['filial_id']) && $nf['filial_id'] != -1) ? $nf['filial_id'] : null
    ]);

    return response()->json($result);
}

    public function salvarItem(Request $request)
    {
        $prod = (array) $request->input('produto', []);

        $compraId = (int) ($prod['compra_id'] ?? 0);
        $produtoId = (int) ($prod['produto_id'] ?? 0);

        $compra = Compra::where('empresa_id', $this->empresa_id)->find($compraId);
        $produtoBD = Produto::where('empresa_id', $this->empresa_id)->find($produtoId);

        if (!$compra || !$produtoBD) {
            return response()->json([
                'message' => 'Compra ou produto não encontrado para a empresa atual.',
            ], 422);
        }

        $quantidadeFinal = $this->parseMoeda($prod['quantidade'] ?? 0);
        $custoUnitario = $this->parseMoeda($prod['valor'] ?? 0);

        if ($quantidadeFinal <= 0 || $custoUnitario < 0) {
            return response()->json([
                'message' => 'Quantidade ou custo unitário inválido.',
            ], 422);
        }

        return DB::transaction(function () use ($prod, $compra, $produtoBD, $quantidadeFinal, $custoUnitario) {
            $cfopOriginal = preg_replace('/[^0-9]/', '', (string) ($prod['cfop'] ?? ''));
            $cfopTela = preg_replace('/[^0-9]/', '', (string) ($prod['cfop_entrada'] ?? ''));
            $finalidade = (string) ($prod['finalidade'] ?? 'revenda');
            $interestadual = str_starts_with($cfopOriginal, '6') || str_starts_with($cfopTela, '2');
            $cstIcms = str_pad(preg_replace('/[^0-9]/', '', (string) ($prod['cst_icms'] ?? '90')), 3, '0', STR_PAD_LEFT);

            if ($finalidade === 'uso_consumo_sem_credito') {
                if (in_array($cfopOriginal, ['5405', '5403', '6403', '6404'], true)) {
                    $cfopEntrada = $interestadual ? '2407' : '1407';
                    $cstIcms = '060';
                } else {
                    $cfopEntrada = $interestadual ? '2556' : '1556';
                    $cstIcms = '090';
                }
                $prod['cst_pis'] = '70';
                $prod['cst_cofins'] = '70';
            } elseif ($finalidade === 'uso_consumo_com_credito') {
                if (in_array($cfopTela, ['1652', '1653', '2652', '2653'], true)) {
                    $cfopEntrada = $cfopTela;
                } elseif (in_array($cfopOriginal, ['5405', '5403', '6403', '6404'], true)) {
                    $cfopEntrada = $interestadual ? '2407' : '1407';
                    $cstIcms = '060';
                } else {
                    $cfopEntrada = $interestadual ? '2556' : '1556';
                    $cstIcms = '090';
                }
                $prod['cst_pis'] = '50';
                $prod['cst_cofins'] = '50';
            } elseif ($finalidade === 'imobilizado') {
                $cfopEntrada = $interestadual ? '2551' : '1551';
                $cstIcms = '090';
                $prod['cst_pis'] = '70';
                $prod['cst_cofins'] = '70';
            } else {
                if (strlen($cfopTela) === 4) {
                    $cfopEntrada = $cfopTela;
                } elseif ($cfopOriginal === '5102') {
                    $cfopEntrada = '1102';
                } elseif ($cfopOriginal === '6102') {
                    $cfopEntrada = '2102';
                } else {
                    $cfopEntrada = preg_replace('/[^0-9]/', '', (string) $this->getCfopEntrada($cfopOriginal));
                }

                if (strlen($cfopEntrada) !== 4) {
                    $cfopEntrada = $interestadual ? '2102' : '1102';
                }
            }

            $subtotalItem = round($quantidadeFinal * $custoUnitario, 2);
            $cstPis = str_pad(preg_replace('/[^0-9]/', '', (string) ($prod['cst_pis'] ?? '70')), 2, '0', STR_PAD_LEFT);
            $cstCofins = str_pad(preg_replace('/[^0-9]/', '', (string) ($prod['cst_cofins'] ?? '70')), 2, '0', STR_PAD_LEFT);

            $vbcPis = $this->parseMoeda($prod['vbc_pis'] ?? 0);
            $pPis = $this->parseMoeda($prod['p_pis'] ?? 0);
            $vPis = $this->parseMoeda($prod['v_pis'] ?? 0);
            $vbcCofins = $this->parseMoeda($prod['vbc_cofins'] ?? 0);
            $pCofins = $this->parseMoeda($prod['p_cofins'] ?? 0);
            $vCofins = $this->parseMoeda($prod['v_cofins'] ?? 0);

            if ($cstPis === '50' && $vPis <= 0) {
                $vbcPis = $subtotalItem;
                $pPis = 1.65;
                $vPis = round($subtotalItem * 0.0165, 2);
            }
            if ($cstCofins === '50' && $vCofins <= 0) {
                $vbcCofins = $subtotalItem;
                $pCofins = 7.60;
                $vCofins = round($subtotalItem * 0.076, 2);
            }

            $tipoItem = in_array($cfopEntrada, ['1556', '2556', '1407', '2407', '1652', '1653', '2652', '2653'], true)
                ? '07'
                : (in_array($cfopEntrada, ['1551', '2551'], true) ? '08' : '00');

            $produtoBD->tipo_item = $tipoItem;
            if (str_starts_with($cfopEntrada, '1')) {
                $produtoBD->CFOP_entrada_estadual = $cfopEntrada;
            } else {
                $produtoBD->CFOP_entrada_inter_estadual = $cfopEntrada;
            }
            $produtoBD->CST_CSOSN_entrada = $cstIcms;
            $produtoBD->save();

            $result = ItemCompra::create([
                'compra_id' => $compra->id,
                'produto_id' => $produtoBD->id,
                'quantidade' => $quantidadeFinal,
                'valor_unitario' => $custoUnitario,
                'unidade_compra' => $prod['unidade'] ?? 'UN',
                'cfop_entrada' => $cfopEntrada,
                'cst_icms' => $cstIcms,
                'vbc_icms' => $this->parseMoeda($prod['vbc_icms'] ?? 0),
                'p_icms' => $this->parseMoeda($prod['p_icms'] ?? 0),
                'v_icms' => $this->parseMoeda($prod['v_icms'] ?? 0),
                'cst_pis' => $cstPis,
                'vbc_pis' => $vbcPis,
                'p_pis' => $pPis,
                'v_pis' => $vPis,
                'cst_cofins' => $cstCofins,
                'vbc_cofins' => $vbcCofins,
                'p_cofins' => $pCofins,
                'v_cofins' => $vCofins,
                'cst_ibs_cbs' => $prod['cst_ibs_cbs'] ?? null,
                'class_trib_ibs_cbs' => $prod['class_trib_ibs_cbs'] ?? $prod['class_trib'] ?? null,
                'bc_ibs_cbs' => $this->parseMoeda($prod['bc_ibs_cbs'] ?? 0),
                'aliq_ibs_uf' => $this->parseMoeda($prod['aliq_ibs'] ?? 0),
                'aliq_cbs' => $this->parseMoeda($prod['aliq_cbs'] ?? 0),
                'valor_ibs' => $this->parseMoeda($prod['valor_ibs'] ?? 0),
                'valor_cbs' => $this->parseMoeda($prod['valor_cbs'] ?? 0),
            ]);

            if ((int) $produtoBD->gerenciar_estoque === 1) {
                $conversao = max((float) ($produtoBD->conversao_unitaria ?? 1), 0.0000001);
                $filialId = ((int) ($compra->filial_id ?? 0)) > 0 ? (int) $compra->filial_id : null;

                (new StockMove())->pluStock(
                    $produtoBD->id,
                    $quantidadeFinal * $conversao,
                    $custoUnitario,
                    $filialId,
                    'compra',
                    $compra->id,
                    $compra->data_retroativa ?? $compra->data_emissao ?? now()
                );
            }

            $codigoFornecedor = trim((string) ($prod['codigo'] ?? ''));
            if ($codigoFornecedor !== '' && $compra->fornecedor_id) {
                DB::table('produto_fornecedors')->updateOrInsert(
                    [
                        'empresa_id' => $this->empresa_id,
                        'produto_id' => $produtoBD->id,
                        'fornecedor_id' => $compra->fornecedor_id,
                        'codigo_fornecedor' => $codigoFornecedor,
                    ],
                    [
                        'filial_id' => ((int) ($compra->filial_id ?? 0)) > 0 ? (int) $compra->filial_id : null,
                        'usuario_id' => $this->usuario_id,
                        'descricao_fornecedor' => trim((string) ($prod['xProd'] ?? '')),
                        'codigo_barras_fornecedor' => trim((string) ($prod['codBarras'] ?? '')),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            return response()->json($result);
        }, 3);
    }

    public function salvarParcela(Request $request)
    {
        $parcela = (array) $request->input('parcela', []);
        $compraId = (int) ($parcela['compra_id'] ?? 0);
        $compra = Compra::where('empresa_id', $this->empresa_id)->find($compraId);

        if (!$compra) {
            return response()->json(['message' => 'Compra não encontrada para a empresa atual.'], 404);
        }

        $valorParcela = $this->parseMoeda($parcela['valor_parcela'] ?? 0);
        if ($valorParcela <= 0) {
            return response()->json(['message' => 'O valor da parcela deve ser maior que zero.'], 422);
        }

        $formaPagamento = strtolower(trim((string) ($parcela['forma_pagamento'] ?? '')));
        $isAdiantamento = $formaPagamento === 'adiantamento';
        $contaEmpresaId = !empty($parcela['conta_empresa_id']) ? (int) $parcela['conta_empresa_id'] : null;
        $veiculoId = !empty($parcela['veiculo_id']) ? (int) $parcela['veiculo_id'] : ($compra->veiculo_id ?? null);
        $dataVencimento = $this->parseDate($parcela['vencimento'] ?? null);
        $dataEmissao = $compra->data_retroativa ?? $compra->data_emissao ?? now()->toDateString();

        try {
            $resultado = DB::transaction(function () use (
                $compra,
                $valorParcela,
                $formaPagamento,
                $isAdiantamento,
                $contaEmpresaId,
                $veiculoId,
                $dataVencimento,
                $dataEmissao
            ) {
                $categoriaNome = '';
                if ($compra->categoria_conta_id) {
                    $categoriaNome = (string) DB::table('categoria_contas')
                        ->where('empresa_id', $this->empresa_id)
                        ->where('id', $compra->categoria_conta_id)
                        ->value('nome');
                }

                $referencia = trim(sprintf(
                    'Ref. Compra %s NFe %s',
                    $categoriaNome,
                    (string) ($compra->nf ?? '')
                ));

                $criarTitulo = function (float $valor, bool $pago, string $tipo, ?int $contaId = null) use (
                    $compra,
                    $dataVencimento,
                    $dataEmissao,
                    $veiculoId,
                    $referencia
                ): ContaPagar {
                    return ContaPagar::create([
                        'compra_id' => $compra->id,
                        'fornecedor_id' => $compra->fornecedor_id,
                        'numero_nota_fiscal' => $compra->nf ?? '',
                        'nf' => $compra->nf ?? '',
                        'data_vencimento' => $dataVencimento,
                        'data_emissao' => $dataEmissao,
                        'data_emissao_nfe' => $dataEmissao,
                        'data_pagamento' => $pago ? $dataEmissao : null,
                        'valor_integral' => $valor,
                        'valor_original' => $valor,
                        'valor_pago' => $pago ? $valor : 0,
                        'status' => $pago,
                        'tipo_pagamento' => $tipo,
                        'empresa_id' => $this->empresa_id,
                        'usuario_id' => $this->usuario_id,
                        'usuario_baixa_id' => $pago ? $this->usuario_id : null,
                        'filial_id' => ((int) ($compra->filial_id ?? 0)) > 0 ? (int) $compra->filial_id : null,
                        'veiculo_id' => $veiculoId,
                        'categoria_id' => $compra->categoria_conta_id,
                        'conta_id_origem' => $contaId,
                        'referencia' => $referencia,
                    ]);
                };

                if ($isAdiantamento) {
                    $saldoAdiantamento = (float) Adiantamento::where('empresa_id', $this->empresa_id)
                        ->where('fornecedor_id', $compra->fornecedor_id)
                        ->where('status', 'aberto')
                        ->lockForUpdate()
                        ->sum(DB::raw('valor_total - valor_utilizado'));

                    if ($saldoAdiantamento <= 0) {
                        throw new \RuntimeException('O fornecedor não possui saldo de adiantamento disponível.');
                    }

                    $valorAdiantamento = min($saldoAdiantamento, $valorParcela);
                    $tituloPago = $criarTitulo($valorAdiantamento, true, 'adiantamento');

                    AdiantamentoController::baixarAdiantamento(
                        $compra->fornecedor_id,
                        'fornecedor',
                        $valorAdiantamento,
                        $this->empresa_id,
                        $tituloPago->id,
                        $this->usuario_id,
                        ((int) ($compra->filial_id ?? 0)) > 0 ? (int) $compra->filial_id : null
                    );

                    $titulos = [$tituloPago];
                    $restante = round($valorParcela - $valorAdiantamento, 2);
                    if ($restante > 0) {
                        $titulos[] = $criarTitulo($restante, false, 'boleto');
                    }

                    return $titulos;
                }

                $baixarImediatamente = $contaEmpresaId !== null;
                $titulo = $criarTitulo(
                    $valorParcela,
                    $baixarImediatamente,
                    $formaPagamento !== '' ? $formaPagamento : 'boleto',
                    $contaEmpresaId
                );

                if ($baixarImediatamente) {
                    $conta = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)
                        ->where('status', 1)
                        ->lockForUpdate()
                        ->find($contaEmpresaId);

                    if (!$conta) {
                        throw new \RuntimeException('Conta bancária inválida ou inativa para a empresa atual.');
                    }

                    $itemConta = \App\Models\ItemContaEmpresa::create([
                        'conta_id' => $conta->id,
                        'descricao' => trim('Pagamento NF ' . ($compra->nf ?? '') . ($categoriaNome !== '' ? " ({$categoriaNome})" : '')),
                        'valor' => $valorParcela,
                        'tipo_pagamento' => $formaPagamento,
                        'tipo' => 'saida',
                        'data_pagamento' => $dataEmissao,
                        'user_id' => $this->usuario_id,
                        'empresa_id' => $this->empresa_id,
                        'origem' => 'Conta Pagar',
                        'conta_pagar_id' => $titulo->id,
                        'categoria_id' => $compra->categoria_conta_id,
                    ]);

                    app(\App\Utils\ContaEmpresaUtil::class)->atualizaSaldo($itemConta);
                }

                return [$titulo];
            }, 3);

            return response()->json(count($resultado) === 1 ? $resultado[0] : $resultado);
        } catch (\Throwable $e) {
            \Log::error('Erro ao salvar parcela da compra fiscal', [
                'empresa_id' => $this->empresa_id,
                'compra_id' => $compraId,
                'erro' => $e->getMessage(),
            ]);

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function parseDate($date){
        if(strpos($date, "/") !== false){
            $d = explode("/", $date);
            if(strlen($d[2]) == 4){ 
                return $d[2] . "-" . $d[1] . "-" . $d[0];
            }
        }
        return $date;
    }
    
  
  	private function aplicarRegrasFiscais($itemXml, $cfopEntrada)
    {
        // 1. Regra CST ICMS (Sugestão)
        $cstIcmsXml = (string)($itemXml->imposto->ICMS->children()[0]->CST ?? $itemXml->imposto->ICMS->children()[0]->CSOSN ?? '00');
        $cstIcmsSugerido = $cstIcmsXml;
        if ($cstIcmsXml == '010' || $cstIcmsXml == '060') {
            $cstIcmsSugerido = '060';
        }

        // 2. Regra CST PIS e COFINS (Sugestão baseada em CFOP)
        $cstPisXml = (string)($itemXml->imposto->PIS->children()[0]->CST ?? '01');
        $cstPisSugerido = '70'; // Padrão "Outros"

        $cfopsCreditoEspecial = ['5656', '5651', '5653', '1653', '2653'];
        $cfopsCompraRevenda = ['1102', '2102'];

        if (in_array($cstPisXml, ['04', '01'])) {
            if (in_array($cfopEntrada, $cfopsCreditoEspecial) || in_array((string)$itemXml->prod->CFOP, $cfopsCreditoEspecial)) {
                $cstPisSugerido = '50'; // Operação com Direito a Crédito
            } elseif (in_array($cfopEntrada, $cfopsCompraRevenda)) {
                $cstPisSugerido = '50';
            }
        } elseif ($cstPisXml == '08') {
            $cstPisSugerido = '74';
        } elseif ($cstPisXml == '09') {
            $cstPisSugerido = '72';
        }

        return [
            'cst_icms' => $cstIcmsSugerido,
            'cst_pis' => $cstPisSugerido,
            'cst_cofins' => $cstPisSugerido, // Normalmente seguem a mesma regra
        ];
    }
	
  
  	private function parseMoeda($valor)
{
    if (empty($valor)) return 0;
    
    $valorStr = (string)$valor;
    // Remove qualquer coisa que não seja número, ponto ou vírgula (como R$, espaços, letras)
    $valorStr = preg_replace('/[^0-9.,]/', '', $valorStr);

    // Se a string tem vírgula E ponto (ex: 2.152,00 ou 2,152.00)
    if (strpos($valorStr, ',') !== false && strpos($valorStr, '.') !== false) {
        $ultimaVirgula = strrpos($valorStr, ',');
        $ultimoPonto = strrpos($valorStr, '.');
        
        if ($ultimaVirgula > $ultimoPonto) {
            // É formato BR (2.152,00) -> Remove os pontos e troca a vírgula por ponto
            $valorStr = str_replace('.', '', $valorStr);
            $valorStr = str_replace(',', '.', $valorStr);
        } else {
            // É formato US (2,152.00) -> Remove as vírgulas
            $valorStr = str_replace(',', '', $valorStr);
        }
    } 
    // Se tem apenas vírgula (ex: 2152,00 ou 2,69)
    elseif (strpos($valorStr, ',') !== false) {
        $valorStr = str_replace(',', '.', $valorStr);
    }
    // Se tem múltiplos pontos por erro da máscara JS (ex: 2.152.00)
    elseif (substr_count($valorStr, '.') > 1) {
        $ultimoPonto = strrpos($valorStr, '.');
        // Pega tudo antes do último ponto, tira os pontos, e junta com os centavos
        $parteInteira = str_replace('.', '', substr($valorStr, 0, $ultimoPonto));
        $parteDecimal = substr($valorStr, $ultimoPonto + 1);
        $valorStr = $parteInteira . '.' . $parteDecimal;
    }

    return (float)$valorStr;
}

}
