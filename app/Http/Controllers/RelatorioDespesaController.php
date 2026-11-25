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

        // 1. Regra padrão de Sexta a Sexta quando não há filtro
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

        // 2. Subquery: pega apenas a MENOR data de vencimento em aberto (status = 0) de cada documento
        $primeirasParcelas = DB::table('conta_pagars')
            ->select(
                'fornecedor_id',
                'filial_id',
                DB::raw("COALESCE(numero_nota_fiscal, '') as num_nota_clean"),
                DB::raw('MIN(data_vencimento) as primeiro_vencimento')
            )
            ->where('empresa_id', $empresaId)
            ->where('status', 0)
            ->groupBy('fornecedor_id', 'filial_id', DB::raw("COALESCE(numero_nota_fiscal, '')"))
            // SÓ entra se o PRIMEIRO vencimento da nota cair exatamente dentro desta semana
            ->havingBetween('primeiro_vencimento', [$dataInicio, $dataFim]);

        /* // 3. Busca todos os lançamentos (todos os rateios e parcelas) das notas selecionadas
         $dados = DB::table('conta_pagars as cp')
             ->joinSub($primeirasParcelas, 'pp', function ($join) {
                 $join->on('cp.fornecedor_id', '=', 'pp.fornecedor_id')
                      ->on('cp.filial_id', '=', 'pp.filial_id')
                      ->on(DB::raw("COALESCE(cp.numero_nota_fiscal, '')"), '=', 'pp.num_nota_clean');
             })
             ->join('categoria_contas as c', 'c.id', '=', 'cp.categoria_id')
             ->join('categoria_conta_grupo as ccg', 'ccg.categoria_conta_id', '=', 'c.id')
             ->join('grupo_categorias as gc', 'gc.id', '=', 'ccg.grupo_categoria_id')
             ->leftJoin('fornecedors as f', 'f.id', '=', 'cp.fornecedor_id')
             ->leftJoin('filials as fil', 'fil.id', '=', 'cp.filial_id')
             ->select(
                 'cp.id',
                 DB::raw("COALESCE(NULLIF(f.razao_social, ''), f.nome_fantasia, 'NÃO INFORMADO') as empresa_beneficiaria"),
                 'cp.numero_nota_fiscal as numero_nota',
                 'cp.valor_integral as valor_rateio',
                 'cp.data_vencimento',
                 'cp.status',
                 'gc.nome as classificacao',
                 'c.nome as descriminacao',
                 DB::raw("IF(cp.filial_id IS NULL OR cp.filial_id = 0, '{$nomeMatriz}', COALESCE(fil.nome_fantasia, fil.descricao)) as pagador")
             )
             ->where('cp.empresa_id', $empresaId)
             ->whereNull('gc.deleted_at')
             ->orderBy('cp.data_vencimento', 'asc')
             ->get();*/

        // 3. Busca todos os lançamentos usando LEFT JOIN para não perder categorias sem grupo
        $dados = DB::table('conta_pagars as cp')
            ->joinSub($primeirasParcelas, 'pp', function ($join) {
                $join->on('cp.fornecedor_id', '=', 'pp.fornecedor_id')
                    ->on('cp.filial_id', '=', 'pp.filial_id')
                    ->on(DB::raw("COALESCE(cp.numero_nota_fiscal, '')"), '=', 'pp.num_nota_clean');
            })
            ->join('categoria_contas as c', 'c.id', '=', 'cp.categoria_id')
            ->leftJoin('categoria_conta_grupo as ccg', 'ccg.categoria_conta_id', '=', 'c.id')
            ->leftJoin('grupo_categorias as gc', function($join) {
                $join->on('gc.id', '=', 'ccg.grupo_categoria_id')
                    ->whereNull('gc.deleted_at');
            })
            ->leftJoin('fornecedors as f', 'f.id', '=', 'cp.fornecedor_id')
            ->leftJoin('filials as fil', 'fil.id', '=', 'cp.filial_id')
            ->select(
                'cp.id',
                DB::raw("COALESCE(NULLIF(f.razao_social, ''), f.nome_fantasia, 'NÃO INFORMADO') as empresa_beneficiaria"),
                'cp.numero_nota_fiscal as numero_nota',
                'cp.valor_integral as valor_rateio',
                'cp.data_vencimento',
                'cp.status',
                // Se o grupo for nulo, define como OUTROS / SEM GRUPO
                DB::raw("COALESCE(gc.nome, 'OUTROS / SEM GRUPO') as classificacao"),
                'c.nome as descriminacao',
                // Puxa priorizando a descrição da filial conforme a tabela
                DB::raw("IF(cp.filial_id IS NULL OR cp.filial_id = 0, '{$nomeMatriz}', COALESCE(fil.descricao, fil.nome_fantasia)) as pagador")
            )
            ->where('cp.empresa_id', $empresaId)
            ->orderBy('cp.data_vencimento', 'asc')
            ->get();

        // 4. Estrutura os dados: agrupa por Documento e consolida os rateios por vencimento
        $documentos = $dados->groupBy(function($item) {
            return $item->empresa_beneficiaria . '-' . ($item->numero_nota ?? 'SN') . '-' . $item->pagador;
        })->map(function($registros) {
            $primeiro = $registros->first();

            // Soma TOTAL da nota (soma de todas as parcelas e rateios)
            $valorTotalNota = $registros->sum('valor_rateio');

            // Agrupa os rateios por DATA DE VENCIMENTO para formar o valor real de cada BOLETO
            $boletosConsolidados = $registros->groupBy('data_vencimento')->map(function($itensVencimento, $dataVenc) {
                return (object)[
                    'data_vencimento' => $dataVenc,
                    'valor_boleto'    => $itensVencimento->sum('valor_rateio'),
                    'qtd_rateios'     => $itensVencimento->count()
                ];
            })->values();

            return (object)[
                'empresa_beneficiaria' => $primeiro->empresa_beneficiaria,
                'numero_nota'          => $primeiro->numero_nota,
                'pagador'              => $primeiro->pagador,
                'classificacao'        => $primeiro->classificacao,
                'descriminacao'        => $primeiro->descriminacao,
                'valor_total_nota'     => $valorTotalNota,
                'boletos'              => $boletosConsolidados,
            ];
        });

        // 5. Total de pagamentos que vencem nesta semana (apenas boletos da semana)
        $totalSemana = 0;
        foreach ($documentos as $doc) {
            foreach ($doc->boletos as $bol) {
                if ($bol->data_vencimento >= $dataInicio && $bol->data_vencimento <= $dataFim) {
                    $totalSemana += $bol->valor_boleto;
                }
            }
        }

        // 6. Total global das notas aprovadas nesta semana
        $totalGeralNotas = $documentos->sum('valor_total_nota');

        $title = 'Relatório de Despesas - Visão Semanal';

        return view('grupo_categorias.relatorio_planilha', compact(
            'documentos',
            'dataInicio',
            'dataFim',
            'mesReferencia',
            'totalSemana',
            'totalGeralNotas',
            'title'
        ));
    }
}
