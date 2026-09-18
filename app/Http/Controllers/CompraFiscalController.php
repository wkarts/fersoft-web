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
use App\Services\FiscalImportService;

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

                    // Extração estruturada de impostos reais com Origem (3 Dígitos) unificado
                    $cst_icms = '';
                    if(isset($item->imposto->ICMS)){
                        $icms_arr = (array)$item->imposto->ICMS;
                        $icms_key = array_key_first($icms_arr); 
                        if($icms_key){
                            $origem = isset($item->imposto->ICMS->$icms_key->orig) ? (string)$item->imposto->ICMS->$icms_key->orig : '0';
                            if(isset($item->imposto->ICMS->$icms_key->CST)) {
                                $cst_icms = $origem . str_pad((string)$item->imposto->ICMS->$icms_key->CST, 2, '0', STR_PAD_LEFT);
                            } elseif(isset($item->imposto->ICMS->$icms_key->CSOSN)) {
                                $cst_icms = $origem . str_pad((string)$item->imposto->ICMS->$icms_key->CSOSN, 2, '0', STR_PAD_LEFT);
                            }
                        }
                    }
                    if(empty($cst_icms)) {
                        $cst_icms = '090';
                    }

                    $vbc_icms = (float)($item->imposto->ICMS->children()[0]->vBC ?? 0);
                    $p_icms = (float)($item->imposto->ICMS->children()[0]->pICMS ?? 0);
                    $v_icms = (float)($item->imposto->ICMS->children()[0]->vICMS ?? 0);

                    // EXTRAÇÃO IBS E CBS DA REFORMA TRIBUTÁRIA
                    $cst_ibs_cbs = '';
                    $bc_ibs_cbs = 0;
                    $aliq_ibs = 0;
                    $valor_ibs = 0;
                    $aliq_cbs = 0;
                    $valor_cbs = 0;
                    $class_trib = '';

                    if (isset($item->imposto->IBSCBS)) {
                        $ibscbs = $item->imposto->IBSCBS;
                        $cst_ibs_cbs = (string)($ibscbs->CST ?? '');
                        $class_trib = (string)($ibscbs->cClassTrib ?? '');

                        // 1. Tenta ler como Operação Normal (Ex: Item 1 - ARLA)
                        if (isset($ibscbs->gIBSCBS)) {
                            $bc_ibs_cbs = (float)($ibscbs->gIBSCBS->vBC ?? 0);
                            $aliq_ibs = (float)($ibscbs->gIBSCBS->gIBSUF->pIBSUF ?? 0);
                            $valor_ibs = (float)($ibscbs->gIBSCBS->vIBS ?? 0);
                            $aliq_cbs = (float)($ibscbs->gIBSCBS->gCBS->pCBS ?? 0);
                            $valor_cbs = (float)($ibscbs->gIBSCBS->gCBS->vCBS ?? 0);
                        }
                        // 2. Tenta ler como Operação Monofásica (Ex: Item 2 - Diesel Combustível)
                        elseif (isset($ibscbs->gIBSCBSMono)) {
                            $valor_ibs = (float)($ibscbs->gIBSCBSMono->vTotIBSMonoItem ?? 0);
                            $valor_cbs = (float)($ibscbs->gIBSCBSMono->vTotCBSMonoItem ?? 0);
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
                        'bc_ibs_cbs'  => $bc_ibs_cbs,
                        'aliq_ibs'    => $aliq_ibs,
                        'valor_ibs'   => $valor_ibs,
                        'aliq_cbs'    => $aliq_cbs,
                        'valor_cbs'   => $valor_cbs,
                        'class_trib'  => $class_trib
					];

					array_push($itens, $itemAdd);
				}

                // Captura de Informações Complementares da Nota Fiscal
                $infCpl = '';
                if (isset($xml->NFe->infNFe->infAdic->infCpl)) {
                    $infCpl = (string) $xml->NFe->infNFe->infAdic->infCpl;
                }

				$chave = substr($xml->NFe->infNFe->attributes()->Id, 3, 44);
				$dadosNf = [
                    'chave' => $chave,
                    'vProd' => (float)$xml->NFe->infNFe->total->ICMSTot->vProd, // Adicionado vProd corretamente
                    'vNF' => (float)$xml->NFe->infNFe->total->ICMSTot->vNF,     // <-- ESSA É A LINHA QUE FALTAVA
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
                    'infCpl' => $infCpl ?? ''
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
              	$contasEmpresa = \App\Models\ContaEmpresa::where('empresa_id', $this->empresa_id)->where('status', 1)->get();

				return view('compraFiscal/visualizaNota')
				->with('title', 'Nota Fiscal')
				->with('itens', $itens)
                ->with('saldo_credito', $saldo_credito)
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
            'valor' => $this->parseMoeda($nf['valor_nf'] ?? 0),
            'desconto' => $this->parseMoeda($nf['desconto']?? 0),
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

    public function salvarItem(Request $request){
        $prod = $request->produto;

        \Log::info("=== DADOS DO ITEM RECEBIDOS DA TELA ===", is_array($prod) ? $prod : []);

        $compra = Compra::find($prod['compra_id']);

        $cfopEntrada = $prod['cfop_entrada'] ?? '';
        $cstIcms = str_pad($prod['cst_icms'] ?? '000', 3, '0', STR_PAD_LEFT);

        $quantidadeFinal = $this->parseMoeda($prod['quantidade'] ?? 0);
        $custoUnitario = $this->parseMoeda($prod['valor'] ?? 0);
        $subtotalItem = $quantidadeFinal * $custoUnitario;  

        // 1. INTELIGÊNCIA FISCAL CONSOLIDAÇÃO DE DE/PARA BASEADO NA FINALIDADE
        $cfopOriginal = preg_replace('/[^0-9]/', '', $prod['cfop'] ?? '');
        $finalidade = $prod['finalidade'] ?? 'revenda';
        $isInterestadual = (substr($cfopOriginal, 0, 1) == '6' || substr($prod['cfop_entrada'] ?? '', 0, 1) == '2');

        if ($finalidade == 'uso_consumo_sem_credito') {
            if (in_array($cfopOriginal, ['5405', '5403', '6403', '6404'])) {
                $cfopEntrada = $isInterestadual ? '2407' : '1407';
                $cstIcms = '060';
            } else {
                $cfopEntrada = $isInterestadual ? '2556' : '1556';
                $cstIcms = '090';
            }
            $prod['cst_pis'] = '70';
            $prod['cst_cofins'] = '70';
        } elseif ($finalidade == 'uso_consumo_com_credito') {
            
            // NOVA REGRA: Verifica se a tela enviou o CFOP de Combustível que a IA configurou
            $cfopTela = preg_replace('/[^0-9]/', '', $prod['cfop_entrada'] ?? '');
            
            if (in_array($cfopTela, ['1653', '2653', '1652', '2652'])) {
                // Respeita o que a IA mandou da tela!
                $cfopEntrada = $cfopTela;
                $cstIcms = str_pad($prod['cst_icms'] ?? '000', 3, '0', STR_PAD_LEFT);
            } else {
                // Lógica antiga (padrão) se não for combustível
                if (in_array($cfopOriginal, ['5405', '5403', '6403', '6404'])) {
                    $cfopEntrada = $isInterestadual ? '2407' : '1407';
                    $cstIcms = '060';
                } else {
                    $cfopEntrada = $isInterestadual ? '2556' : '1556';
                    $cstIcms = '090';
                }
            }
            
            // Força o crédito de PIS/COFINS
            $prod['cst_pis'] = '50';
            $prod['cst_cofins'] = '50';

        
        } elseif ($finalidade == 'imobilizado') {
            $cfopEntrada = $isInterestadual ? '2551' : '1551';
            $cstIcms = '090';
            $prod['cst_pis'] = '70';
            $prod['cst_cofins'] = '70';
        } else {
            // Revenda padrão respeitando 5102 -> 1102, 6102 -> 2102
            $cfopXml = preg_replace('/[^0-9]/', '', $prod['cfop_entrada'] ?? '');
            if (!empty($cfopXml) && strlen($cfopXml) == 4) {
                $cfopEntrada = $cfopXml;
            } else {
                if ($cfopOriginal == '5102') $cfopEntrada = '1102';
                elseif ($cfopOriginal == '6102') $cfopEntrada = '2102';
                else $cfopEntrada = $this->getCfopEntrada($cfopOriginal);
                if(empty($cfopEntrada) || strlen($cfopEntrada) < 4) { $cfopEntrada = '2102'; } 
            }
            $cstIcms = str_pad($prod['cst_icms'] ?? '000', 3, '0', STR_PAD_LEFT);
        }

        // --- NOVA INTELIGÊNCIA FISCAL: FORÇAR CRÉDITO PIS/COFINS ---
        $cstPis = $prod['cst_pis'] ?? '70';
        $cstCofins = $prod['cst_cofins'] ?? '70';

        $vbc_pis = $this->parseMoeda($prod['vbc_pis'] ?? 0);
        $p_pis = $this->parseMoeda($prod['p_pis'] ?? 0);
        $v_pis = $this->parseMoeda($prod['v_pis'] ?? 0);

        $vbc_cofins = $this->parseMoeda($prod['vbc_cofins'] ?? 0);
        $p_cofins = $this->parseMoeda($prod['p_cofins'] ?? 0);
        $v_cofins = $this->parseMoeda($prod['v_cofins'] ?? 0);

        // Se o CST indicar crédito (ex: 50) e o XML trouxe zerado, calcula automaticamente (Lucro Real: 1.65% e 7.6%)
        if ($cstPis == '50' && $v_pis == 0) {
            $vbc_pis = $subtotalItem;
            $p_pis = 1.65;
            $v_pis = round($subtotalItem * (1.65 / 100), 2);
        }
        if ($cstCofins == '50' && $v_cofins == 0) {
            $vbc_cofins = $subtotalItem;
            $p_cofins = 7.60;
            $v_cofins = round($subtotalItem * (7.60 / 100), 2);
        }

        // 2. CONVERSÃO SEGURA DE VALORES
        $tipoItem = in_array($cfopEntrada, ['1556', '2556', '1407', '2407']) ? '07' : (in_array($cfopEntrada, ['1551', '2551']) ? '08' : '00');

        $produtoBD = Produto::where('id', (int) ($prod['produto_id'] ?? 0))->where('empresa_id', $this->empresa_id)->first();

        if ($produtoBD) {
            $produtoBD->tipo_item = $tipoItem; 
            if(substr($cfopEntrada, 0, 1) == '1') { $produtoBD->CFOP_entrada_estadual = $cfopEntrada; } 
            else { $produtoBD->CFOP_entrada_inter_estadual = $cfopEntrada; }
            $produtoBD->CST_CSOSN_entrada = $cstIcms; 
            $produtoBD->save();
        }

        // 4. CRIA O ITEM DA COMPRA (Agora salvando PIS/COFINS inteligentes)
        $result = ItemCompra::create([
            'compra_id' => (int) $prod['compra_id'],
            'produto_id' => (int) $prod['produto_id'],
            'quantidade' => $quantidadeFinal,
            'valor_unitario' => $custoUnitario,
            'unidade_compra' => $prod['unidade'] ?? 'UN',
            'cfop_entrada' => $cfopEntrada,

            // ICMS garantindo as 3 casas
            'cst_icms' => $cstIcms,
            'vbc_icms' => $this->parseMoeda($prod['vbc_icms'] ?? 0),
            'p_icms' => $this->parseMoeda($prod['p_icms'] ?? 0),
            'v_icms' => $this->parseMoeda($prod['v_icms'] ?? 0),

            // PIS/COFINS (Inteligentes)
            'cst_pis' => $cstPis,
            'vbc_pis' => $vbc_pis,
            'p_pis' => $p_pis,
            'v_pis' => $v_pis,
            'cst_cofins' => $cstCofins,
            'vbc_cofins' => $vbc_cofins,
            'p_cofins' => $p_cofins,
            'v_cofins' => $v_cofins,

            // Reforma Tributária
            'cst_ibs_cbs'    => $prod['cst_ibs_cbs'] ?? null,
            'bc_ibs_cbs'     => $this->parseMoeda($prod['bc_ibs_cbs'] ?? 0),
            'aliq_ibs_uf'    => $this->parseMoeda($prod['aliq_ibs'] ?? 0),
            'aliq_cbs'       => $this->parseMoeda($prod['aliq_cbs'] ?? 0),
            'valor_ibs'      => $this->parseMoeda($prod['valor_ibs'] ?? 0),
            'valor_cbs'      => $this->parseMoeda($prod['valor_cbs'] ?? 0),
            'class_trib_ibs_cbs' => $prod['class_trib_ibs_cbs'] ?? null
        ]);

        // 5. MOVIMENTAÇÃO DE ESTOQUE CORRIGIDA
        if($produtoBD && $produtoBD->gerenciar_estoque == 1){
            $conversao = (float)($produtoBD->conversao_unitaria ?? 1);
            $qtdFinalMovimentacao = $quantidadeFinal * $conversao;

            $stockMove = new StockMove();
            $filial = $compra->filial_id > 0 ? $compra->filial_id : null;

            $stockMove->pluStock(
                $produtoBD->id, 
                $qtdFinalMovimentacao, 
                $custoUnitario, 
                $filial, 
                'compra', 
                $compra->id, 
                $compra->data_emissao
            );
        }

        // 6. SALVA O VÍNCULO PRODUTO x FORNECEDOR EM MEMÓRIA (Referência produto fornecedor)
        if ((int)$prod['produto_id'] > 0 && $compra && $compra->fornecedor_id > 0) {
            \DB::table('produto_fornecedors')->updateOrInsert(
                [
                    'produto_id' => $prod['produto_id'],
                    'fornecedor_id' => $compra->fornecedor_id,
                    'codigo_fornecedor' => preg_replace('/[^a-zA-Z0-9]/', '', $prod['codigo'] ?? '')
                ],
                [
                    'empresa_id' => $this->empresa_id,
                    'descricao_fornecedor' => $prod['xProd'] ?? '',
                    'codigo_barras_fornecedor' => $prod['codBarras'] ?? '',
                    'usuario_id' => $this->usuario_id,
                    'updated_at' => now()
                ]
            );
        }

        return response()->json($result);
    }

    public function salvarParcela(Request $request){
    $parcela = $request->parcela;
    $compra = Compra::find($parcela['compra_id']);

    $valor = $parcela['valor_parcela'];
    if (strpos($valor, ',') !== false) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    }
    $valorParcelaFloat = (float)$valor;

    $formaPagamento = strtolower($parcela['forma_pagamento'] ?? '');
    $isAdiantamento = ($formaPagamento === 'adiantamento');
    $contaEmpresaId = !empty($parcela['conta_empresa_id']) ? $parcela['conta_empresa_id'] : null;

    $veiculoIdParcela = !empty($parcela['veiculo_id']) ? $parcela['veiculo_id'] : ($compra ? $compra->veiculo_id : null);

    $statusPaga = ($isAdiantamento || $contaEmpresaId != null) ? 1 : 0;
    $dataPagamento = $statusPaga ? ($compra ? $compra->data_emissao : date('Y-m-d')) : null;
    $valorPago = $statusPaga ? $valorParcelaFloat : 0;

    // 1. MONTA A REFERÊNCIA PERFEITA DO CONTAS A PAGAR
    $nomeCategoria = '';
    if ($compra && $compra->categoria_conta_id) {
        $catDB = \DB::table('categoria_contas')->where('id', $compra->categoria_conta_id)->first();
        if ($catDB) $nomeCategoria = $catDB->nome;
    }
    $referencia = "Ref. Compra " . $nomeCategoria . " NFe " . ($compra ? $compra->nf : '');

    // BLOCO ADIANTAMENTO
    if ($isAdiantamento) {
        $saldoAdiantamento = Adiantamento::where('empresa_id', $this->empresa_id)
            ->where('fornecedor_id', $compra->fornecedor_id)
            ->where('status', 'aberto')
            ->sum(\DB::raw('valor_total - valor_utilizado'));

        if ($saldoAdiantamento > 0 && $saldoAdiantamento < $valorParcelaFloat) {
            $valorRestante = $valorParcelaFloat - $saldoAdiantamento;

            $resPaga = ContaPagar::create([
                'compra_id' => $parcela['compra_id'], 
                'fornecedor_id' => $compra->fornecedor_id,
              	'numero_nota_fiscal' => $compra ? $compra->nf : '',
                'data_vencimento' => $this->parseDate($parcela['vencimento']), 
                'data_emissao' => $compra->data_emissao, 
                'data_emissao_nfe' => $compra->data_emissao,
                'valor_integral' => $saldoAdiantamento, 
                'valor_pago' => $saldoAdiantamento, 
                'status' => 1, 
                'data_pagamento' => $compra ? $compra->data_emissao : date('Y-m-d'),
                'tipo_pagamento' => 'adiantamento', 
                'empresa_id' => $this->empresa_id, 
                'usuario_id' => $this->usuario_id, 
                'usuario_baixa_id' => $this->usuario_id,
                'filial_id' => $compra ? $compra->filial_id : null, 
                'veiculo_id' => $veiculoIdParcela,
                'categoria_id' => $compra ? $compra->categoria_conta_id : null,
                'referencia' => $referencia // <--- REFERÊNCIA AQUI
            ]);

            \App\Http\Controllers\AdiantamentoController::baixarAdiantamento($compra->fornecedor_id, 'fornecedor', $saldoAdiantamento, $this->empresa_id, $resPaga->id, $this->usuario_id, $this->filial_id);

            return response()->json(ContaPagar::create([
                'compra_id' => $parcela['compra_id'], 
                'fornecedor_id' => $compra->fornecedor_id, 
              	'numero_nota_fiscal' => $compra ? $compra->nf : '',
                'data_vencimento' => $this->parseDate($parcela['vencimento']), 
                'data_emissao' => $compra->data_emissao, 
                'data_emissao_nfe' => $compra->data_emissao,
                'valor_integral' => $valorRestante,
              	'valor_original' => $valorRestante,
                'valor_pago' => 0, 
                'status' => 0, 
                'tipo_pagamento' => 'boleto', 
                'empresa_id' => $this->empresa_id,
                'usuario_id' => $this->usuario_id, 
                'filial_id' => $compra ? $compra->filial_id : null, 
                'categoria_id' => $compra ? $compra->categoria_conta_id : null,
                'veiculo_id' => $veiculoIdParcela, 
                'conta_empresa_id' => $contaEmpresaId,
                'referencia' => $referencia // <--- REFERÊNCIA AQUI
            ]));
        }
    }

    // CRIAÇÃO DO CONTAS A PAGAR PRINCIPAL
    $result = ContaPagar::create([
        'compra_id' => $parcela['compra_id'],
        'fornecedor_id' => $compra ? $compra->fornecedor_id : null,
      	'numero_nota_fiscal' => $compra ? $compra->nf : '',
        'data_vencimento' => $this->parseDate($parcela['vencimento']),
        'data_emissao' => $compra ? $compra->data_emissao : date('Y-m-d'),
        'data_emissao_nfe' => $compra ? $compra->data_emissao : date('Y-m-d'),
        'valor_integral' => $valorParcelaFloat,
      	'valor_original' => $valorParcelaFloat,
        'valor_pago' => $valorPago,
        'data_pagamento' => $compra ? $compra->data_emissao : date('Y-m-d'),
        'status' => $statusPaga,
        'tipo_pagamento' => $formaPagamento,
        'empresa_id' => $this->empresa_id,
        'usuario_id' => $this->usuario_id,
        'filial_id' => $compra ? $compra->filial_id : null,
        'veiculo_id' => $veiculoIdParcela,
        'categoria_id' => $compra ? $compra->categoria_conta_id : null,
        'conta_empresa_id' => $contaEmpresaId,
        'usuario_baixa_id' => $statusPaga ? $this->usuario_id : null,
        'referencia' => $referencia // <--- REFERÊNCIA AQUI
    ]);

    // LÓGICA DE BAIXAS
    if($isAdiantamento) {
        \App\Http\Controllers\AdiantamentoController::baixarAdiantamento($compra->fornecedor_id, 'fornecedor', $valorParcelaFloat, $this->empresa_id, $result->id, $this->usuario_id, $this->filial_id);
    } 
    elseif ($statusPaga && $contaEmpresaId) {
        // BUSCA A CONTA BANCÁRIA PARA SUBTRAIR E GRAVAR O SALDO
        $contaBancaria = \DB::table('conta_empresas')->where('id', $contaEmpresaId)->first();
        
        if ($contaBancaria) {
            // A matemática do caixa: Saldo Antigo menos Valor da Parcela
            $saldoAtualizado = $contaBancaria->saldo - $valorParcelaFloat;
            
            // GRAVANDO NO EXTRATO (Item Conta Empresa) COM TODOS OS DADOS
            \DB::table('item_conta_empresas')->insert([
                'conta_id' => $contaEmpresaId,
                'descricao' => "Pagamento NF " . ($compra ? $compra->nf : '') . " (" . $nomeCategoria . ")",
                'valor' => $valorParcelaFloat,
                'tipo_pagamento' => $formaPagamento,
                'tipo' => 'saida',
                'data_pagamento' => $compra ? $compra->data_emissao : date('Y-m-d'), // Usa a data da emissão como pediu
                'user_id' => $this->usuario_id,
                'empresa_id' => $this->empresa_id,
                'origem' => 'Conta Pagar', // Corrigido de 'manual' para Conta Pagar
                'conta_pagar_id' => $result->id, // Gravando o ID do Contas a Pagar
                'saldo_atual' => $saldoAtualizado, // Gravando o saldo pós-movimentação
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // ATUALIZANDO O SALDO GERAL NA TABELA DA CONTA BANCÁRIA
            \DB::table('conta_empresas')->where('id', $contaEmpresaId)->update([
                'saldo' => $saldoAtualizado,
                'updated_at' => now()
            ]);
        }
    }

    return response()->json($result);
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
        $cstPisSugerido = '70'; 

        $cfopsCreditoEspecial = ['5656', '5651', '5653', '1653', '2653'];
        $cfopsCompraRevenda = ['1102', '2102'];

        if (in_array($cstPisXml, ['04', '01'])) {
            if (in_array($cfopEntrada, $cfopsCreditoEspecial) || in_array((string)$itemXml->prod->CFOP, $cfopsCreditoEspecial)) {
                $cstPisSugerido = '50'; 
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
            'cst_cofins' => $cstPisSugerido, 
        ];
    }
	
  	private function parseMoeda($valor)
{
    if (empty($valor)) return 0;
    
    $valorStr = (string)$valor;
    $valorStr = preg_replace('/[^0-9.,]/', '', $valorStr);

    if (strpos($valorStr, ',') !== false && strpos($valorStr, '.') !== false) {
        $ultimaVirgula = strrpos($valorStr, ',');
        $ultimoPonto = strrpos($valorStr, '.');
        
        if ($ultimaVirgula > $ultimoPonto) {
            $valorStr = str_replace('.', '', $valorStr);
            $valorStr = str_replace(',', '.', $valorStr);
        } else {
            $valorStr = str_replace(',', '', $valorStr);
        }
    } 
    elseif (strpos($valorStr, ',') !== false) {
        $valorStr = str_replace(',', '.', $valorStr);
    }
    elseif (substr_count($valorStr, '.') > 1) {
        $ultimoPonto = strrpos($valorStr, '.');
        $parteInteira = str_replace('.', '', substr($valorStr, 0, $ultimoPonto));
        $parteDecimal = substr($valorStr, $ultimoPonto + 1);
        $valorStr = $parteInteira . '.' . $parteDecimal;
    }

    return (float)$valorStr;
}

}