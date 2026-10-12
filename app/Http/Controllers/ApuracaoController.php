<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoriaConta;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Utils\ContaEmpresaUtil;

class ApuracaoController extends BaseController
{
    protected $util;

    public function __construct(ContaEmpresaUtil $util){
        parent::__construct();
        $this->util = $util;
        $this->redirectPage = '/apuracao';
    }

    protected function rules(): array { return []; }
    protected function messages(): array { return []; }

    public function index(Request $request)
    {
        $mes = (int) ($request->mes ?? date('m'));
        $ano = (int) ($request->ano ?? date('Y'));
        $filial_id = $request->filled('filial_id') ? $request->filial_id : null;
        $regime = $request->regime ?? 'competencia';

        // Inicializa as estruturas básicas para a View
        $dados = [
            'receita_bruta'   => 0,
            'deducao_venda'   => 0, // Novo
            'devolucao'       => 0,
            'venda_cancelada' => 0, // <-- NOVA LINHA
            'cmv'             => 0,
            'pessoal'         => 0, // Novo
            'administrativa'  => 0,
            'operacional'     => 0,
            'tributaria'      => 0,
            'financeira'      => 0,
            'nao_operacional' => 0,
        ];

        // Inicialize também no array $detalhes para que os sub-itens apareçam ao clicar
        $detalhes = [
            'receita_bruta'   => [],
            'deducao_venda'   => [], // Novo
            'venda_cancelada' => [], // <-- NOVA LINHA
            'pessoal'         => [], // Novo
            'administrativa'  => [],
            'operacional'     => [],
            'tributaria'      => [],
            'financeira'      => [],
            'nao_operacional' => [],
        ];

        // 1. RECEITA BRUTA
        $receitasObtidas = $this->calcularReceitaBruta($mes, $ano, $filial_id);
        $dados['receita_bruta'] = array_sum($receitasObtidas);

        foreach ($receitasObtidas as $nome => $total) {
            $detalhes['receita_bruta'][] = (object)[
                'categoria_nome' => $nome,
                'total' => $total
            ];
        }

        // 2. DEDUÇÕES (Devolução de Venda)
        $dados['devolucao'] = $this->calcularDevolucoesVenda($mes, $ano, $filial_id);

        // 3. CUSTO DAS MERCADORIAS VENDIDAS (CMV)
        $cmvData = $this->calcularCMV($mes, $ano, $filial_id, $request);
        $cmvReal = $cmvData['cmv_real'];
        $estoqueInicial = $cmvData['estoque_inicial'];
        $comprasDoMes = $cmvData['entradas_liquidas'];
        $estoqueFinal = $cmvData['estoque_final'];

        // 4. MOVIMENTAÇÕES FINANCEIRAS DETALHADAS
        $dados['financeira'] = $this->calcularMovimentacoesFinanceiras($mes, $ano, $filial_id);

        // 5. DEMAIS DESPESAS OPERACIONAIS / ADMINISTRATIVAS / TRIBUTÁRIAS
        $financeiroGrupado = $this->calcularDespesasGrupadas($mes, $ano, $filial_id);
        foreach ($financeiroGrupado as $grupo => $valor) {
            if ($grupo !== 'financeira' && array_key_exists($grupo, $dados)) {
                // CORREÇÃO: Força o não_operacional a ser negativo, pois vem do contas a pagar (despesa)
                $dados[$grupo] = ($grupo === 'nao_operacional') ? -$valor : $valor;
            }
        }

        // Alimenta os detalhes de cada grupo (sub-itens do DRE)
        foreach ($detalhes as $grupo => $lista) {
            if ($grupo == 'receita_bruta') continue;

            if ($grupo == 'financeira') {
                $detalhes['financeira'] = $this->obterDetalhesFinanceiros($mes, $ano, $filial_id);
            } else {
                $det = $this->obterDetalhesPorGrupo($grupo, $mes, $ano, $filial_id);
                // Inverte o sinal dos detalhes do não operacional para a visualização
                if ($grupo === 'nao_operacional') {
                    foreach ($det as &$d) $d->total = -abs($d->total);
                }
                $detalhes[$grupo] = $det;
            }
        }

        // Dados auxiliares para a View
        $nomeMatriz = DB::table('empresas')->where('id', $this->empresa_id)->value('nome_fantasia') ?? 'MATRIZ';
        $filiais = DB::table('filials')->where('empresa_id', $this->empresa_id)->get();

        $mesAnterior = $mes == 1 ? 12 : $mes - 1;
        $anoAnterior = $mes == 1 ? $ano - 1 : $ano;
        $saldoAnterior = DB::table('fechamentos_mensais')
            ->where('empresa_id', $this->empresa_id)
            ->where('mes', $mesAnterior)
            ->where('ano', $anoAnterior)
            ->value('lucro_prejuizo_liquido') ?? 0;

        // Trata o ID da filial para a busca
        $f_id = null;
        if ($filial_id == 'matriz') {
            $f_id = -1; // Matriz
        } elseif ($filial_id != 'todos' && $filial_id != '') {
            $f_id = $filial_id; // Uma filial específica
        }

        // Busca a trava. Se houver um fechamento Global (NULL), ele trava as filiais também!
        $apuracao = DB::table('fechamentos_mensais')
            ->where('empresa_id', $this->empresa_id)
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->where(function($query) use ($f_id) {
                if ($f_id === null) {
                    $query->whereNull('filial_id');
                } else {
                    $query->where('filial_id', $f_id)
                        ->orWhereNull('filial_id'); // A trava Global bloqueia as individuais
                }
            })
            ->first();

        return view('apuracao.index', compact(
            'mes', 'ano', 'filial_id', 'filiais', 'nomeMatriz', 'saldoAnterior', 'regime',
            'dados', 'detalhes', 'cmvReal', 'estoqueInicial', 'comprasDoMes', 'apuracao', 'estoqueFinal'
        ))->with('title', 'DRE - Demonstração do Resultado do Exercício');
    }

