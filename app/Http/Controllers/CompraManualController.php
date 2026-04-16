<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CompraManual;
use App\Models\ItemCompra;
use App\Models\Compra;
use App\Models\Produto;
use App\Models\ContaPagar;
use App\Models\ConfigNota;
use App\Models\Fornecedor;
use App\Helpers\StockMove;
use Carbon\Carbon;
use App\Models\Transportadora;
use App\Models\CategoriaConta;
use App\Models\Categoria;
use App\Models\Tributacao;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AdiantamentoController;

class CompraManualController extends Controller
{
    protected $empresa_id = null;
    protected $usuario_id = null;
    protected $filial_id  = null;

    public function __construct(){
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

    private function getValueOrDefault($array, $key, $default = null) {
        if (isset($array[$key])) {
            $value = trim($array[$key]);
            if ($value !== '') {
                return $value;
            }
        }
        return $default;
    }

    public function custoMedio(Request $request){
        $item = Produto::findOrFail($request->id);

        $valorCompraAtual = __replace($request->valor);
        $valorCompraProduto = $item->valor_compra;
        $estoque = 0;
        if($item->estoque){
            $estoque = $item->estoque->quantidade;
        }
        $quantidadeDaCompra = __replace($request->quantidade);

        $vl = ($valorCompraAtual*$quantidadeDaCompra) + ($valorCompraProduto*$estoque);
        $vcm = moeda($vl/($estoque+$quantidadeDaCompra));
        return response()->json($vcm, 200);
    }

    public function numeroSequencial(){
        $verify = Compra::where('empresa_id', $this->empresa_id)
            ->where('numero_sequencial', 0)
            ->first();
        if($verify){
            $vendas = Compra::where('empresa_id', $this->empresa_id)
                ->get();

            $n = 1;
            foreach($vendas as $v){
                $v->numero_sequencial = $n;
                $n++;
                $v->save();
            }
        }
    }

    public function index(){
        $this->numeroSequencial();
        $veiculos = \App\Models\Veiculo::where('empresa_id', $this->empresa_id)->get();
        $countProdutos = Produto::
        where('empresa_id', $this->empresa_id)
            ->count();

        if($countProdutos > env("ASSINCRONO_PRODUTOS")){
            $view = $this->compraAssincrona();
            return $view;
        }else{
            $fornecedores = Fornecedor::
            where('empresa_id', $this->empresa_id)
                ->orderBy('razao_social')->get();

            if(sizeof($fornecedores) == 0){
                session()->flash("mensagem_erro", "Cadastre um fornecedor!");
                return redirect('/fornecedores');
            }

            $produtos = Produto::
            where('empresa_id', $this->empresa_id)
                ->orderBy('nome')
                ->get();

            foreach($produtos as $p){
                if($p->grade){
                    $p->nome .= " $p->str_grade";
                }
            }

            $transportadoras = Transportadora::
            where('empresa_id', $this->empresa_id)
                ->get();

            $config = ConfigNota::
            where('empresa_id', $this->empresa_id)
                ->first();

            $categorias = Categoria::
            where('empresa_id', $this->empresa_id)
                ->get();

            $unidadesDeMedida = Produto::unidadesMedida();

            $tributacao = Tributacao::
            where('empresa_id', $this->empresa_id)
                ->first();
            $anps = Produto::lista_ANP();

            if($tributacao->regime == 1){
                $listaCSTCSOSN = Produto::listaCST();
            }else{
                $listaCSTCSOSN = Produto::listaCSOSN();
            }

            $listaCST_PIS_COFINS = Produto::listaCST_PIS_COFINS();
            $listaCST_IPI = Produto::listaCST_IPI();

            $natureza = Produto::
            firstNatureza($this->empresa_id);

            return view('compraManual/register')
                ->with('compraManual', true)
                ->with('fornecedores', $fornecedores)
                ->with('config', $config)
                ->with('transportadoras', $transportadoras)
                ->with('produtos', $produtos)
                ->with('categorias', $categorias)
                ->with('tributacao', $tributacao)
                ->with('anps', $anps)
                ->with('listaCSTCSOSN', $listaCSTCSOSN)
                ->with('unidadesDeMedida', $unidadesDeMedida)
                ->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
                ->with('listaCST_IPI', $listaCST_IPI)
                ->with('natureza', $natureza)
                ->with('title', 'Nova Compra Manual')
                ->with('veiculos', $veiculos);
        }
    }

    protected function compraAssincrona(){
        $fornecedores = Fornecedor::
        where('empresa_id', $this->empresa_id)
            ->orderBy('razao_social')->get();
       $veiculos = \App\Models\Veiculo::where('empresa_id', $this->empresa_id)->get();

        if(sizeof($fornecedores) == 0){
            session()->flash("mensagem_erro", "Cadastre um fornecedor!");
            return redirect('/fornecedores');
        }

        $transportadoras = Transportadora::
        where('empresa_id', $this->empresa_id)
            ->get();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
            ->first();

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
            ->get();

        $unidadesDeMedida = Produto::unidadesMedida();

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
            ->first();
        $anps = Produto::lista_ANP();

        if($tributacao->regime == 1){
            $listaCSTCSOSN = Produto::listaCST();
        }else{
            $listaCSTCSOSN = Produto::listaCSOSN();
        }

        $listaCST_PIS_COFINS = Produto::listaCST_PIS_COFINS();
        $listaCST_IPI = Produto::listaCST_IPI();

        $natureza = Produto::
        firstNatureza($this->empresa_id);

        $categoriasDeConta = CategoriaConta::where('empresa_id', $this->empresa_id)
            ->where('tipo', 'pagar')
            ->orderBy('nome', 'asc')->get();

        $p = view('compraManual/register_assincrono')
            ->with('compraManualAssincrono', true)
            ->with('veiculos', $veiculos)
            ->with('fornecedores', $fornecedores)
            ->with('transportadoras', $transportadoras)
            ->with('config', $config)
            ->with('categoriasDeConta', $categoriasDeConta)
            ->with('categorias', $categorias)
            ->with('tributacao', $tributacao)
            ->with('anps', $anps)
            ->with('listaCSTCSOSN', $listaCSTCSOSN)
            ->with('unidadesDeMedida', $unidadesDeMedida)
            ->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
            ->with('listaCST_IPI', $listaCST_IPI)
            ->with('natureza', $natureza)
            ->with('title', 'Compra Manual');

        return $p;
    }

    public function salvar(Request $request)
    {
        try {
            $result = DB::transaction(function () use ($request) {
                $compra = $request->compra;

                $qtdVol = isset($compra['qtdVol']) ? str_replace(",", ".", $compra['qtdVol']) : 0;
                $pesoLiquido = isset($compra['pesoL']) ? str_replace(",", ".", $compra['pesoL']) : 0;
                $pesoBruto = isset($compra['pesoB']) ? str_replace(",", ".", $compra['pesoB']) : 0;
                $valorFrete = isset($compra['valorFrete']) ? str_replace(",", ".", $compra['valorFrete']) : 0;

                $nfInformada = $compra['nf'] ?? 0;
                $emissaoInformada = $compra['numero_emissao'] ?? 0;

                // Definir a data de emissão: se houver data retroativa informada, use-a; caso contrário, use a data atual.
                if (isset($compra['data_retroativa']) && trim($compra['data_retroativa']) !== '' && $compra['data_retroativa'] != -1) {
                    $dataEmissao = $this->parseDate($compra['data_retroativa']);
                } else {
                    $dataEmissao = date('Y-m-d H:i:s');
                }

                // REGRA ESTADO
                $statusCalculado = 'NOVO';
                if ($emissaoInformada > 0) {
                    $statusCalculado = 'APROVADO';
                } elseif ($nfInformada > 0) {
                    $statusCalculado = 'EMITIDA';
                }

                $nf = (isset($compra['nNf']) && trim($compra['nNf']) !== '') ? $compra['nNf'] : 0;

                $result = \App\Models\Compra::create([
                    'fornecedor_id'      => $compra['fornecedor'],
                    //'nf'                 => $compra['nf'] ?? '0',
                    'nf'                 => $nf,
                    'numero_emissao'     => $compra['numero_emissao'] ?? '0',
                    'data_emissao'       => $compra['data_emissao'] ?? $dataEmissao,
                    'veiculo_id'         => (!empty($compra['veiculo_id']) && $compra['veiculo_id'] > 0) ? $compra['veiculo_id'] : null,
                    'usuario_id'         => $this->usuario_id,
                    'observacao'         => $compra['observacao'] ?? '',
                    'lote'               => $compra['lote'] ?? '',
                    'valor'              => str_replace(",", ".", $compra['total']),
                    'desconto'           => $compra['desconto'] != null ? str_replace(",", ".", $compra['desconto']) : 0,
                    'acrescimo'          => $compra['acrescimo'] != null ? str_replace(",", ".", $compra['acrescimo']) : 0,
                    'xml_path'           => '',
                    'estado'             => $statusCalculado,
                    'chave'              => '',
                    'empresa_id'         => $this->empresa_id,
                    'categoria_conta_id' => $compra['categoria_conta_id'] ?? null,
                    'valor_frete'        => $valorFrete,
                    'placa'              => $compra['placaVeiculo'] ?? '',
                    'tipo'               => (int)($compra['frete'] ?? 0),
                    'uf'                 => $compra['ufPlaca'] ?? '',
                    'numeracaoVolumes'   => $compra['numeracaoVol'] ?? '0',
                    'peso_liquido'       => $pesoLiquido,
                    'peso_bruto'         => $pesoBruto,
                    'especie'            => $compra['especie'] ?? '*',
                    'qtdVolumes'         => $qtdVol,
                    'transportadora_id'  => $compra['transportadora'] ?? null,
                    'filial_id'          => $this->filial_id,
                    'data_retroativa'    => (isset($compra['data_retroativa']) && $compra['data_retroativa'] != -1) ? $this->parseDate($compra['data_retroativa']) : null,
                    'data_saida'         => (isset($compra['data_saida']) && $compra['data_saida'] != -1) ? $this->parseDate($compra['data_saida']) : null
                ]);

                // Salva os itens
                $this->salvarItens($result->id, $compra['itens'] ?? []);

                // Força a criação de 1 fatura se a compra for à vista e a tabela estiver vazia
                if (empty($compra['fatura']) || !is_array($compra['fatura'])) {
                    $compra['fatura'] = [
                        [
                            'data' => date('d/m/Y'),
                            'valor' => $compra['total'],
                            'numero' => 1
                        ]
                    ];
                }

                // Salva as parcelas
                $this->salvarParcela(
                    $result->id,
                    $compra['fatura'],
                    $compra['fornecedor'],
                    $compra['categoria_conta_id'] ?? null,
                    $compra
                );

                return $result;
            });

            return response()->json($result);
        } catch (\Exception $e) {
            __saveError($e, $this->empresa_id);
            return response()->json($e->getMessage(), 400);
        }
    }

    public function salvar_mod_date(Request $request)
    {
        try {
            $result = DB::transaction(function () use ($request) {
                $compra = $request->compra;

                $qtdVol = isset($compra['qtdVol']) ? str_replace(",", ".", $compra['qtdVol']) : 0;
                $pesoLiquido = isset($compra['pesoL']) ? str_replace(",", ".", $compra['pesoL']) : 0;
                $pesoBruto = isset($compra['pesoB']) ? str_replace(",", ".", $compra['pesoB']) : 0;
                $valorFrete = isset($compra['valorFrete']) ? str_replace(",", ".", $compra['valorFrete']) : 0;

                $nfInformada = $compra['nf'] ?? 0;
                $emissaoInformada = $compra['numero_emissao'] ?? 0;

                // Definir a data de emissão: se houver data retroativa informada, use-a; caso contrário, use a data atual.
                if (isset($compra['data_retroativa']) && trim($compra['data_retroativa']) !== '' && $compra['data_retroativa'] != -1) {
                    $dataEmissao = $this->parseDate($compra['data_retroativa']);
                } else {
                    $dataEmissao = date('Y-m-d H:i:s');
                }
                //REGRA ESTADO
                $statusCalculado = 'NOVO';
                if ($emissaoInformada > 0) {
                    $statusCalculado = 'APROVADO';
                } elseif ($nfInformada > 0) {
                    $statusCalculado = 'EMITIDA';
                }

                $nf = (isset($compra['nNf']) && trim($compra['nNf']) !== '') ? $compra['nNf'] : 0;

                $result = \App\Models\Compra::create([
                    'fornecedor_id'      => $compra['fornecedor'],
                    //'nf'                 => $compra['nf'] ?? '0',
                    'nf'                 => $nf,
                    'numero_emissao'     => $compra['numero_emissao'] ?? '0',
                    'data_emissao'       => $compra['data_emissao'] ?? date('Y-m-d'),
                    'veiculo_id'         => (!empty($compra['veiculo_id']) && $compra['veiculo_id'] > 0) ? $compra['veiculo_id'] : null,
                    'usuario_id'         => $this->usuario_id,
                    'observacao'         => $compra['observacao'] ?? '',
                    'lote'               => $compra['lote'] ?? '',
                    'valor'              => str_replace(",", ".", $compra['total']),
                    'desconto'           => $compra['desconto'] != null ? str_replace(",", ".", $compra['desconto']) : 0,
                    'acrescimo'          => $compra['acrescimo'] != null ? str_replace(",", ".", $compra['acrescimo']) : 0,
                    'xml_path'           => '',
                    'estado'             => $statusCalculado,
                    'chave'              => '',
                    'empresa_id'         => $this->empresa_id,
                    'categoria_conta_id' => $compra['categoria_conta_id'] ?? null,
                    'valor_frete'        => $valorFrete,
                    'placa'              => $compra['placaVeiculo'] ?? '',
                    'tipo'               => (int)($compra['frete'] ?? 0),
                    'uf'                 => $compra['ufPlaca'] ?? '',
                    'numeracaoVolumes'   => $compra['numeracaoVol'] ?? '0',
                    'peso_liquido'       => $pesoLiquido,
                    'peso_bruto'         => $pesoBruto,
                    'especie'            => $compra['especie'] ?? '*',
                    'qtdVolumes'         => $qtdVol,
                    'transportadora_id'  => $compra['transportadora'] ?? null,
                    'filial_id'          => $this->filial_id,
                    'data_retroativa'    => (isset($compra['data_retroativa']) && $compra['data_retroativa'] != -1) ? $this->parseDate($compra['data_retroativa']) : null,
                    'data_saida'         => (isset($compra['data_saida']) && $compra['data_saida'] != -1) ? $this->parseDate($compra['data_saida']) : null
                ]);

                // Salva os itens
                $this->salvarItens($result->id, $compra['itens'] ?? []);

                // Força a criação de 1 fatura se a compra for à vista e a tabela estiver vazia
                if (empty($compra['fatura']) || !is_array($compra['fatura'])) {
                    $compra['fatura'] = [
                        [
                            'data' => date('d/m/Y'),
                            'valor' => $compra['total'],
                            'numero' => 1
                        ]
                    ];
                }

                // Salva as parcelas
                $this->salvarParcela(
                    $result->id,
                    $compra['fatura'],
                    $compra['fornecedor'],
                    $compra['categoria_conta_id'] ?? null,
                    $compra
                );

                return $result;
            });
            return response()->json($result);
        } catch (\Exception $e) {
            __saveError($e, $this->empresa_id);
            return response()->json($e->getMessage(), 400);
        }
    }

    public function update(Request $request)
    {
        try {
            $result = DB::transaction(function () use ($request) {
                $compra = $request->compra;

                // 1. PROCESSA OS VALORES NUMÉRICOS
                $qtdVol       = isset($compra['qtdVol']) ? str_replace(',', '.', $compra['qtdVol']) : 0;
                $pesoLiquido  = isset($compra['pesoL'])    ? str_replace(',', '.', $compra['pesoL'])    : 0;
                $pesoBruto    = isset($compra['pesoB'])    ? str_replace(',', '.', $compra['pesoB'])    : 0;
                $valorFrete   = str_replace(',', '.', $compra['valorFrete'] ?? 0);

                // 2. RECUPERA E ATUALIZA OS DADOS DA COMPRA
                $res = \App\Models\Compra::findOrFail($compra['id']);

                $res->fornecedor_id      = $compra['fornecedor_id'] ?? $compra['fornecedor'] ?? $res->fornecedor_id;
                $res->nf                 = $compra['nf'] ?? '0';
                $res->numero_emissao     = $compra['numero_emissao'] ?? '0';
                $res->data_emissao       = $compra['data_emissao'] ?? $res->data_emissao;
                $res->veiculo_id         = (!empty($compra['veiculo_id']) && $compra['veiculo_id'] > 0) ? $compra['veiculo_id'] : null;
                $res->observacao         = $compra['observacao'] ?? '';
                $res->valor              = str_replace(',', '.', $compra['total']);
                $res->desconto           = isset($compra['desconto']) && $compra['desconto'] != '' ? str_replace(',', '.', $compra['desconto']) : 0;
                $res->acrescimo          = isset($compra['acrescimo']) && $compra['acrescimo'] != '' ? str_replace(',', '.', $compra['acrescimo']) : 0;
                $res->valor_frete        = $valorFrete;
                $res->placa              = $compra['placaVeiculo'] ?? '';
                $res->tipo               = (int)($compra['frete'] ?? 0);
                $res->uf                 = $compra['ufPlaca'] ?? '';
                $res->numeracaoVolumes   = $compra['numeracaoVol'] ?? '0';
                $res->peso_liquido       = $pesoLiquido;
                $res->peso_bruto         = $pesoBruto;
                $res->especie            = $compra['especie'] ?? '*';
                $res->qtdVolumes         = $qtdVol;
                $res->transportadora_id  = $compra['transportadora'] ?? null;
                $res->categoria_conta_id = $compra['categoria_conta_id'] ?: null;
                $res->data_retroativa    = (isset($compra['data_retroativa']) && $compra['data_retroativa'] != -1) ? $this->parseDate($compra['data_retroativa']) : null;
                $res->data_saida         = (isset($compra['data_saida']) && $compra['data_saida'] != -1) ? $this->parseDate($compra['data_saida']) : null;

                $statusCalculado = 'NOVO';
                if (($compra['numero_emissao'] ?? 0) > 0) {
                    $statusCalculado = 'APROVADO';
                } elseif (($compra['nf'] ?? 0) > 0) {
                    $statusCalculado = 'EMITIDA';
                }
                $res->estado = $statusCalculado;
                $res->save();

                // 3. ATUALIZA OS ITENS (REMOVE E REGRAVA)
                $this->removerItens($res->id);
                $this->salvarItens($res->id, $compra['itens'] ?? []);

                // 4. EXCLUI PARCELAS QUE FORAM REMOVIDAS NA TELA
                if (!empty($compra['faturas_removidas'])) {
                    \App\Models\ContaPagar::whereIn('id', $compra['faturas_removidas'])->delete();
                }

                // 5. PROCESSA AS PARCELAS (FATURA)
                if (empty($compra['fatura']) || !is_array($compra['fatura'])) {
                    $compra['fatura'] = [
                        [
                            'data' => date('d/m/Y'),
                            'valor' => $compra['total'],
                            'numero' => 1
                        ]
                    ];
                }

                $totalParcelas = count($compra['fatura']);
                $usouCredito = (isset($compra['usar_adiantamento']) && $compra['usar_adiantamento'] == 1);

                // Trata a categoria para não perder na edição
                $categoria_id = null;
                if (!empty($compra['categoria_conta_id'])) {
                    $categoria_id = $compra['categoria_conta_id'];
                } else {
                    $catDefault = CategoriaConta::where('empresa_id', $this->empresa_id)->first();
                    $categoria_id = $catDefault ? $catDefault->id : null;
                }

                // --- CALCULA O SALDO DISPONÍVEL DO FORNECEDOR (PARA ABATIMENTO PARCIAL) ---
                $saldoAdiantamento = 0;
                if ($usouCredito) {
                    $adiantamentos = \App\Models\Adiantamento::where('empresa_id', $this->empresa_id)
                        ->where('fornecedor_id', $res->fornecedor_id)
                        ->where('status', 'aberto')
                        ->get();
                    foreach($adiantamentos as $ad) {
                        $saldoAdiantamento += ($ad->valor_total - $ad->valor_utilizado);
                    }
                }

                foreach ($compra['fatura'] as $parcela) {
                    $dataVenc = $this->parseDate($parcela['data']);

                    // Limpeza do valor
                    $valString = str_replace(['R$', ' '], '', $parcela['valor']);
                    if (strpos($valString, ',') !== false) {
                        $valString = str_replace('.', '', $valString);
                        $valString = str_replace(',', '.', $valString);
                    }
                    $cleanValor = (float) $valString;
                    $formaPgtoOriginal = $compra['formaPagamento'] ?? '';

                    // --- LÓGICA DE SPLIT INTELIGENTE (TOTAL OU PARCIAL) ---
                    if ($usouCredito && $saldoAdiantamento > 0) {

                        if ($saldoAdiantamento >= $cleanValor) {
                            // Cobre a parcela toda
                            if (isset($parcela['db_id']) && $parcela['db_id'] > 0) {
                                \App\Models\ContaPagar::where('id', $parcela['db_id'])->update([
                                    'data_vencimento' => $dataVenc,
                                    'data_pagamento'  => date('Y-m-d'),
                                    'valor_integral'  => $cleanValor,
                                    'valor_pago'      => $cleanValor,
                                    'status'          => true,
                                    'tipo_pagamento'  => 'adiantamento',
                                    'categoria_id'    => $categoria_id,
                                    'numero_nota_fiscal' => $res->nf,
                                    'fornecedor_id'   => $res->fornecedor_id
                                ]);
                            } else {
                                $nova = \App\Models\ContaPagar::create([
                                    'compra_id'       => $res->id,
                                    'fornecedor_id'   => $res->fornecedor_id,
                                    'numero_nota_fiscal' => $res->nf,
                                    'usuario_id'      => $this->usuario_id,
                                    'veiculo_id'      => $res->veiculo_id,
                                    'data_emissao'    => $res->data_emissao,
                                    'data_vencimento' => $dataVenc,
                                    'data_pagamento'  => date('Y-m-d'),
                                    'valor_integral'  => $cleanValor,
                                    'valor_pago'      => $cleanValor,
                                    'status'          => true,
                                    'tipo_pagamento'  => 'adiantamento',
                                    'referencia'      => "Parcela {$parcela['numero']}/{$totalParcelas} da Compra {$res->id}",
                                    'categoria_id'    => $categoria_id,
                                    'empresa_id'      => $this->empresa_id,
                                    'filial_id'       => $this->filial_id,
                                ]);
                                AdiantamentoController::baixarAdiantamento($res->fornecedor_id, 'fornecedor', $cleanValor, $this->empresa_id, $nova->id);
                            }
                            $saldoAdiantamento -= $cleanValor;

                        } else {
                            // SPLIT! O saldo do fornecedor acabou no meio da conta
                            $valorAdiantamento = $saldoAdiantamento;
                            $valorRestante = $cleanValor - $saldoAdiantamento;
                            $saldoAdiantamento = 0; // Zerou a carteira

                            // PARTE 1 (Paga com Adiantamento)
                            if (isset($parcela['db_id']) && $parcela['db_id'] > 0) {
                                \App\Models\ContaPagar::where('id', $parcela['db_id'])->update([
                                    'data_vencimento' => $dataVenc,
                                    'data_pagamento'  => date('Y-m-d'),
                                    'valor_integral'  => $valorAdiantamento,
                                    'valor_pago'      => $valorAdiantamento,
                                    'status'          => true,
                                    'tipo_pagamento'  => 'adiantamento',
                                    'categoria_id'    => $categoria_id,
                                    'numero_nota_fiscal' => $res->nf,
                                    'fornecedor_id'   => $res->fornecedor_id
                                ]);
                                // Não disparamos o baixarAdiantamento na edição pois exigiria estorno prévio complexo, mantemos a baixa salva original
                            } else {
                                $novaPaga = \App\Models\ContaPagar::create([
                                    'compra_id'       => $res->id,
                                    'fornecedor_id'   => $res->fornecedor_id,
                                    'numero_nota_fiscal' => $res->nf,
                                    'usuario_id'      => $this->usuario_id,
                                    'veiculo_id'      => $res->veiculo_id,
                                    'data_emissao'    => $res->data_emissao,
                                    'data_vencimento' => $dataVenc,
                                    'data_pagamento'  => date('Y-m-d'),
                                    'valor_integral'  => $valorAdiantamento,
                                    'valor_pago'      => $valorAdiantamento,
                                    'status'          => true,
                                    'tipo_pagamento'  => 'adiantamento',
                                    'referencia'      => "Parcela {$parcela['numero']} (Adiantamento) da Compra {$res->id}",
                                    'categoria_id'    => $categoria_id,
                                    'empresa_id'      => $this->empresa_id,
                                    'filial_id'       => $this->filial_id,
                                ]);
                                AdiantamentoController::baixarAdiantamento($res->fornecedor_id, 'fornecedor', $valorAdiantamento, $this->empresa_id, $novaPaga->id);
                            }

                            // PARTE 2 (Aberta para o financeiro pagar o resto)
                            \App\Models\ContaPagar::create([
                                'compra_id'       => $res->id,
                                'fornecedor_id'   => $res->fornecedor_id,
                                'numero_nota_fiscal' => $res->nf,
                                'usuario_id'      => $this->usuario_id,
                                'veiculo_id'      => $res->veiculo_id,
                                'data_emissao'    => $res->data_emissao,
                                'data_vencimento' => $dataVenc,
                                'data_pagamento'  => null,
                                'valor_integral'  => $valorRestante,
                                'valor_pago'      => 0,
                                'status'          => false,
                                'tipo_pagamento'  => $formaPgtoOriginal,
                                'referencia'      => "Parcela {$parcela['numero']} (Restante) da Compra {$res->id}",
                                'categoria_id'    => $categoria_id,
                                'empresa_id'      => $this->empresa_id,
                                'filial_id'       => $this->filial_id,
                            ]);
                        }
                    } else {
                        // NORMAL - SEM CRÉDITO OU ACABOU O CRÉDITO
                        if (isset($parcela['db_id']) && $parcela['db_id'] > 0) {
                            \App\Models\ContaPagar::where('id', $parcela['db_id'])->update([
                                'data_vencimento' => $dataVenc,
                                'data_pagamento'  => null,
                                'valor_integral'  => $cleanValor,
                                'valor_pago'      => 0,
                                'status'          => false,
                                'tipo_pagamento'  => $formaPgtoOriginal,
                                'categoria_id'    => $categoria_id,
                                'numero_nota_fiscal' => $res->nf,
                                'fornecedor_id'   => $res->fornecedor_id
                            ]);
                        } else {
                            \App\Models\ContaPagar::create([
                                'compra_id'       => $res->id,
                                'fornecedor_id'   => $res->fornecedor_id,
                                'numero_nota_fiscal' => $res->nf,
                                'usuario_id'      => $this->usuario_id,
                                'veiculo_id'      => $res->veiculo_id,
                                'data_emissao'    => $res->data_emissao,
                                'data_vencimento' => $dataVenc,
                                'data_pagamento'  => null,
                                'valor_integral'  => $cleanValor,
                                'valor_pago'      => 0,
                                'status'          => false,
                                'tipo_pagamento'  => $formaPgtoOriginal,
                                'referencia'      => "Parcela {$parcela['numero']}/{$totalParcelas} da Compra {$res->id}",
                                'categoria_id'    => $categoria_id,
                                'empresa_id'      => $this->empresa_id,
                                'filial_id'       => $this->filial_id,
                            ]);
                        }
                    }
                }

                session()->flash('mensagem_sucesso', 'Compra atualizada!');
                return $res;
            });

            return response()->json($result);
        } catch (\Exception $e) {
            __saveError($e, $this->empresa_id);
            return response()->json($e->getMessage(), 400);
        }
    }

    private function salvarItens($id, $itens) {
        $stockMove = new StockMove();
        $compra = \App\Models\Compra::findOrFail($id);

        foreach ($itens as $i) {
            $prod = \App\Models\Produto::where('id', (int)$i['codigo'])
                ->where('empresa_id', $this->empresa_id)
                ->first();

            $result = \App\Models\ItemCompra::create([
                'compra_id'      => $id,
                'produto_id'     => (int)$i['codigo'],
                'quantidade'     => str_replace(",", ".", $i['quantidade']),
                'valor_unitario' => str_replace(",", ".", $i['valor']),
                'unidade_compra' => $prod['unidade_compra'],
            ]);

            if ($prod->reajuste_automatico) {
                $prod->valor_venda = $prod->valor_compra +
                    (($prod->valor_compra * $prod->percentual_lucro) / 100);
            }

            if (isset($i['valor_custo'])) {
                $vc = __replace($i['valor_custo']);
                if ($vc > 0) {
                    $prod->valor_compra = $vc;
                }
            }
            $prod->save();

            if ($prod->gerenciar_estoque) {
                // Captura a data retroativa (se existir) ou a data de emissão
                $dataLancamento = $compra->data_retroativa ?? $compra->data_emissao ?? date('Y-m-d');

                $stockMove->pluStock(
                    (int)$i['codigo'],
                    __replace($i['quantidade']) * $prod->conversao_unitaria,
                    __replace($i['valor']),
                    $compra->filial_id,
                    'compra',
                    $compra->id,
                    $dataLancamento // <--- PASSA A DATA AQUI!
                );
            }
        }
        return true;
    }

    public function salvarParcela($id, $fatura, $fornecedor_id, $categoria_conta_id, $compra = []){
        $cont = 0;

        $numero_emissao = !empty($compra['numero_emissao']) ? $compra['numero_emissao'] : (!empty($compra['nf']) ? $compra['nf'] : '0');
        $data_emissao   = !empty($compra['data_emissao']) ? $compra['data_emissao'] : date('Y-m-d');
        $veiculo_id     = (!empty($compra['veiculo_id']) && $compra['veiculo_id'] > 0) ? $compra['veiculo_id'] : null;

        $usouCredito = (isset($compra['usar_adiantamento']) && $compra['usar_adiantamento'] == 1);

        // --- CALCULA O SALDO DISPONÍVEL DO FORNECEDOR ---
        $saldoAdiantamento = 0;
        if ($usouCredito) {
            $adiantamentos = \App\Models\Adiantamento::where('empresa_id', $this->empresa_id)
                ->where('fornecedor_id', $fornecedor_id)
                ->where('status', 'aberto')
                ->get();
            foreach($adiantamentos as $ad) {
                $saldoAdiantamento += ($ad->valor_total - $ad->valor_utilizado);
            }
        }

        foreach($fatura as $parcela){
            $cont = $cont+1;

            // 1. LIMPEZA PROFUNDA DO VALOR
            $valorParcelaStr = $parcela['valor'];
            $valorParcelaStr = str_replace(['R$', ' '], '', $valorParcelaStr);
            if (strpos($valorParcelaStr, ',') !== false) {
                $valorParcelaStr = str_replace('.', '', $valorParcelaStr);
                $valorParcelaStr = str_replace(',', '.', $valorParcelaStr);
            }
            $valorParcelaFloat = (float) $valorParcelaStr;
            $formaPgtoOriginal = $compra['formaPagamento'] ?? '';

            // 2. CORREÇÃO DA CATEGORIA DA CONTA
            $categoria_id = null;
            if (!empty($categoria_conta_id)) {
                $categoria_id = $categoria_conta_id;
            } elseif (!empty($compra['categoria_conta_id'])) {
                $categoria_id = $compra['categoria_conta_id'];
            }
            if (!$categoria_id) {
                $catDefault = CategoriaConta::where('empresa_id', $this->empresa_id)->first();
                $categoria_id = $catDefault ? $catDefault->id : null;
            }

            // 3. LÓGICA DE SPLIT INTELIGENTE
            if ($usouCredito && $saldoAdiantamento > 0) {
                if ($saldoAdiantamento >= $valorParcelaFloat) {
                    // Paga tudo!
                    $result = ContaPagar::create([
                        'compra_id' => $id,
                        'fornecedor_id' => $fornecedor_id,
                        'usuario_id'    => $this->usuario_id,
                        'veiculo_id'    => $veiculo_id,
                        'numero_nota_fiscal' => $numero_emissao,
                        'data_emissao'  => $data_emissao,
                        'data_vencimento' => $this->parseDate($parcela['data']),
                        'data_pagamento' => date('Y-m-d'),
                        'valor_integral' => $valorParcelaFloat,
                        'valor_pago' => $valorParcelaFloat,
                        'status' => true,
                        'tipo_pagamento' => 'adiantamento',
                        'referencia' => "Parcela $cont/" . sizeof($fatura) . " da Compra $id",
                        'categoria_id' => $categoria_id,
                        'empresa_id' => $this->empresa_id,
                        'filial_id' => ((int) $this->filial_id === -1 ? null : $this->filial_id),
                    ]);
                    AdiantamentoController::baixarAdiantamento($fornecedor_id, 'fornecedor', $valorParcelaFloat, $this->empresa_id, $result->id);
                    $saldoAdiantamento -= $valorParcelaFloat;

                } else {
                    // SPLIT: Paga uma parte e deixa o resto aberto
                    $valorAdiantamento = $saldoAdiantamento;
                    $valorRestante = $valorParcelaFloat - $saldoAdiantamento;
                    $saldoAdiantamento = 0;

                    // PARCELA 1: Baixada com adiantamento
                    $result1 = ContaPagar::create([
                        'compra_id' => $id,
                        'fornecedor_id' => $fornecedor_id,
                        'usuario_id'    => $this->usuario_id,
                        'veiculo_id'    => $veiculo_id,
                        'numero_nota_fiscal' => $numero_emissao,
                        'data_emissao'  => $data_emissao,
                        'data_vencimento' => $this->parseDate($parcela['data']),
                        'data_pagamento' => date('Y-m-d'),
                        'valor_integral' => $valorAdiantamento,
                        'valor_pago' => $valorAdiantamento,
                        'status' => true,
                        'tipo_pagamento' => 'adiantamento',
                        'referencia' => "Parcela $cont (Adiantamento) da Compra $id",
                        'categoria_id' => $categoria_id,
                        'empresa_id' => $this->empresa_id,
                        'filial_id' => ((int) $this->filial_id === -1 ? null : $this->filial_id),
                    ]);
                    AdiantamentoController::baixarAdiantamento($fornecedor_id, 'fornecedor', $valorAdiantamento, $this->empresa_id, $result1->id);

                    // PARCELA 2: Aberta com o que faltou
                    ContaPagar::create([
                        'compra_id' => $id,
                        'fornecedor_id' => $fornecedor_id,
                        'usuario_id'    => $this->usuario_id,
                        'veiculo_id'    => $veiculo_id,
                        'numero_nota_fiscal' => $numero_emissao,
                        'data_emissao'  => $data_emissao,
                        'data_vencimento' => $this->parseDate($parcela['data']),
                        'data_pagamento' => null,
                        'valor_integral' => $valorRestante,
                        'valor_pago' => 0,
                        'status' => false,
                        'tipo_pagamento' => $formaPgtoOriginal,
                        'referencia' => "Parcela $cont (Restante) da Compra $id",
                        'categoria_id' => $categoria_id,
                        'empresa_id' => $this->empresa_id,
                        'filial_id' => ((int) $this->filial_id === -1 ? null : $this->filial_id),
                    ]);
                }
            } else {
                // NÃO USOU CRÉDITO OU NÃO TEM SALDO
                ContaPagar::create([
                    'compra_id' => $id,
                    'fornecedor_id' => $fornecedor_id,
                    'usuario_id'    => $this->usuario_id,
                    'veiculo_id'    => $veiculo_id,
                    'numero_nota_fiscal' => $numero_emissao,
                    'data_emissao'  => $data_emissao,
                    'data_vencimento' => $this->parseDate($parcela['data']),
                    'data_pagamento' => null,
                    'valor_integral' => $valorParcelaFloat,
                    'valor_pago' => 0,
                    'status' => false,
                    'tipo_pagamento' => $formaPgtoOriginal,
                    'referencia' => "Parcela $cont/" . sizeof($fatura) . " da Compra $id",
                    'categoria_id' => $categoria_id,
                    'empresa_id' => $this->empresa_id,
                    'filial_id' => ((int) $this->filial_id === -1 ? null : $this->filial_id),
                ]);
            }
        }
        return true;
    }

    private function parseDate($date){
        return date('Y-m-d', strtotime(str_replace("/", "-", $date)));
    }

    public function ultimaCompra($produtoId){
        $item = ItemCompra::
        where('produto_id', $produtoId)
            ->orderBy('id', 'desc')
            ->get();

        if(count($item) > 0){
            $last = $item[0];
            $r = [
                'fornecedor' => $last->compra->fornecedor->razao_social,
                'valor' => $last->valor_unitario,
                'quantidade' => $last->quantidade,
                'data' => Carbon::parse($last->compra->created_at)->format('d/m/Y H:i:s')
            ];
            echo json_encode($r);
        }else{
            echo json_encode(null);
        }
    }

    public function editar($id){
        $compra = Compra::findOrFail($id);
        $veiculos = \App\Models\Veiculo::where('empresa_id', $this->empresa_id)->get();
        $countProdutos = Produto::
        where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->count();

        $fornecedores = Fornecedor::
        where('empresa_id', $this->empresa_id)
            ->orderBy('razao_social')->get();

        if(sizeof($fornecedores) == 0){
            session()->flash("mensagem_erro", "Cadastre um fornecedor!");
            return redirect('/fornecedores');
        }

        $produtos = Produto::
        where('empresa_id', $this->empresa_id)
            ->where('inativo', false)
            ->orderBy('nome')
            ->get();

        foreach($produtos as $p){
            if($p->grade){
                $p->nome .= " $p->str_grade";
            }
        }

        $transportadoras = Transportadora::
        where('empresa_id', $this->empresa_id)
            ->get();

        $config = ConfigNota::
        where('empresa_id', $this->empresa_id)
            ->first();

        $fatura = null;
        if(sizeof($compra->fatura) == 0){
            $fatura = [];
            $temp = [
                'data_vencimento' => \Carbon\Carbon::parse($compra->created_at)->format('Y-m-d'),
                'valor_integral' => $compra->valor
            ];

            array_push($fatura, $temp);
        }else{
            $fatura = $compra->fatura;
        }

        $categorias = Categoria::
        where('empresa_id', $this->empresa_id)
            ->get();

        $unidadesDeMedida = Produto::unidadesMedida();

        $tributacao = Tributacao::
        where('empresa_id', $this->empresa_id)
            ->first();
        $anps = Produto::lista_ANP();

        if($tributacao->regime == 1){
            $listaCSTCSOSN = Produto::listaCST();
        }else{
            $listaCSTCSOSN = Produto::listaCSOSN();
        }

        $listaCST_PIS_COFINS = Produto::listaCST_PIS_COFINS();
        $listaCST_IPI = Produto::listaCST_IPI();

        $natureza = Produto::
        firstNatureza($this->empresa_id);

        $categoriasDeConta = CategoriaConta::where('empresa_id', $this->empresa_id)
            ->where('tipo', 'pagar')
            ->orderBy('nome', 'asc')->get();

        return view('compraManual/edit')
            ->with('compraManual', true)
            ->with('fornecedores', $fornecedores)
            ->with('unidadesDeMedida', $unidadesDeMedida)
            ->with('tributacao', $tributacao)
            ->with('categoriasDeConta', $categoriasDeConta)
            ->with('config', $config)
            ->with('listaCSTCSOSN', $listaCSTCSOSN)
            ->with('listaCST_PIS_COFINS', $listaCST_PIS_COFINS)
            ->with('listaCST_IPI', $listaCST_IPI)
            ->with('natureza', $natureza)
            ->with('anps', $anps)
            ->with('fatura', $fatura)
            ->with('compra', $compra)
            ->with('categorias', $categorias)
            ->with('transportadoras', $transportadoras)
            ->with('produtos', $produtos)
            ->with('title', 'Editar Compra Manual')
            ->with('veiculos', $veiculos);

    }

    public function syncDataEmissaoRetroativa()
    {
        $updatedCount = 0;

        $compras = \App\Models\Compra::whereNotNull('data_retroativa')
            ->where(function ($query) {
                $query->whereNull('data_emissao')
                    ->orWhereRaw('DATE(data_emissao) <> DATE(data_retroativa)');
            })
            ->where('empresa_id', $this->empresa_id)
            ->get();

        foreach ($compras as $compra) {
            $novaDataEmissao = $compra->data_retroativa;

            $compra->data_emissao = $novaDataEmissao;
            if ($compra->save()) {
                $updatedCount++;
            }
        }

        if ($updatedCount >= 0) {
            session()->flash('mensagem_sucesso', "Sincronização concluída. {$updatedCount} compra(s) atualizada(s)!");
        } else {
            session()->flash('mensagem_erro', 'Ocorreu um erro na sincronização da data de emissão retroativa!');
        }

        $rota = __getRedirect($this->empresa_id, 'compras');
        return $rota ? redirect($rota) : redirect('/compras');
    }

    private function removerItens($compraId) {
        $stockMove = new StockMove();
        $compra = \App\Models\Compra::find($compraId);

        // 1. Calcula o saldo real (Entradas - Saídas) que esta compra deixou no estoque
        $saldos = \Illuminate\Support\Facades\DB::table('stock_movements')
            ->select('produto_id', 'filial_id',
                \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN tipo = "entrada" THEN quantidade ELSE -quantidade END) as saldo_real')
            )
            ->where('origem_tipo', 'compra')
            ->where('origem_id', $compraId)
            ->groupBy('produto_id', 'filial_id')
            ->having('saldo_real', '>', 0)
            ->get();

        // 2. Estorna EXATAMENTE a quantidade matemática que estava valendo (os 300)
        foreach ($saldos as $saldo) {
            $stockMove->downStock(
                $saldo->produto_id,
                $saldo->saldo_real,
                $saldo->filial_id,
                'compra',
                $compraId
            );
        }

        // 3. Limpa os itens velhos da compra para dar espaço aos novos
        \App\Models\ItemCompra::where('compra_id', $compraId)->delete();
        return true;
    }

