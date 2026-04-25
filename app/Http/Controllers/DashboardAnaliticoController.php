<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardAnaliticoController extends Controller
{
    protected $empresa_id = null;
    protected $sessionData = [];

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $sessionData = session('user_logged');

            if (!$sessionData) {
                return redirect('/login');
            }

            $this->sessionData = $sessionData;
            $this->empresa_id = $sessionData['empresa'] ?? $sessionData['empresa_id'] ?? null;

            return $next($request);
        });
    }

    private function getLocalPadraoSession()
    {
        return $this->sessionData['local_padrao']
            ?? session('user_logged.local_padrao')
            ?? null;
    }

    private function normalizarFilialPadrao($localPadrao)
    {
        if ($localPadrao === null || $localPadrao === '') {
            return null;
        }

        if ((string)$localPadrao === '-1' || (string)$localPadrao === 'matriz') {
            return 'matriz';
        }

        return (string)$localPadrao;
    }

    private function resolverFilialInicial(Request $request)
    {
        $filialSolicitada = $request->get('filial_id');

        if ($filialSolicitada !== null && $filialSolicitada !== '') {
            return (string)$filialSolicitada;
        }

        $filialPadrao = $this->normalizarFilialPadrao($this->getLocalPadraoSession());

        if ($filialPadrao !== null && __filial_solicitada_valida_para_usuario($filialPadrao)) {
            return $filialPadrao;
        }

        if (__usuario_pode_ver_todos_locais()) {
            return 'todos';
        }

        $locaisPermitidos = __usuario_locais_ids_logado();

        if (in_array('-1', array_map('strval', (array)$locaisPermitidos), true)) {
            return 'matriz';
        }

        foreach ((array)$locaisPermitidos as $localId) {
            if ((string)$localId !== '-1' && !empty($localId)) {
                return (string)$localId;
            }
        }

        return 'todos';
    }

    public function index(Request $request)
    {
        $empresa_id = $this->empresa_id;
        $periodo = $request->get('periodo', '30');
        $filial_id = $this->resolverFilialInicial($request);
        $title = 'Dashboard Analítico Executivo';

        /*
        |--------------------------------------------------------------------------
        | Se o usuário pedir um local fora da sua permissão, zera o resultado
        |--------------------------------------------------------------------------
        */
        if (!__filial_solicitada_valida_para_usuario($filial_id)) {
            $filiais = collect();
            return view('dashboard_novo.index', [
                'title' => $title,
                'totalFaturamento' => 0,
                'ticketMedio' => 0,
                'receberVencidas' => 0,
                'receberNoPeriodo' => 0,
                'pagarVencidas' => 0,
                'pagarNoPeriodo' => 0,
                'custoEstoque' => 0,
                'totalClientes' => 0,
                'totalProdutos' => 0,
                'saldosContas' => collect(),
                'produtosMaisVendidos' => collect(),
                'topClientes' => collect(),
                'produtosComEstoque' => collect(),
                'produtosAlerta' => collect(),
                'filiais' => $filiais,
                'filial_id' => $filial_id,
                'periodo' => $periodo,
                'diasDesc' => 'Sem permissão para o local selecionado',
                'localPadraoAtual' => $this->normalizarFilialPadrao($this->getLocalPadraoSession()),
            ]);
        }

        $hojeObj = Carbon::now();

        if ($periodo === 'hoje') {
            $dataInicial = $hojeObj->copy()->startOfDay()->toDateTimeString();
            $diasDesc = 'Hoje';
        } elseif ($periodo === 'mes') {
            $dataInicial = $hojeObj->copy()->startOfMonth()->toDateTimeString();
            $diasDesc = 'Mês Atual';
        } else {
            $diasNum = is_numeric($periodo) ? (int)$periodo : 30;
            $dataInicial = $hojeObj->copy()->subDays($diasNum)->startOfDay()->toDateTimeString();
            $diasDesc = "Últimos {$diasNum} dias";
        }

        $hojeStr = Carbon::now()->format('Y-m-d');
        $dataFimFinanceiro = Carbon::now()->addDays(30)->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | Filiais visíveis no filtro
        |--------------------------------------------------------------------------
        */
        if (__usuario_pode_ver_todos_locais()) {
            $filiais = DB::table('filials')
                ->where('empresa_id', $empresa_id)
                ->select('id', 'razao_social as nome')
                ->orderBy('razao_social')
                ->get();
        } else {
            $locaisPermitidos = __usuario_locais_ids_logado();
            $idsPermitidos = array_values(array_filter($locaisPermitidos, function ($item) {
                return (string)$item !== '-1';
            }));

            $filiais = DB::table('filials')
                ->where('empresa_id', $empresa_id)
                ->whereIn('id', count($idsPermitidos) > 0 ? $idsPermitidos : [0])
                ->select('id', 'razao_social as nome')
                ->orderBy('razao_social')
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | FATURAMENTO
        |--------------------------------------------------------------------------
        */
        $qVendas = DB::table('vendas')
            ->where('tipo_pagamento', '!=', '90')
            ->where('data_registro', '>=', $dataInicial);

        __aplicar_filtro_empresa_filial($qVendas);

        $vendasQtd = $qVendas->count();
        $vendasTotal = $qVendas->sum('valor_total');

        $qCaixa = DB::table('venda_caixas')
            ->where('tipo_pagamento', '!=', '90')
            ->where('data_registro', '>=', $dataInicial);

        __aplicar_filtro_empresa_filial($qCaixa);

        $caixaQtd = $qCaixa->count();
        $caixaTotal = $qCaixa->sum('valor_total');

        $totalFaturamento = (float)$vendasTotal + (float)$caixaTotal;
        $ticketMedio = ($vendasQtd + $caixaQtd) > 0
            ? $totalFaturamento / ($vendasQtd + $caixaQtd)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | CONTAS A RECEBER
        |--------------------------------------------------------------------------
        */
        $qRecVenc = DB::table('conta_recebers')
            ->where('status', 0)
            ->where('data_vencimento', '<', $hojeStr);

        __aplicar_filtro_empresa_filial($qRecVenc);

        $receberVencidas = (float)$qRecVenc->sum('valor_integral');

        $qRecPer = DB::table('conta_recebers')
            ->where('status', 0)
            ->whereBetween('data_vencimento', [$hojeStr, $dataFimFinanceiro]);

        __aplicar_filtro_empresa_filial($qRecPer);

        $receberNoPeriodo = (float)$qRecPer->sum('valor_integral');

        /*
        |--------------------------------------------------------------------------
        | CONTAS A PAGAR
        |--------------------------------------------------------------------------
        */
        $qPagVenc = DB::table('conta_pagars')
            ->where('status', 0)
            ->where('data_vencimento', '<', $hojeStr);

        __aplicar_filtro_empresa_filial($qPagVenc);

        $pagarVencidas = (float)$qPagVenc->sum('valor_integral');

        $qPagPer = DB::table('conta_pagars')
            ->where('status', 0)
            ->whereBetween('data_vencimento', [$hojeStr, $dataFimFinanceiro]);

        __aplicar_filtro_empresa_filial($qPagPer);

        $pagarNoPeriodo = (float)$qPagPer->sum('valor_integral');

        /*
        |--------------------------------------------------------------------------
        | PRODUTOS COM ESTOQUE
        |--------------------------------------------------------------------------
        */
        $qEstoque = DB::table('produtos as p')
            ->join('estoques as e', 'p.id', '=', 'e.produto_id')
            ->where('p.empresa_id', $empresa_id)
            ->where('e.quantidade', '>', 0)
            ->select('p.nome', 'e.quantidade', 'p.unidade_venda', 'p.valor_venda')
            ->orderBy('e.quantidade', 'desc');

        __aplicar_filtro_empresa_filial($qEstoque, 'e');
        $produtosComEstoque = $qEstoque->get();

        /*
        |--------------------------------------------------------------------------
        | ALERTA DE ESTOQUE
        |--------------------------------------------------------------------------
        */
        $qAlerta = DB::table('produtos as p')
            ->join('estoques as e', 'p.id', '=', 'e.produto_id')
            ->where('p.empresa_id', $empresa_id)
            ->where('p.tipo_item', '00')
            ->where('e.quantidade', '<=', 5)
            ->select('p.nome', 'e.quantidade', 'p.unidade_venda')
            ->orderBy('e.quantidade', 'asc');

        __aplicar_filtro_empresa_filial($qAlerta, 'e');
        $produtosAlerta = $qAlerta->get();

        /*
        |--------------------------------------------------------------------------
        | KPIs
        |--------------------------------------------------------------------------
        */
        $totalClientes = DB::table('clientes')
            ->where('empresa_id', $empresa_id)
            ->count();

        $totalProdutos = DB::table('produtos')
            ->where('empresa_id', $empresa_id)
            ->count();

        $qCusto = DB::table('estoques')
            ->selectRaw('SUM(quantidade * valor_compra) as total');

        __aplicar_filtro_empresa_filial($qCusto);

        $custoEstoque = (float)($qCusto->first()->total ?? 0);

        /*
        |--------------------------------------------------------------------------
        | GRÁFICOS
        |--------------------------------------------------------------------------
        */
        $qGrafProd = DB::table('item_vendas as iv')
            ->join('produtos as p', 'iv.produto_id', '=', 'p.id')
            ->join('vendas as v', 'iv.venda_id', '=', 'v.id')
            ->select('p.nome', DB::raw('SUM(iv.quantidade) as total_qtd'))
            ->where('v.empresa_id', $empresa_id)
            ->where('v.data_registro', '>=', $dataInicial)
            ->groupBy('p.id', 'p.nome')
            ->orderBy('total_qtd', 'desc')
            ->limit(5);

        __aplicar_filtro_empresa_filial($qGrafProd, 'v');
        $produtosMaisVendidos = $qGrafProd->get();

        $qGrafCli = DB::table('vendas as v')
            ->join('clientes as c', 'v.cliente_id', '=', 'c.id')
            ->select('c.razao_social as nome', DB::raw('SUM(v.valor_total) as total'))
            ->where('v.empresa_id', $empresa_id)
            ->where('v.data_registro', '>=', $dataInicial)
            ->groupBy('c.id', 'c.razao_social')
            ->orderBy('total', 'desc')
            ->limit(5);

        __aplicar_filtro_empresa_filial($qGrafCli, 'v');
        $topClientes = $qGrafCli->get();

        /*
        |--------------------------------------------------------------------------
        | SALDOS DAS CONTAS
        |--------------------------------------------------------------------------
        */
        $qSaldos = DB::table('conta_empresas as ce')
            ->leftJoin('plano_contas as pc', 'pc.id', '=', 'ce.plano_conta_id')
            ->leftJoin('usuarios as u', 'u.id', '=', 'ce.usuario_id')
            ->leftJoin('filials as f', 'f.id', '=', 'ce.filial_id')
            ->where('ce.exibir_dashboard_analitico', 1)
            ->select(
                'ce.*',
                'pc.descricao as plano_descricao',
                'u.nome as usuario_nome',
                'f.razao_social as filial_nome'
            )
            ->orderBy('ce.nome');

        __aplicar_filtro_empresa_filial($qSaldos, 'ce');
        $saldosContas = $qSaldos->get();

        return view('dashboard_novo.index', compact(
            'title',
            'totalFaturamento',
            'ticketMedio',
            'receberVencidas',
            'receberNoPeriodo',
            'pagarVencidas',
            'pagarNoPeriodo',
            'custoEstoque',
            'totalClientes',
            'totalProdutos',
            'saldosContas',
            'produtosMaisVendidos',
            'topClientes',
            'produtosComEstoque',
            'produtosAlerta',
            'filiais',
            'filial_id',
            'periodo',
            'diasDesc'
        ))->with('localPadraoAtual', $this->normalizarFilialPadrao($this->getLocalPadraoSession()));
    }
}