    private function calcularReceitaBruta($mes, $ano, $filial_id)
    {
        $queryVendas = DB::table('vendas')
            ->where('empresa_id', $this->empresa_id)
            ->where('estado', 'APROVADO')
            ->whereNotNull('tipo_pagamento')
            ->where('tipo_pagamento', '!=', '90')
            ->whereMonth('data_emissao', $mes)
            ->whereYear('data_emissao', $ano);

        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
            $queryVendas->where('filial_id', $filial_id);
        } elseif ($filial_id == 'matriz') {
            $queryVendas->whereNull('filial_id');
        }

        $queryCtes = DB::table('ctes')
            ->where('empresa_id', $this->empresa_id)
            ->where('estado', 'APROVADO')
            ->whereMonth('data_emissao', $mes)
            ->whereYear('data_emissao', $ano);

        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
            $queryCtes->where('filial_id', $filial_id);
        } elseif ($filial_id == 'matriz') {
            $queryCtes->whereNull('filial_id');
        }

        return [
            'Vendas de Mercadorias' => $queryVendas->sum('valor_total'),
            'Serviços de Transporte' => $queryCtes->sum('valor_receber'),
        ];
    }

    private function calcularDevolucoesVenda($mes, $ano, $filial_id)
    {
        $query = DB::table('devolucaos')
            ->where('empresa_id', $this->empresa_id)
            ->where('tipo', 0)
            ->whereMonth('created_at', $mes)
            ->whereYear('created_at', $ano);

        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
            $query->where('filial_id', $filial_id);
        } elseif ($filial_id == 'matriz') {
            $query->whereNull('filial_id');
        }

        return $query->sum('valor_devolvido');
    }

    private function calcularCMV($mes, $ano, $filial_id, Request $request)
    {
        $dataInicial = \Carbon\Carbon::create($ano, $mes, 1)->startOfDay()->toDateTimeString();
        $dataFinal = \Carbon\Carbon::create($ano, $mes)->endOfMonth()->toDateTimeString();
        $empresaId = $this->empresa_id;
        $regime = request('regime', 'competencia'); // Captura o regime da tela

        // 1. QUERIES DE MOVIMENTAÇÃO DE ESTOQUE
        $comprasSql = "SELECT item_compras.produto_id, COALESCE(compras.filial_id, -1) as filial_id, item_compras.quantidade as qtd, compras.data_emissao as data, 'entrada' as tipo FROM item_compras JOIN compras ON compras.id = item_compras.compra_id WHERE compras.empresa_id = {$empresaId} AND compras.estado IN ('APROVADO', 'IMPORTADO') AND compras.data_emissao <= '{$dataFinal}'";
        $vendasSql = "SELECT item_vendas.produto_id, COALESCE(vendas.filial_id, -1) as filial_id, item_vendas.quantidade as qtd, vendas.data_emissao as data, 'saida' as tipo FROM item_vendas JOIN vendas ON vendas.id = item_vendas.venda_id WHERE vendas.empresa_id = {$empresaId} AND vendas.estado = 'APROVADO' AND vendas.data_emissao <= '{$dataFinal}'";
        $vendasCaixaSql = "SELECT item_venda_caixas.produto_id, COALESCE(venda_caixas.filial_id, -1) as filial_id, item_venda_caixas.quantidade as qtd, venda_caixas.data_emissao as data, 'saida' as tipo FROM item_venda_caixas JOIN venda_caixas ON venda_caixas.id = item_venda_caixas.venda_caixa_id WHERE venda_caixas.empresa_id = {$empresaId} AND venda_caixas.estado = 'APROVADO' AND venda_caixas.data_emissao <= '{$dataFinal}'";
        $alteracoesSql = "SELECT produto_id, COALESCE(filial_id, -1) as filial_id, quantidade as qtd, created_at as data, IF(tipo = 'incremento', 'entrada', 'saida') as tipo FROM alteracao_estoques WHERE empresa_id = {$empresaId} AND created_at <= '{$dataFinal}'";
        $transfSaidaSql = "SELECT item_transferencias.produto_id, COALESCE(transferencias.filial_saida_id, -1) as filial_id, item_transferencias.quantidade as qtd, transferencias.created_at as data, 'saida' as tipo FROM item_transferencias JOIN transferencias ON transferencias.id = item_transferencias.transferencia_id WHERE transferencias.empresa_id = {$empresaId} AND transferencias.created_at <= '{$dataFinal}'";
        $devolucoesSql = "SELECT p.produto_id, COALESCE(devolucaos.filial_id, -1) as filial_id, item_devolucaos.quantidade as qtd, devolucaos.data_registro as data, IF(devolucaos.tipo = 0, 'entrada', 'saida') as tipo FROM item_devolucaos JOIN devolucaos ON devolucaos.id = item_devolucaos.devolucao_id JOIN (SELECT MIN(id) as produto_id, referencia, nome FROM produtos WHERE empresa_id = {$empresaId} GROUP BY referencia, nome) p ON p.referencia = item_devolucaos.cod AND p.nome = item_devolucaos.nome WHERE devolucaos.empresa_id = {$empresaId} AND devolucaos.data_registro <= '{$dataFinal}'";

        $unifiedSql = "($comprasSql) UNION ALL ($vendasSql) UNION ALL ($vendasCaixaSql) UNION ALL ($alteracoesSql) UNION ALL ($transfSaidaSql) UNION ALL ($devolucoesSql)";

        $saldosSql = "SELECT produto_id, filial_id, SUM(CASE WHEN data < '{$dataInicial}' THEN IF(tipo = 'entrada', qtd, -qtd) ELSE 0 END) as saldo_inicial, SUM(IF(tipo = 'entrada', qtd, -qtd)) as saldo_final FROM ({$unifiedSql}) as mov GROUP BY produto_id, filial_id";

        $queryEstoque = DB::table('produtos')
            ->join(DB::raw("({$saldosSql}) as saldos"), 'produtos.id', '=', 'saldos.produto_id')
            ->leftJoin('categorias', 'categorias.id', '=', 'produtos.categoria_id')
            ->where('produtos.empresa_id', $empresaId)
            ->where('produtos.gerenciar_estoque', 1)
            ->where(function($q) {
                $q->where('produtos.tipo_item', '00')->orWhere('categorias.nome', 'like', '%SUCATA%');
            });

        if ($filial_id && $filial_id != 'todos') {
            $f_id = ($filial_id == 'matriz') ? -1 : $filial_id;
            $queryEstoque->where('saldos.filial_id', $f_id);
        }

        $queryEstoque->select(
            DB::raw('SUM(COALESCE(saldos.saldo_inicial, 0) * produtos.valor_compra) as total_inicial'),
            DB::raw('SUM(COALESCE(saldos.saldo_final, 0) * produtos.valor_compra) as total_final')
        );

        $totais = $queryEstoque->first();
        $estoqueInicial = $request->filled('simular_est_inicial') ? (float)$request->simular_est_inicial : (float)($totais->total_inicial ?? 0);
        $estoqueFinal = $request->filled('simular_est_final') ? (float)$request->simular_est_final : (float)($totais->total_final ?? 0);

        // 2. COMPRAS LÍQUIDAS (FINANCEIRO)
        if ($request->filled('simular_compras')) {
            $compras = (float) $request->simular_compras;
        } else {
            // A. Soma Contas a Pagar
            $queryPagar = DB::table('conta_pagars as p')
                ->join('categoria_contas as c', 'p.categoria_id', '=', 'c.id')
                ->leftJoin('compras as comp', 'p.compra_id', '=', 'comp.id')
                ->where('p.empresa_id', $this->empresa_id)
                ->where('c.dre_grupo', 'cmv')
                ->whereNull('p.conta_id_origem'); // Apenas o contas a pagar tem essa coluna

            if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
                $queryPagar->where('p.filial_id', $filial_id);
            } elseif ($filial_id == 'matriz') {
                $queryPagar->whereNull('p.filial_id');
            }

            if ($regime == 'caixa') {
                $queryPagar->where('p.status', 1)
                    ->whereMonth('p.data_pagamento', $mes)
                    ->whereYear('p.data_pagamento', $ano);
                $totalPagar = (float) $queryPagar->sum('p.valor_pago');
            } else {
                $queryPagar->whereRaw('MONTH(COALESCE(comp.data_emissao, p.data_emissao, p.data_vencimento)) = ?', [$mes])
                    ->whereRaw('YEAR(COALESCE(comp.data_emissao, p.data_emissao, p.data_vencimento)) = ?', [$ano]);
                $totalPagar = (float) $queryPagar->sum('p.valor_integral');
            }

            // B. Subtrai Devoluções de Compras
            $queryReceber = DB::table('conta_recebers as r')
                ->join('categoria_contas as c', 'r.categoria_id', '=', 'c.id')
                ->where('r.empresa_id', $this->empresa_id)
                ->where('c.dre_grupo', 'cmv');
            // REMOVIDO DAQUI O WHERENULL CONTA_ID_ORIGEM

            if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
                $queryReceber->where('r.filial_id', $filial_id);
            } elseif ($filial_id == 'matriz') {
                $queryReceber->whereNull('r.filial_id');
            }

            if ($regime == 'caixa') {
                $queryReceber->where('r.status', 1)
                    ->whereRaw('MONTH(COALESCE(r.data_recebimento, r.data_pagamento)) = ?', [$mes])
                    ->whereRaw('YEAR(COALESCE(r.data_recebimento, r.data_pagamento)) = ?', [$ano]);
                $totalReceber = (float) $queryReceber->sum('r.valor_recebido');
            } else {
                $queryReceber->whereRaw('MONTH(COALESCE(r.nf_data_emissao, r.data_vencimento)) = ?', [$mes])
                    ->whereRaw('YEAR(COALESCE(r.nf_data_emissao, r.data_vencimento)) = ?', [$ano]);
                $totalReceber = (float) $queryReceber->sum('r.valor_integral');
            }

            $compras = $totalPagar - $totalReceber;
        }

        return [
            'estoque_inicial' => $estoqueInicial,
            'entradas_liquidas' => $compras,
            'estoque_final' => $estoqueFinal,
            'cmv_real' => ($estoqueInicial + $compras) - $estoqueFinal
        ];
    }

    private function calcularMovimentacoesFinanceiras($mes, $ano, $filial_id)
    {
        // 1. Juros e Multas PAGOS (Contas a Pagar)
        $queryJurosPagar = DB::table('conta_pagars')
            ->where('empresa_id', $this->empresa_id)
            ->whereMonth('data_emissao', $mes)
            ->whereYear('data_emissao', $ano);
        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') $queryJurosPagar->where('filial_id', $filial_id);
        $jurosE_MultasPagos = (float) $queryJurosPagar->sum(DB::raw('juros + multa'));

        // 2. Descontos CONCEDIDOS (Contas a Receber) -> É uma despesa financeira pois reduz o faturamento
        $queryDescontoReceber = DB::table('conta_recebers')
            ->where('empresa_id', $this->empresa_id)
            ->whereRaw('MONTH(COALESCE(nf_data_emissao, data_vencimento)) = ?', [$mes])
            ->whereRaw('YEAR(COALESCE(nf_data_emissao, data_vencimento)) = ?', [$ano]);

        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
            $queryDescontoReceber->where('filial_id', $filial_id);
        }

        $descontosConcedidos = (float) $queryDescontoReceber->sum('desconto');

        // 3. Contas lançadas com o grupo DRE 'financeira' (Ex: Tarifas Bancárias)
        $queryDespesasFixasFin = DB::table('conta_pagars as p')
            ->join('categoria_contas as c', 'p.categoria_id', '=', 'c.id')
            ->where('p.empresa_id', $this->empresa_id)
            ->where('c.dre_grupo', 'financeira')
            ->whereMonth('p.data_emissao', $mes)
            ->whereYear('p.data_emissao', $ano);
        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') $queryDespesasFixasFin->where('p.filial_id', $filial_id);
        $despesasContasFin = (float) $queryDespesasFixasFin->sum('p.valor_integral');

        // Retorna a soma de tudo convertida para negativo (despesa)
        return -($jurosE_MultasPagos + $descontosConcedidos + $despesasContasFin);
    }

    private function obterDetalhesFinanceiros($mes, $ano, $filial_id)
    {
        $detalhes = [];

        // Detalhe 1: Juros e Multas Pagos
        $queryJuros = DB::table('conta_pagars')
            ->where('empresa_id', $this->empresa_id)
            ->whereMonth('data_emissao', $mes)
            ->whereYear('data_emissao', $ano);
        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') $queryJuros->where('filial_id', $filial_id);
        $totalJuros = $queryJuros->sum(DB::raw('juros + multa'));

        if ($totalJuros > 0) {
            $detalhes[] = (object)['categoria_nome' => 'Juros e Multas Pagos', 'total' => -$totalJuros];
        }

        /// Detalhe 2: Descontos Concedidos no Recebimento
        $queryDescontos = DB::table('conta_recebers')
            ->where('empresa_id', $this->empresa_id)
            ->whereRaw('MONTH(COALESCE(nf_data_emissao, data_vencimento)) = ?', [$mes])
            ->whereRaw('YEAR(COALESCE(nf_data_emissao, data_vencimento)) = ?', [$ano]);

        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
            $queryDescontos->where('filial_id', $filial_id);
        }

        $totalDescontos = $queryDescontos->sum('desconto');

        if ($totalDescontos > 0) {
            $detalhes[] = (object)['categoria_nome' => 'Descontos Concedidos (CR)', 'total' => -$totalDescontos];
        }

        // Detalhe 3: Demais categorias financeiras
        $categoriasFin = $this->obterDetalhesPorGrupo('financeira', $mes, $ano, $filial_id);
        foreach ($categoriasFin as $cat) {
            $detalhes[] = (object)['categoria_nome' => $cat->categoria_nome, 'total' => -$cat->total];
        }

        return $detalhes;
    }

    private function calcularDespesasGrupadas($mes, $ano, $filial_id)
    {
        $regime = request('regime', 'competencia');

        // 1. Busca Despesas Normais do Financeiro (Contas a Pagar)
        $query = DB::table('conta_pagars as p')
            ->join('categoria_contas as c', 'p.categoria_id', '=', 'c.id')
            ->leftJoin('compras as comp', 'p.compra_id', '=', 'comp.id')
            ->select('c.dre_grupo')
            ->where('p.empresa_id', $this->empresa_id)
            ->where('c.incluir_resultado', 1)
            ->whereNull('p.conta_id_origem');

        if ($regime == 'caixa') {
            $query->addSelect(DB::raw("SUM(p.valor_pago) as total"))
                ->where('p.status', 1)
                ->whereMonth('p.data_pagamento', $mes)
                ->whereYear('p.data_pagamento', $ano);
        } else {
            $query->addSelect(DB::raw("SUM(p.valor_integral) as total"))
                ->whereRaw('MONTH(COALESCE(comp.data_emissao, p.data_emissao, p.data_vencimento)) = ?', [$mes])
                ->whereRaw('YEAR(COALESCE(comp.data_emissao, p.data_emissao, p.data_vencimento)) = ?', [$ano]);
        }

        $query->groupBy('c.dre_grupo');

        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
            $query->where('p.filial_id', $filial_id);
        } elseif ($filial_id == 'matriz') {
            $query->whereNull('p.filial_id');
        }

        $despesas = $query->pluck('total', 'c.dre_grupo')->toArray();

        // =========================================================================
        // 2. BUSCA DEVOLUÇÕES DE VENDA QUE ENTRARAM PELA ROTINA DE COMPRAS
        // =========================================================================
        $queryDevCompras = DB::table('compras')
            ->where('empresa_id', $this->empresa_id)
            ->whereIn('estado', ['APROVADO', 'IMPORTADO'])
            ->whereMonth('data_emissao', $mes)
            ->whereYear('data_emissao', $ano)
            ->whereExists(function ($query) {
                // Filtra as NFs que possuem itens com CFOP de Devolução de Venda
                $query->select(DB::raw(1))
                    ->from('item_compras')
                    ->whereColumn('item_compras.compra_id', 'compras.id')
                    ->whereIn('item_compras.cfop_entrada', [ // <-- CORREÇÃO AQUI
                        '1201', '1.201', '2201', '2.201',
                        '1202', '1.202', '2202', '2.202',
                        '1410', '1.410', '2410', '2.410',
                        '1411', '1.411', '2411', '2.411'
                    ]);
            });

        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
            $queryDevCompras->where('filial_id', $filial_id);
        } elseif ($filial_id == 'matriz') {
            $queryDevCompras->whereNull('filial_id');
        }

        $totalDevolucoes = (float) $queryDevCompras->sum('valor');

        $totalDevolucoes = (float) $queryDevCompras->sum('valor');

        if ($totalDevolucoes > 0) {
            // AGORA VAI PARA O SEU GRUPO EXCLUSIVO DE VENDAS CANCELADAS
            $despesas['venda_cancelada'] = $totalDevolucoes;
        }

        return $despesas;
    }

    private function obterDetalhesPorGrupo($grupo, $mes, $ano, $filial_id)
    {
        $regime = request('regime', 'competencia');

        $query = DB::table('conta_pagars as p')
            ->join('categoria_contas as c', 'p.categoria_id', '=', 'c.id')
            ->leftJoin('compras as comp', 'p.compra_id', '=', 'comp.id')
            ->select('c.nome as categoria_nome')
            ->where('p.empresa_id', $this->empresa_id)
            ->where('c.dre_grupo', $grupo)
            ->where('c.incluir_resultado', 1)
            ->whereNull('p.conta_id_origem');

        if ($regime == 'caixa') {
            $query->addSelect(DB::raw("SUM(p.valor_pago) as total"))
                ->where('p.status', 1)
                ->whereMonth('p.data_pagamento', $mes)
                ->whereYear('p.data_pagamento', $ano);
        } else {
            $query->addSelect(DB::raw("SUM(p.valor_integral) as total"))
                ->whereRaw('MONTH(COALESCE(comp.data_emissao, p.data_emissao, p.data_vencimento)) = ?', [$mes])
                ->whereRaw('YEAR(COALESCE(comp.data_emissao, p.data_emissao, p.data_vencimento)) = ?', [$ano]);
        }

        $query->groupBy('c.nome');

        if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
            $query->where('p.filial_id', $filial_id);
        } elseif ($filial_id == 'matriz') {
            $query->whereNull('p.filial_id');
        }

        $detalhes = $query->get()->toArray();

        // =========================================================================
        // INJETA AS DEVOLUÇÕES DE COMPRA NA EXIBIÇÃO ANALÍTICA
        // =========================================================================
        if ($grupo == 'venda_cancelada') { // <-- TROQUE AQUI DE 'deducao_venda' PARA 'venda_cancelada'
            $queryDevCompras = DB::table('compras')
                ->where('empresa_id', $this->empresa_id)
                ->whereIn('estado', ['APROVADO', 'IMPORTADO'])
                ->whereMonth('data_emissao', $mes)
                ->whereYear('data_emissao', $ano)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('item_compras')
                        ->whereColumn('item_compras.compra_id', 'compras.id')
                        ->whereIn('item_compras.cfop_entrada', [ // <-- CORREÇÃO AQUI
                            '1201', '1.201', '2201', '2.201',
                            '1202', '1.202', '2202', '2.202',
                            '1410', '1.410', '2410', '2.410',
                            '1411', '1.411', '2411', '2.411'
                        ]);
                });

            if ($filial_id && $filial_id != 'todos' && $filial_id != 'matriz') {
                $queryDevCompras->where('filial_id', $filial_id);
            } elseif ($filial_id == 'matriz') {
                $queryDevCompras->whereNull('filial_id');
            }

            $totalDevolucoes = (float) $queryDevCompras->sum('valor');

            if ($totalDevolucoes > 0) {
                // Adiciona uma linha específica para você saber exatamente de onde vem o dinheiro
                $detalhes[] = (object) [
                    'categoria_nome' => 'Devoluções de Venda (Entradas CFOP 1202/2202)',
                    'total' => $totalDevolucoes
                ];
            }
        }

        return $detalhes;
    }

    public function finalizar(Request $request)
    {
        $mes = $request->mes;
        $ano = $request->ano;

        // 1. Trata o ID da Filial que veio da tela
        $f_id = null;
        if ($request->filial_id == 'matriz') {
            $f_id = -1;
        } elseif ($request->filial_id != 'todos' && $request->filial_id != '') {
            $f_id = $request->filial_id;
        }

        DB::beginTransaction();
        try {
            $userId = auth()->check() ? auth()->user()->id : ($this->usuario_id ?? 1);

            // 2. Definir a data limite (último segundo do mês apurado)
            $dataFinal = \Carbon\Carbon::create($ano, $mes)->endOfMonth()->toDateTimeString();
            $empresaId = $this->empresa_id;

            // 3. Construção das subqueries em formato RAW filtrando até a data limite
            $comprasSql = "SELECT item_compras.produto_id, COALESCE(compras.filial_id, -1) as filial_id, item_compras.quantidade as qtd, 'entrada' as tipo
                           FROM item_compras
                           JOIN compras ON compras.id = item_compras.compra_id
                           WHERE compras.empresa_id = {$empresaId} AND compras.estado IN ('APROVADO', 'IMPORTADO') AND compras.data_emissao <= '{$dataFinal}'";

            $vendasSql = "SELECT item_vendas.produto_id, COALESCE(vendas.filial_id, -1) as filial_id, item_vendas.quantidade as qtd, 'saida' as tipo
                          FROM item_vendas
                          JOIN vendas ON vendas.id = item_vendas.venda_id
                          WHERE vendas.empresa_id = {$empresaId} AND vendas.estado = 'APROVADO' AND vendas.data_emissao <= '{$dataFinal}'";

            $vendasCaixaSql = "SELECT item_venda_caixas.produto_id, COALESCE(venda_caixas.filial_id, -1) as filial_id, item_venda_caixas.quantidade as qtd, 'saida' as tipo
                               FROM item_venda_caixas
                               JOIN venda_caixas ON venda_caixas.id = item_venda_caixas.venda_caixa_id
                               WHERE venda_caixas.empresa_id = {$empresaId} AND venda_caixas.estado = 'APROVADO' AND venda_caixas.data_emissao <= '{$dataFinal}'";

            $alteracoesSql = "SELECT produto_id, COALESCE(filial_id, -1) as filial_id, quantidade as qtd, IF(tipo = 'incremento', 'entrada', 'saida') as tipo
                              FROM alteracao_estoques
                              WHERE empresa_id = {$empresaId} AND created_at <= '{$dataFinal}'";

            $transfSaidaSql = "SELECT item_transferencias.produto_id, COALESCE(transferencias.filial_saida_id, -1) as filial_id, item_transferencias.quantidade as qtd, 'saida' as tipo
                               FROM item_transferencias
                               JOIN transferencias ON transferencias.id = item_transferencias.transferencia_id
                               WHERE transferencias.empresa_id = {$empresaId} AND transferencias.created_at <= '{$dataFinal}'";

            $devolucoesSql = "SELECT p.produto_id, COALESCE(devolucaos.filial_id, -1) as filial_id, item_devolucaos.quantidade as qtd, IF(devolucaos.tipo = 0, 'entrada', 'saida') as tipo
                              FROM item_devolucaos
                              JOIN devolucaos ON devolucaos.id = item_devolucaos.devolucao_id
                              JOIN (SELECT MIN(id) as produto_id, referencia, nome FROM produtos WHERE empresa_id = {$empresaId} GROUP BY referencia, nome) p
                                ON p.referencia = item_devolucaos.cod AND p.nome = item_devolucaos.nome
                              WHERE devolucaos.empresa_id = {$empresaId} AND devolucaos.data_registro <= '{$dataFinal}'";

            $unifiedSql = "($comprasSql) UNION ALL ($vendasSql) UNION ALL ($vendasCaixaSql) UNION ALL ($alteracoesSql) UNION ALL ($transfSaidaSql) UNION ALL ($devolucoesSql)";

            // 4. Calculando o Saldo exato da data final com filtro > 0
            $saldosSql = "SELECT
                            produto_id,
                            filial_id,
                            SUM(IF(tipo = 'entrada', qtd, -qtd)) as saldo_final
                          FROM ({$unifiedSql}) as mov
                          GROUP BY produto_id, filial_id
                          HAVING saldo_final > 0"; // Garante apenas saldos positivos

            // Puxamos os itens juntando com o produto para ter o valor de custo
            $itensEstoque = DB::table('produtos as p')
                ->join(DB::raw("({$saldosSql}) as saldos"), 'p.id', '=', 'saldos.produto_id')
                ->where('p.empresa_id', $empresaId)
                ->where('p.tipo_item', '00')
                ->select('saldos.produto_id', 'saldos.filial_id', 'saldos.saldo_final as quantidade', 'p.valor_compra')
                ->get();

            // Limpa o estoque salvo anteriormente para essa mesma unidade neste mês (para não duplicar caso esteja refazendo o fechamento)
            $queryLimpezaEstoque = DB::table('estoque_mensal_fechamentos')
                ->where('empresa_id', $empresaId)
                ->where('mes', $mes)
                ->where('ano', $ano);

            if ($f_id == -1) {
                $queryLimpezaEstoque->whereNull('filial_id');
            } elseif ($f_id != null) {
                $queryLimpezaEstoque->where('filial_id', $f_id);
            }
            $queryLimpezaEstoque->delete();

            // 5. Salva o fechamento do estoque filtrando pela unidade selecionada
            foreach ($itensEstoque as $item) {
                $item_f_id = $item->filial_id == -1 ? null : $item->filial_id;

                // Grava se for de "Todas" (f_id null), ou se for "Matriz" (-1 e item null), ou se for a Filial exata selecionada
                if ($f_id === null || ($f_id === -1 && $item_f_id === null) || ($f_id == $item_f_id)) {
                    DB::table('estoque_mensal_fechamentos')->insert([
                        'empresa_id'           => $this->empresa_id,
                        'produto_id'           => $item->produto_id,
                        'filial_id'            => $item_f_id,
                        'usuario_id'           => $userId,
                        'quantidade'           => $item->quantidade,
                        'valor_unitario_custo' => $item->valor_compra,
                        'mes'                  => $mes,
                        'ano'                  => $ano,
                        'created_at'           => now(),
                        'updated_at'           => now()
                    ]);
                }
            }

            // Garante que exclui a trava se já existir para a mesma unidade
            DB::table('fechamentos_mensais')
                ->where('empresa_id', $this->empresa_id)
                ->where('mes', $mes)
                ->where('ano', $ano)
                ->where('filial_id', $f_id)
                ->delete();

            // 6. Salva o resumo da apuração na tabela de trava
            DB::table('fechamentos_mensais')->insert([
                'empresa_id'             => $this->empresa_id,
                'filial_id'              => $f_id, // SALVA A UNIDADE AQUI
                'mes'                    => $mes,
                'ano'                    => $ano,
                'lucro_prejuizo_liquido' => $request->lucro_prejuizo_liquido ?? 0,
                'regime'                 => $request->regime ?? 'competencia',
                'status'                 => 'encerrado',
                'usuario_id'             => $userId,
                'data_fechamento'        => now(),
                'created_at'             => now()
            ]);

            DB::commit();
            return redirect()->back()->with('mensagem_sucesso', 'Período encerrado com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('mensagem_erro', 'Erro: ' . $e->getMessage());
        }
    }

    public function reabrir(Request $request)
    {
        try {
            $apuracao = DB::table('fechamentos_mensais')->where('id', $request->apuracao_id)->first();

            if ($apuracao) {
                // 1. Limpa o histórico de estoque salvo para esta trava
                $queryLimpezaEstoque = DB::table('estoque_mensal_fechamentos')
                    ->where('empresa_id', $apuracao->empresa_id)
                    ->where('mes', $apuracao->mes)
                    ->where('ano', $apuracao->ano);

                if ($apuracao->filial_id === null) {
                    // Se foi fechamento global, não filtra filial (apaga o estoque de todas as unidades deste mês)
                } elseif ($apuracao->filial_id == -1) {
                    $queryLimpezaEstoque->whereNull('filial_id'); // Apaga só o estoque da matriz
                } else {
                    $queryLimpezaEstoque->where('filial_id', $apuracao->filial_id); // Apaga só o estoque da filial específica
                }
                $queryLimpezaEstoque->delete();

                // 2. Deleta a trava do mês
                DB::table('fechamentos_mensais')->where('id', $request->apuracao_id)->delete();
            }

            session()->flash('mensagem_sucesso', 'Apuração reaberta e saldos de estoque destravados com sucesso!');
        } catch (\Exception $e) {
            session()->flash('mensagem_erro', 'Erro ao reabrir apuração: ' . $e->getMessage());
        }

        return redirect()->back();
    }
}