    public function updateItem(Request $request)
    {
        try {
            $item = ItemCompra::findOrFail($request->itemId);
            $item->quantidade = $request->quantidade;
            $item->valor_unitario = str_replace(",", ".", $request->valor);
            $item->save();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function editarItem(Request $request)
    {
        $validated = $request->validate([
            'valor' => 'required|numeric',
            'quantidade' => 'required|numeric',
            'nome' => 'required|string',
        ]);

        $itemId = $request->input('id_item');
        $quantidade = $request->input('quantidade');
        $valor_unitario = $request->input('valor');
        $compraId = $request->input('compraId');

        $item = \App\Models\ItemCompra::where('id', $itemId)
            ->where('compra_id', $compraId)
            ->first();

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Item não encontrado']);
        }

        $item->quantidade = $quantidade;
        $item->valor_unitario = $valor_unitario;
        $item->save();

        return response()->json(['status' => 'success']);
    }

    public function excluirItem(Request $request)
    {
        try {
            DB::transaction(function() use ($request) {
                $item = ItemCompra::findOrFail($request->itemId);
                $prod = Produto::find($item->produto_id);

                if ($prod->gerenciar_estoque) {
                    $stockMove = new StockMove();

                    // Usamos o downStock e passamos o vínculo da compra
                    $stockMove->downStock(
                        $item->produto_id,
                        $item->quantidade,
                        $item->compra->filial_id,
                        'compra',
                        $item->compra_id
                    );
                }

                $item->delete();
                session()->flash('mensagem_sucesso', 'Item excluído com sucesso!');
            });

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
