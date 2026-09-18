<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatorioResumoController extends Controller
{
    public function resumoDespesas(Request $request)
    {
        $empresaId = $request->empresa_id ?? auth()->user()->empresa_id;
        $filialId = $request->input('filial_id'); 
        $ano = $request->input('ano', date('Y'));
        $mes = $request->input('mes', date('m')); 

        $dataInicioMes = "{$ano}-{$mes}-01";
        $dataFimMes = date('Y-m-t', strtotime($dataInicioMes));

        $filiais = DB::table('filials')->where('empresa_id', $empresaId)->get();

        // Nome da Unidade
        if ($filialId && $filialId !== 'matriz' && $filialId !== 'todas') {
            $filialSel = $filiais->firstWhere('id', $filialId);
            $nomeUnidade = $filialSel->nome_fantasia ?? $filialSel->razao_social ?? $filialSel->descricao ?? 'FILIAL';
        } elseif ($filialId === 'todas') {
            $nomeUnidade = 'CONSOLIDADO (TODAS AS UNIDADES)';
        } else {
            $matrizInfo = DB::table('config_notas')->where('empresa_id', $empresaId)->first();
            $nomeUnidade = $matrizInfo->nome_fantasia ?? $matrizInfo->razao_social ?? 'MATRIZ';
        }

        // 1. Contas a Pagar Pagas
        $queryCp = DB::table('conta_pagars as cp')
            ->join('categoria_contas as c', 'c.id', '=', 'cp.categoria_id')
            ->leftJoin('categoria_conta_grupo as ccg', 'ccg.categoria_conta_id', '=', 'c.id')
            ->leftJoin('grupo_categorias as gc', 'gc.id', '=', 'ccg.grupo_categoria_id')
            ->select(
                DB::raw("COALESCE(cp.valor_pago, cp.valor_integral) as valor"),
                DB::raw("COALESCE(cp.data_pagamento, cp.data_vencimento) as data"),
                'c.id as categoria_id',
                'c.nome as categoria_nome',
                'gc.id as grupo_id',
                'gc.nome as grupo_nome',
                'cp.filial_id'
            )
            ->where('cp.empresa_id', $empresaId)
            ->whereNull('gc.deleted_at')
            ->whereBetween(DB::raw("COALESCE(cp.data_pagamento, cp.data_vencimento)"), ["{$ano}-01-01 00:00:00", "{$ano}-12-31 23:59:59"]);

        if ($filialId === 'matriz' || $filialId === null) {
            $queryCp->whereNull('cp.filial_id');
        } elseif ($filialId !== 'todas') {
            $queryCp->where('cp.filial_id', $filialId);
        }

        $despesasCp = $queryCp->get();

        // 2. Entradas do Caixinha (item_conta_empresas)
        $queryCaixa = DB::table('item_conta_empresas as ice')
            ->join('conta_empresas as ce', 'ce.id', '=', 'ice.conta_id')
            ->join('categoria_contas as c', 'c.id', '=', 'ice.categoria_id')
            ->leftJoin('categoria_conta_grupo as ccg', 'ccg.categoria_conta_id', '=', 'c.id')
            ->leftJoin('grupo_categorias as gc', 'gc.id', '=', 'ccg.grupo_categoria_id')
            ->select(
                'ice.valor',
                DB::raw("COALESCE(ice.created_at, ice.updated_at) as data"),
                'c.id as categoria_id',
                'c.nome as categoria_nome',
                'gc.id as grupo_id',
                'gc.nome as grupo_nome',
                'ce.filial_id'
            )
            ->where('c.empresa_id', $empresaId)
            ->where('ice.tipo', 'entrada')
            ->whereNull('gc.deleted_at')
            ->whereBetween('ice.created_at', ["{$ano}-01-01 00:00:00", "{$ano}-12-31 23:59:59"]);

        if ($filialId === 'matriz' || $filialId === null) {
            $queryCaixa->where(function($q) {
                $q->whereNull('ce.filial_id')->orWhereNull('c.filial_id');
            });
        } elseif ($filialId !== 'todas') {
            $queryCaixa->where(function($q) use ($filialId) {
                $q->where('ce.filial_id', $filialId)->orWhere('c.filial_id', $filialId);
            });
        }

        $entradasCaixa = $queryCaixa->get();

        $todasMovimentacoes = $despesasCp->concat($entradasCaixa);

        // Movimentações do Mês Selecionado
        $despesasMesAtual = $todasMovimentacoes->filter(function($item) use ($dataInicioMes, $dataFimMes) {
            $dataItem = date('Y-m-d', strtotime($item->data));
            return $dataItem >= $dataInicioMes && $dataItem <= $dataFimMes;
        });

        // SEPARAÇÃO: GRUPOS NORMAIS vs FROTA
        $gruposSemFrota = $despesasMesAtual->filter(function($item) {
            return !str_contains(strtolower($item->grupo_nome ?? ''), 'frota');
        })->groupBy('grupo_nome')->map(function($g) { return $g->sum('valor'); });

        $totalSemFrota = $gruposSemFrota->sum();

        // DETALHAMENTO DA FROTA
        $despesasFrota = $despesasMesAtual->filter(function($item) {
            return str_contains(strtolower($item->grupo_nome ?? ''), 'frota');
        });

        $categoriasFrota = $despesasFrota->groupBy('categoria_nome')->map(function($cat) {
            return $cat->sum('valor');
        });

        $totalFrota = $despesasFrota->sum('valor');
        $totalGeralComFrota = $totalSemFrota + $totalFrota;

        // TOTALIZADORES POR MÊS E CAIXINHA
        $despesasPorMes = [];
        $caixinhaPorMes = [];
        
        for ($m = 1; $m <= 12; $m++) {
            $mesKey = str_pad($m, 2, '0', STR_PAD_LEFT);
            
            $totalMes = $todasMovimentacoes->filter(function($item) use ($ano, $mesKey) {
                return date('Y-m', strtotime($item->data)) === "{$ano}-{$mesKey}";
            })->sum('valor');

            $totalCaixinha = $todasMovimentacoes->filter(function($item) use ($ano, $mesKey) {
                $ehCaixinha = str_contains(strtolower($item->grupo_nome ?? ''), 'caixinha') 
                           || str_contains(strtolower($item->categoria_nome ?? ''), 'caixa')
                           || str_contains(strtolower($item->categoria_nome ?? ''), 'caixinha');
                return $ehCaixinha && date('Y-m', strtotime($item->data)) === "{$ano}-{$mesKey}";
            })->sum('valor');

            $despesasPorMes[$m] = $totalMes;
            $caixinhaPorMes[$m] = $totalCaixinha;
        }

        $totalAno = array_sum($despesasPorMes);
        $mediaMensal = $totalAno / 12;

        $title = "Resumo Financeiro de Despesas - {$ano}";

        return view('grupo_categorias.relatorio_resumo', compact(
            'gruposSemFrota',
            'totalSemFrota',
            'categoriasFrota',
            'totalFrota',
            'totalGeralComFrota',
            'despesasPorMes',
            'caixinhaPorMes',
            'totalAno',
            'mediaMensal',
            'nomeUnidade',
            'filiais',
            'filialId',
            'ano',
            'mes',
            'title'
        ));
    }
  
  public function comparativoMeses(Request $request)
{
    $empresaId = $request->empresa_id ?? auth()->user()->empresa_id;
    $filialId = $request->input('filial_id'); 
    $ano = $request->input('ano', date('Y'));
    $mes = $request->input('mes', date('m')); 

    // Define as datas do Mês Atual
    $dataInicioAtual = "{$ano}-{$mes}-01";
    $dataFimAtual = date('Y-m-t', strtotime($dataInicioAtual));

    // Calcula automaticamente o Mês Anterior (se for janeiro, pega dezembro do ano anterior)
    $dataCarbon = \Carbon\Carbon::parse($dataInicioAtual);
    $dataInicioAnterior = $dataCarbon->copy()->subMonth()->startOfMonth()->format('Y-m-d');
    $dataFimAnterior = $dataCarbon->copy()->subMonth()->endOfMonth()->format('Y-m-d');

    // Nomes dos Mêses em Português
    $mesesNomes = [
        1 => 'JANEIRO', 2 => 'FEVEREIRO', 3 => 'MARÇO', 4 => 'ABRIL',
        5 => 'MAIO', 6 => 'JUNHO', 7 => 'JULHO', 8 => 'AGOSTO',
        9 => 'SETEMBRO', 10 => 'OUTUBRO', 11 => 'NOVEMBRO', 12 => 'DEZEMBRO'
    ];

    $nomeMesAtual = $mesesNomes[(int)$mes];
    $nomeMesAnterior = $mesesNomes[(int)$dataCarbon->copy()->subMonth()->month];

    // Nome da Unidade / Empresa
    $filiais = DB::table('filials')->where('empresa_id', $empresaId)->get();
    if ($filialId && $filialId !== 'matriz' && $filialId !== 'todas') {
        $filialSel = $filiais->firstWhere('id', $filialId);
        $nomeUnidade = $filialSel->nome_fantasia ?? $filialSel->razao_social ?? $filialSel->descricao ?? 'FILIAL';
    } elseif ($filialId === 'todas') {
        $nomeUnidade = 'CONSOLIDADO (TODAS AS UNIDADES)';
    } else {
        $matrizInfo = DB::table('config_notas')->where('empresa_id', $empresaId)->first();
        $nomeUnidade = $matrizInfo->nome_fantasia ?? $matrizInfo->razao_social ?? 'MATRIZ';
    }

    // 1. BUSCA DAS CONTAS PAGAS (Contas a Pagar)
    $queryCp = DB::table('conta_pagars as cp')
        ->join('categoria_contas as c', 'c.id', '=', 'cp.categoria_id')
        ->leftJoin('categoria_conta_grupo as ccg', 'ccg.categoria_conta_id', '=', 'c.id')
        ->leftJoin('grupo_categorias as gc', 'gc.id', '=', 'ccg.grupo_categoria_id')
        ->select(
            DB::raw("COALESCE(cp.valor_pago, cp.valor_integral) as valor"),
            DB::raw("COALESCE(cp.data_pagamento, cp.data_vencimento) as data"),
            'gc.nome as grupo_nome',
            'cp.filial_id'
        )
        ->where('cp.empresa_id', $empresaId)
        ->whereNull('gc.deleted_at')
        ->whereBetween(DB::raw("COALESCE(cp.data_pagamento, cp.data_vencimento)"), ["{$dataInicioAnterior} 00:00:00", "{$dataFimAtual} 23:59:59"]);

    if ($filialId === 'matriz' || $filialId === null) {
        $queryCp->whereNull('cp.filial_id');
    } elseif ($filialId !== 'todas') {
        $queryCp->where('cp.filial_id', $filialId);
    }

    // 2. BUSCA DAS ENTRADAS DO CAIXINHA (item_conta_empresas)
    $queryCaixa = DB::table('item_conta_empresas as ice')
        ->join('conta_empresas as ce', 'ce.id', '=', 'ice.conta_id')
        ->join('categoria_contas as c', 'c.id', '=', 'ice.categoria_id')
        ->leftJoin('categoria_conta_grupo as ccg', 'ccg.categoria_conta_id', '=', 'c.id')
        ->leftJoin('grupo_categorias as gc', 'gc.id', '=', 'ccg.grupo_categoria_id')
        ->select(
            'ice.valor',
            DB::raw("COALESCE(ice.created_at, ice.updated_at) as data"),
            'gc.nome as grupo_nome',
            'ce.filial_id'
        )
        ->where('c.empresa_id', $empresaId)
        ->where('ice.tipo', 'entrada')
        ->whereNull('gc.deleted_at')
        ->whereBetween('ice.created_at', ["{$dataInicioAnterior} 00:00:00", "{$dataFimAtual} 23:59:59"]);

    if ($filialId === 'matriz' || $filialId === null) {
        $queryCaixa->where(function($q) {
            $q->whereNull('ce.filial_id')->orWhereNull('c.filial_id');
        });
    } elseif ($filialId !== 'todas') {
        $queryCaixa->where(function($q) use ($filialId) {
            $q->where('ce.filial_id', $filialId)->orWhere('c.filial_id', $filialId);
        });
    }

    $todasMovimentacoes = $queryCp->get()->concat($queryCaixa->get());

    // Agrupa todos os grupos encontrados nas duas janelas de datas
    $gruposNomes = $todasMovimentacoes->pluck('grupo_nome')->unique()->filter()->values();

    $comparativo = [];
    foreach ($gruposNomes as $grupo) {
        // Soma do Mês Atual
        $valAtual = $todasMovimentacoes->filter(function($item) use ($grupo, $dataInicioAtual, $dataFimAtual) {
            $dt = date('Y-m-d', strtotime($item->data));
            return $item->grupo_nome === $grupo && ($dt >= $dataInicioAtual && $dt <= $dataFimAtual);
        })->sum('valor');

        // Soma do Mês Anterior
        $valAnterior = $todasMovimentacoes->filter(function($item) use ($grupo, $dataInicioAnterior, $dataFimAnterior) {
            $dt = date('Y-m-d', strtotime($item->data));
            return $item->grupo_nome === $grupo && ($dt >= $dataInicioAnterior && $dt <= $dataFimAnterior);
        })->sum('valor');

        $comparativo[] = [
            'grupo' => $grupo,
            'valor_atual' => $valAtual,
            'valor_anterior' => $valAnterior,
        ];
    }

    $title = "Comparativo de Despesas - {$nomeMesAtual} vs {$nomeMesAnterior}";

    return view('grupo_categorias.relatorio_comparativo', compact(
        'comparativo',
        'nomeMesAtual',
        'nomeMesAnterior',
        'nomeUnidade',
        'filiais',
        'filialId',
        'ano',
        'mes',
        'mesesNomes',
        'title'
    ));
}
}