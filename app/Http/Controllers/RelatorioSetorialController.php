<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RelatorioSetorialController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = $request->empresa_id ?? auth()->user()->empresa_id;

        // Padrão: Puxa sempre o mês anterior se nenhum filtro for informado
        $mesAnteriorPadrao = Carbon::now()->subMonth()->format('Y-m');
        $mesAno = $request->input('mes', $mesAnteriorPadrao);

        list($ano, $mes) = explode('-', $mesAno);
        $filialId = $request->input('filial_id', 'todas');

        // Busca filiais da empresa ordenadas pela descrição
        $filiais = DB::table('filials')
            ->where('empresa_id', $empresaId)
            ->orderBy('descricao')
            ->get();

        // Nome da Unidade para o cabeçalho
        $nomeUnidade = 'TODAS AS UNIDADES (CONSOLIDADO)';
        if ($filialId === 'matriz') {
            $configMatriz = DB::table('config_notas')->where('empresa_id', $empresaId)->first();
            $nomeUnidade = $configMatriz->nome_fantasia ?? $configMatriz->razao_social ?? 'MATRIZ';
        } elseif ($filialId !== 'todas') {
            $filialSel = $filiais->firstWhere('id', $filialId);
            $nomeUnidade = $filialSel->descricao ?? $filialSel->nome_fantasia ?? $filialSel->razao_social ?? 'FILIAL';
        }

        // Busca os grupos cadastrados
        $gruposDb = DB::table('grupo_categorias')
            ->where('empresa_id', $empresaId)
            ->whereNull('deleted_at')
            ->orderBy('nome')
            ->get();

        // Consulta as despesas do mês selecionado
        $query = DB::table('conta_pagars as cp')
            ->join('categoria_contas as c', 'c.id', '=', 'cp.categoria_id')
            ->leftJoin('categoria_conta_grupo as ccg', 'ccg.categoria_conta_id', '=', 'c.id')
            ->leftJoin('grupo_categorias as gc', function($join) {
                $join->on('gc.id', '=', 'ccg.grupo_categoria_id')
                    ->whereNull('gc.deleted_at');
            })
            ->select(
                DB::raw("COALESCE(cp.valor_pago, cp.valor_integral) as valor"),
                'c.id as categoria_id',
                'c.nome as categoria_nome',
                DB::raw("COALESCE(gc.nome, 'OUTROS / SEM GRUPO') as grupo_nome"),
                'cp.filial_id'
            )
            ->where('cp.empresa_id', $empresaId)
            ->whereYear('cp.data_vencimento', $ano)
            ->whereMonth('cp.data_vencimento', $mes);

        // Filtro por Filial / Matriz
        if ($filialId === 'matriz') {
            $query->where(function($q) {
                $q->whereNull('cp.filial_id')->orWhere('cp.filial_id', 0);
            });
        } elseif ($filialId !== 'todas') {
            $query->where('cp.filial_id', $filialId);
        }

        $despesas = $query->get();

        // Estrutura os dados: [Grupo] => [ 'categorias' => [Nome => Valor], 'total_grupo' => X ]
        $estruturado = [];
        $totalGeral = 0;

        foreach ($gruposDb as $g) {
            $estruturado[strtoupper($g->nome)] = [
                'categorias' => [],
                'total_grupo' => 0
            ];
        }
        $estruturado['OUTROS / SEM GRUPO'] = [
            'categorias' => [],
            'total_grupo' => 0
        ];

        foreach ($despesas as $d) {
            $gNome = strtoupper($d->grupo_nome);
            $cNome = strtoupper($d->categoria_nome);
            $val = (float)$d->valor;

            if (!isset($estruturado[$gNome])) {
                $estruturado[$gNome] = [
                    'categorias' => [],
                    'total_grupo' => 0
                ];
            }

            if (!isset($estruturado[$gNome]['categorias'][$cNome])) {
                $estruturado[$gNome]['categorias'][$cNome] = 0;
            }

            $estruturado[$gNome]['categorias'][$cNome] += $val;
            $estruturado[$gNome]['total_grupo'] += $val;
            $totalGeral += $val;
        }

        // Filtra para remover grupos que não possuem movimentação no período
        $estruturado = array_filter($estruturado, function($grupoData) {
            return count($grupoData['categorias']) > 0;
        });

        $title = 'Resumo Setorial de Despesas';

        return view('grupo_categorias.relatorio_setorial', compact(
            'estruturado',
            'totalGeral',
            'mesAno',
            'filialId',
            'filiais',
            'nomeUnidade',
            'title'
        ));
    }

    public function exportarExcel(Request $request)
    {
        $empresaId = $request->empresa_id ?? auth()->user()->empresa_id;
        $mesAno = $request->input('mes', Carbon::now()->subMonth()->format('Y-m'));
        list($ano, $mes) = explode('-', $mesAno);
        $filialId = $request->input('filial_id', 'todas');

        $query = DB::table('conta_pagars as cp')
            ->join('categoria_contas as c', 'c.id', '=', 'cp.categoria_id')
            ->leftJoin('categoria_conta_grupo as ccg', 'ccg.categoria_conta_id', '=', 'c.id')
            ->leftJoin('grupo_categorias as gc', function($join) {
                $join->on('gc.id', '=', 'ccg.grupo_categoria_id')
                    ->whereNull('gc.deleted_at');
            })
            ->select(
                DB::raw("COALESCE(cp.valor_pago, cp.valor_integral) as valor"),
                'c.nome as categoria_nome',
                DB::raw("COALESCE(gc.nome, 'OUTROS / SEM GRUPO') as grupo_nome")
            )
            ->where('cp.empresa_id', $empresaId)
            ->whereYear('cp.data_vencimento', $ano)
            ->whereMonth('cp.data_vencimento', $mes);

        if ($filialId === 'matriz') {
            $query->where(function($q) { $q->whereNull('cp.filial_id')->orWhere('cp.filial_id', 0); });
        } elseif ($filialId !== 'todas') {
            $query->where('cp.filial_id', $filialId);
        }

        $despesas = $query->get();
        $fileName = "resumo_setorial_{$mesAno}.csv";

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use($despesas) {
            $file = fopen('php://output', 'w');
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($file, ['Grupo', 'Categoria', 'Valor (R$)'], ';');

            foreach ($despesas as $d) {
                fputcsv($file, [
                    strtoupper($d->grupo_nome),
                    strtoupper($d->categoria_nome),
                    number_format($d->valor, 2, ',', '')
                ], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
