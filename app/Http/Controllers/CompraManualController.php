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
        $countProdutos = Produto::
        where('empresa_id', $this->empresa_id)
            // ->where('inativo', false)
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
                // ->where('inativo', false)
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
                ->with('title', 'Nova Compra Manual');
        }
    }

    protected function compraAssincrona(){
        $fornecedores = Fornecedor::
        where('empresa_id', $this->empresa_id)
            ->orderBy('razao_social')->get();

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

                // Tratar os demais campos, por exemplo, quantidade de volumes, pesos, etc.
                $qtdVol = isset($compra['qtdVol']) ? str_replace(",", ".", $compra['qtdVol']) : 0;
                $pesoLiquido = isset($compra['pesoL']) ? str_replace(",", ".", $compra['pesoL']) : 0;
                $pesoBruto = isset($compra['pesoB']) ? str_replace(",", ".", $compra['pesoB']) : 0;
                $valorFrete = isset($compra['valorFrete']) ? str_replace(",", ".", $compra['valorFrete']) : 0;

                // Definir a data de emissão: se houver data retroativa informada, use-a; caso contrário, use a data atual.
                if (isset($compra['data_retroativa']) && trim($compra['data_retroativa']) !== '' && $compra['data_retroativa'] != -1) {
                    $dataEmissao = $this->parseDate($compra['data_retroativa']);
                } else {
                    $dataEmissao = date('Y-m-d H:i:s');
                }

                $nf = (isset($compra['nNf']) && trim($compra['nNf']) !== '') ? $compra['nNf'] : 0;

                $result = \App\Models\Compra::create([
                    'fornecedor_id'      => $compra['fornecedor'],
                    'usuario_id'         => get_id_user(),
                    'nf'                 => $nf,
                    'observacao'         => $compra['observacao'] ?? '',
                    'lote'               => $compra['lote'] ?? '',
                    'valor'              => str_replace(",", ".", $compra['total']),
                    'desconto'           => $compra['desconto'] != null ? str_replace(",", ".", $compra['desconto']) : 0,
                    'acrescimo'          => $compra['acrescimo'] != null ? str_replace(",", ".", $compra['acrescimo']) : 0,
                    'xml_path'           => '',
                    'estado'             => 'NOVO',
                    'chave'              => '',
                    'numero_emissao'     => 0,
                    'empresa_id'         => $this->empresa_id,
                    'categoria_conta_id' => $compra['categoria_conta_id'] ?? null,
                    'valor_frete'        => $valorFrete,
                    'placa'              => $compra['placaVeiculo'] ?? '',
                    'tipo'               => (int)$compra['frete'],
                    'uf'                 => $compra['ufPlaca'] ?? '',
                    'numeracaoVolumes'   => $compra['numeracaoVol'] ?? '0',
                    'peso_liquido'       => $pesoLiquido,
                    'peso_bruto'         => $pesoBruto,
                    'especie'            => $compra['especie'] ?? '*',
                    'qtdVolumes'         => $qtdVol,
                    'transportadora_id'  => $compra['transportadora'],
                    //'filial_id'          => $compra['filial_id'] != -1 ? $compra['filial_id'] : null,
                    'filial_id'          => $this->filial_id,
                    'data_emissao'       => $dataEmissao,
                    'data_retroativa'    => $compra['data_retroativa'] != -1 ? $this->parseDate($compra['data_retroativa']) : null,
                    'data_saida'         => $compra['data_saida'] != -1 ? $this->parseDate($compra['data_saida']) : null
                ]);

                // Salva os itens
                $this->salvarItens($result->id, $compra['itens'] ?? []);

                // Salva as parcelas (se aplicável)
                if (!empty($compra['formaPagamento']) && $compra['formaPagamento'] != 'a_vista') {

                    // Se a forma de pagamento não é à vista, fatura é obrigatória
                    if (empty($compra['fatura']) || !is_array($compra['fatura'])) {
                        throw new \Exception('Dados de fatura não informados para forma de pagamento diferente de "a_vista".');
                    }

                    $this->salvarParcela(
                        $result->id,
                        $compra['fatura'],
                        $compra['fornecedor'],
                        $compra['categoria_conta_id'] ?? null
                    );
                }

                return $result;
            });
            echo json_encode($result);
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

                // 1) Processa os valores numéricos
                $qtdVol       = isset($compra['qtdVol']) ? str_replace(',', '.', $compra['qtdVol']) : 0;
                $pesoLiquido  = isset($compra['pesoL'])    ? str_replace(',', '.', $compra['pesoL'])    : 0;
                $pesoBruto    = isset($compra['pesoB'])    ? str_replace(',', '.', $compra['pesoB'])    : 0;
                $valorFrete   = str_replace(',', '.', $compra['valorFrete'] ?? 0);

                // 2) Recupera e atualiza a compra
                $res = \App\Models\Compra::findOrFail($compra['id']);
                $res->fornecedor_id      = $compra['fornecedor_id'];
                $res->nf                 = '0';
                $res->observacao         = $compra['observacao'] ?? '';
                $res->valor              = str_replace(',', '.', $compra['total']);
                $res->desconto           = $compra['desconto']   ? str_replace(',', '.', $compra['desconto'])   : 0;
                $res->acrescimo          = $compra['acrescimo']  ? str_replace(',', '.', $compra['acrescimo'])  : 0;
                $res->valor_frete        = $valorFrete;
                $res->placa              = $compra['placaVeiculo'] ?? '';
                $res->tipo               = (int)$compra['frete'];
                $res->uf                 = $compra['ufPlaca']     ?? '';
                $res->numeracaoVolumes   = $compra['numeracaoVol'] ?? '0';
                $res->peso_liquido       = $pesoLiquido;
                $res->peso_bruto         = $pesoBruto;
                $res->especie            = $compra['especie']     ?? '*';
                $res->qtdVolumes         = $qtdVol;
                $res->transportadora_id  = $compra['transportadora'];
                $res->categoria_conta_id = $compra['categoria_conta_id'] ?: null;
                $res->data_retroativa    = $compra['data_retroativa']
                    ? $this->parseDate($compra['data_retroativa'])
                    : null;
                $res->data_saida         = $compra['data_saida']
                    ? $this->parseDate($compra['data_saida'])
                    : null;
                $res->save();

                // 3) Remove e regrava itens (mantém consistência de estoque)
                $this->removerItens($res->id);
                $this->salvarItens($res->id, $compra['itens'] ?? []);

                // 4) Exclui as parcelas removidas na UI
                if (!empty($compra['faturas_removidas'])) {
                    \App\Models\ContaPagar::whereIn('id', $compra['faturas_removidas'])->delete();
                }

                // 5) Atualiza ou cria parcelas corretamente, limpando separadores de milhares
                if (!empty($compra['formaPagamento']) && $compra['formaPagamento'] !== 'a_vista') {

                    if (empty($compra['fatura']) || !is_array($compra['fatura'])) {
                        throw new \Exception('Dados de fatura não informados para forma de pagamento diferente de "a_vista".');
                    }

                    $totalParcelas = count($compra['fatura']);

                    foreach ($compra['fatura'] as $parcela) {
                        // remove separador de milhar e converte vírgula para ponto
                        $rawValor    = $parcela['valor'];
                        $cleanValor  = str_replace('.', '', $rawValor);
                        $cleanValor  = str_replace(',', '.', $cleanValor);

                        $dataVenc    = $this->parseDate($parcela['data']);

                        if (!empty($parcela['db_id'])) {
                            // Atualiza parcela existente
                            $cp = \App\Models\ContaPagar::find($parcela['db_id']);
                            if ($cp) {
                                $cp->data_vencimento = $dataVenc;
                                $cp->valor_integral = $cleanValor;
                                $cp->save();
                            }
                        } else {
                            // Cria nova parcela
                            \App\Models\ContaPagar::create([
                                'compra_id'       => $res->id,
                                'fornecedor_id'   => $compra['fornecedor_id'],
                                'data_vencimento' => $dataVenc,
                                'data_pagamento'  => $dataVenc,
                                'valor_integral'  => $cleanValor,
                                'valor_pago'      => 0,
                                'status'          => false,
                                'referencia'      => "Parcela {$parcela['numero']}/{$totalParcelas} da Compra {$res->id}",
                                'categoria_id'    => $res->categoria_conta_id,
                                'empresa_id'      => $this->empresa_id,
                                'filial_id'       => ((int)$this->filial_id === -1 ? null : $this->filial_id),
                            ]);
                        }
                    }
                }

                session()->flash('mensagem_sucesso', 'Compra atualizada!');
                return $res;
            });

            echo json_encode($result);
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
                $stockMove->pluStock(
                    (int)$i['codigo'],
                    __replace($i['quantidade']) * $prod->conversao_unitaria,
                    __replace($i['valor']),
                    $compra->filial_id
                );
            }
        }
        return true;
    }

    public function salvarParcela($id, $fatura, $fornecedor_id, $categoria_conta_id){
        $cont = 0;
        $valor = 0;
        foreach($fatura as $parcela){
            $cont = $cont+1;
            $valorParcela = str_replace(".", "", $parcela['valor']);
            $valorParcela = str_replace(",", ".", $valorParcela);

            $categoria = CategoriaConta::where('empresa_id', $this->empresa_id)->first();
            if($categoria_conta_id){
                $categoria = CategoriaConta::findOrFail($categoria_conta_id);
            }

            $result = ContaPagar::create([
                'compra_id' => $id,
                'fornecedor_id' => $fornecedor_id,
                'data_vencimento' => $this->parseDate($parcela['data']),
                'data_pagamento' => $this->parseDate($parcela['data']),
                'valor_integral' => $valorParcela,
                'valor_pago' => 0,
                'status' => false,
                'referencia' => "Parcela $cont/" . sizeof($fatura) . " da Compra $id",
                'categoria_id' => $categoria->id,
                'empresa_id' => $this->empresa_id,
                'filial_id' => ((int) $this->filial_id === -1 ? null : $this->filial_id),
                //-'filial_id' => $this->filial_id
            ]);
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
            ->with('title', 'Editar Compra Manual');

    }

    public function syncDataEmissaoRetroativa()
    {
        $updatedCount = 0;

        // Seleciona as compras com data_retroativa preenchida
        // e cuja data_emissao esteja vazia ou seja diferente da data_retroativa.
        $compras = \App\Models\Compra::whereNotNull('data_retroativa')
            ->where(function ($query) {
                $query->whereNull('data_emissao')
                    ->orWhereRaw('DATE(data_emissao) <> DATE(data_retroativa)');
            })
            ->where('empresa_id', $this->empresa_id)
            ->get();

        foreach ($compras as $compra) {
            // Se disponível, extraia a data do XML; caso contrário, use a data retroativa informada.
            $novaDataEmissao = $compra->data_retroativa;
            // Exemplo para usar o XML (caso exista):
            // $dataXML = $this->getDataEmissaoXML($compra);
            // if ($dataXML && $dataXML != $compra->data_emissao) {
            //     $novaDataEmissao = $dataXML;
            // }

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
        $itensAntigos = \App\Models\ItemCompra::where('compra_id', $compraId)->get();

        foreach ($itensAntigos as $item) {
            $prod = \App\Models\Produto::where('id', (int)$item->produto_id)
                ->where('empresa_id', $this->empresa_id)
                ->first();
            if ($prod && $prod->gerenciar_estoque) {
                // Calcula a quantidade negativa para remover o estoque previamente adicionado
                $quantidadeNegativa = -((float)$item->quantidade * $prod->conversao_unitaria);
                $stockMove->pluStock(
                    (int)$prod->id,
                    $quantidadeNegativa,
                    (float)$item->valor_unitario,
                    $prod->filial_id // Use o campo correto que identifica o local do estoque
                );
            }
        }
        // Exclui os registros antigos de itens para esta compra
        \App\Models\ItemCompra::where('compra_id', $compraId)->delete();
        return true;
    }

    public function updateItem(Request $request)
    {
        try {
            $item = ItemCompra::findOrFail($request->itemId); // Localiza o item pelo ID
            $item->quantidade = $request->quantidade; // Atualiza a quantidade
            $item->valor_unitario = str_replace(",", ".", $request->valor); // Atualiza o valor unitário
            $item->save(); // Salva as alterações

            return response()->json(['success' => true]); // Retorna sucesso
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]); // Retorna erro
        }
    }

    public function editarItem(Request $request)
    {
        // Validação para garantir que 'valor_unitario' está presente
        $validated = $request->validate([
            'valor' => 'required|numeric',
            'quantidade' => 'required|numeric',
            'nome' => 'required|string',
        ]);

        // Recupera os dados do item
        $itemId = $request->input('id_item'); // <-- Agora é o ID correto do item
        $quantidade = $request->input('quantidade');
        $valor_unitario = $request->input('valor');
        $compraId = $request->input('compraId');

        // Busca o item diretamente pelo ID
        $item = \App\Models\ItemCompra::where('id', $itemId)
            ->where('compra_id', $compraId)
            ->first();

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Item não encontrado']);
        }

        // Atualiza os dados do item
        $item->quantidade = $quantidade;
        $item->valor_unitario = $valor_unitario;
        $item->save();

        return response()->json(['status' => 'success']);
    }

    public function excluirItem(Request $request)
    {
        try {
            // Iniciar transação
            DB::transaction(function() use ($request) {
                // Encontrar o item da compra pelo ID
                $item = ItemCompra::findOrFail($request->itemId);

                // Remover o item do estoque (se necessário)
                $prod = Produto::find($item->produto_id);
                if ($prod->gerenciar_estoque) {
                    // Se o produto gerencia estoque, devemos registrar o movimento de exclusão no estoque
                    $stockMove = new StockMove();
                    $stockMove->pluStock($item->produto_id, -$item->quantidade, -$item->valor_unitario, $item->compra->filial_id);
                }

                // Excluir o item da compra
                $item->delete();

                // Definir mensagem de sucesso para a sessão
                session()->flash('mensagem_sucesso', 'Item excluído com sucesso!');
            });

            // Retornar resposta JSON de sucesso
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            // Retornar erro em caso de exceção
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

}
