<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RelatorioDespesaController extends Controller
{
    public function visualizacaoPlanilha(Request $request)
    {
        $empresaId = $request->empresa_id ?? auth()->user()->empresa_id;

        // 1. REGRA DA SEMANA (Sexta a Sexta) COMO PADRÃO QUANDO NÃO HÁ FILTRO
        if ($request->has('data_inicio') && $request->filled('data_inicio')) {
            $dataInicio = $request->input('data_inicio');
        } else {
            $dataInicio = Carbon::now()->isFriday()
                ? Carbon::now()->format('Y-m-d')
                : Carbon::now()->previous(Carbon::FRIDAY)->format('Y-m-d');
        }

        if ($request->has('data_fim') && $request->filled('data_fim')) {
            $dataFim = $request->input('data_fim');
        } else {
            $dataFim = Carbon::parse($dataInicio)->addDays(7)->format('Y-m-d');
        }

        $mesReferencia = $request->input('mes_referencia', Carbon::parse($dataInicio)->format('Y-m'));

        // Configuração da Matriz
        $configMatriz = DB::table('config_notas')
            ->where('empresa_id', $empresaId)
            ->first();

        $nomeMatriz = $configMatriz->nome_fantasia ?? $configMatriz->razao_social ?? 'MATRIZ';

        // Busca das Contas a Pagar
        $dados = DB::table('conta_pagars as cp')
            ->join('categoria_contas as c', 'c.id', '=', 'cp.categoria_id')
            ->join('categoria_conta_grupo as ccg', 'ccg.categoria_conta_id', '=', 'c.id')
            ->join('grupo_categorias as gc', 'gc.id', '=', 'ccg.grupo_categoria_id')
            ->leftJoin('fornecedors as f', 'f.id', '=', 'cp.fornecedor_id')
            ->leftJoin('filials as fil', 'fil.id', '=', 'cp.filial_id')
            ->select(
                DB::raw("COALESCE(NULLIF(f.razao_social, ''), f.nome_fantasia, 'NÃO INFORMADO') as empresa_beneficiaria"),
                'cp.numero_nota_fiscal as numero_nota',
                'cp.valor_integral as valor_boleto',
                'cp.data_vencimento',
                'gc.nome as classificacao',
                'c.nome as descriminacao',
                DB::raw("IF(cp.filial_id IS NULL OR cp.filial_id = 0, '{$nomeMatriz}', COALESCE(fil.nome_fantasia, fil.descricao)) as pagador")
            )
            ->where('cp.empresa_id', $empresaId)
            ->whereNull('gc.deleted_at')
            ->whereBetween('cp.data_vencimento', [$dataInicio, $dataFim])
            ->orderBy('cp.data_vencimento', 'asc')
            ->get();

        // Agrupa separando por Beneficiário + Número da Nota + Unidade Pagadora (Matriz ou Filial)
        $linhas = $dados->groupBy(function($item) {
            return $item->empresa_beneficiaria . '-' . ($item->numero_nota ?? 'SN') . '-' . $item->pagador;
        });

        $title = 'Despesas - Visão Semanal';

        return view('grupo_categorias.relatorio_planilha', compact(
            'linhas',
            'dataInicio',
            'dataFim',
            'mesReferencia',
            'title'
        ));
    }
}
