<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Filial;
use Barryvdh\DomPDF\Facade\Pdf;

class FaturamentoController extends Controller
{
    public function index(Request $request)
    {
        // Captura a empresa logada
        $empresa_id = $this->empresa_id ?? (session('user_logged')['empresa_id'] ?? 2);
        
        $tipo_periodo = $request->get('tipo_periodo', 'anual'); 
        $ano = $request->get('ano', date('Y'));
        $filtro_filial = $request->get('filial_id'); 

        // Define as datas de início e fim para a declaração e o gráfico
        if ($tipo_periodo == '12_meses') {
            $data_fim = Carbon::now()->subMonth()->endOfMonth();
            $data_inicio = Carbon::now()->subMonths(12)->startOfMonth();
            $title = "Evolução 12 Meses (" . $data_inicio->format('m/Y') . " a " . $data_fim->format('m/Y') . ")";
        } else {
            $data_inicio = Carbon::parse("$ano-01-01 00:00:00");
            $data_fim = Carbon::parse("$ano-12-31 23:59:59");
            $title = "Posição de Faturamento - " . $ano;
        }

        // 1. BUSCA VENDAS AGRUPADAS (Performance otimizada para não travar a CPU)
        $vendasMensais = DB::table('vendas')
            ->select(DB::raw('MONTH(data_emissao) as mes, YEAR(data_emissao) as ano, SUM(valor_total) as total'))
            ->where('empresa_id', $empresa_id)
            ->where('estado', 'APROVADO')
            ->where('tipo_pagamento', '!=', '90')
            ->whereBetween('data_emissao', [$data_inicio, $data_fim])
            ->when($filtro_filial, function($q) use ($filtro_filial) {
                return $filtro_filial === 'matriz' ? $q->whereNull('filial_id') : $q->where('filial_id', $filtro_filial);
            })
            ->groupBy('ano', 'mes')
            ->orderBy('ano', 'asc')
            ->orderBy('mes', 'asc')
            ->get();

        $faturamentoBruto = $vendasMensais->sum('total');

        // 2. BUSCA DEVOLUÇÕES (Pela Natureza de Operação)
        $devolucoes = DB::table('compras')
            ->join('natureza_operacaos', 'natureza_operacaos.id', '=', 'compras.natureza_id')
            ->where('compras.empresa_id', $empresa_id)
            ->where('compras.estado', 'APROVADO')
            ->whereBetween('compras.data_emissao', [$data_inicio, $data_fim])
            ->where(function($q) {
                $q->where('natureza_operacaos.natureza', 'like', '%Devolução%')
                  ->orWhere('natureza_operacaos.natureza', 'like', '%Retorno%')
                  ->orWhere('compras.observacao', 'like', '%Devolução%');
            })
            ->when($filtro_filial, function($q) use ($filtro_filial) {
                return $filtro_filial === 'matriz' ? $q->whereNull('compras.filial_id') : $q->where('compras.filial_id', $filtro_filial);
            })
            ->sum('compras.valor');

        $faturamentoLiquido = max(0, $faturamentoBruto - $devolucoes);

        // 3. PREPARAÇÃO DOS DADOS DO GRÁFICO E TABELA
        $labels = []; $valores = [];
        foreach($vendasMensais as $v) {
            $labels[] = str_pad($v->mes, 2, '0', STR_PAD_LEFT) . '/' . $v->ano;
            $valores[] = (float)$v->total;
        }

        $filiais = Filial::where('empresa_id', $empresa_id)->get();

        // Envia TODAS as variáveis para a View, corrigindo o erro de "Undefined variable"
        return view('faturamento.index', compact(
            'title', 'faturamentoBruto', 'devolucoes', 'faturamentoLiquido',
            'filiais', 'ano', 'filtro_filial', 'tipo_periodo', 
            'labels', 'valores', 'data_inicio', 'data_fim'
        ));
    }
  public function gerarPdfFaturamento(Request $request)
{
    $empresa_id = $this->empresa_id ?? (session('user_logged')['empresa_id'] ?? 2);
    
    $tipo_periodo = $request->get('tipo_periodo', 'anual'); 
    $ano = $request->get('ano', date('Y'));
    $filtro_filial = $request->get('filial_id'); 

    // 1. REGRAS DE DATAS (Não conta mês atual se for 12_meses)
    if ($tipo_periodo == '12_meses') {
        // Pega o último dia do mês passado
        $data_fim = \Carbon\Carbon::now()->subMonth()->endOfMonth();
        // Volta 12 meses para trás (a partir do mês passado)
        $data_inicio = clone $data_fim;
        $data_inicio = $data_inicio->subMonths(11)->startOfMonth(); 
    } else {
        $data_inicio = \Carbon\Carbon::parse("$ano-01-01 00:00:00");
        $data_fim = \Carbon\Carbon::parse("$ano-12-31 23:59:59");
    }

    $empresa = \App\Models\Empresa::with('contabilidade')->find($empresa_id);

    // MÁGICA DA MATRIZ: Busca a Razão Social Oficial
    $configNota = DB::table('config_notas')->where('empresa_id', $empresa_id)->first();
    if ($configNota && isset($configNota->razao_social)) {
        $empresa->razao_social = $configNota->razao_social;
    }

    // MÁGICA DA FILIAL
    if ($filtro_filial && $filtro_filial !== 'matriz') {
        $filial = \App\Models\Filial::find($filtro_filial);
        if ($filial) {
            $empresa->razao_social = $filial->razao_social ?? $filial->nome; 
            if(isset($filial->cnpj)) $empresa->cnpj = $filial->cnpj;
        }
    }

    // 2. BUSCA AS VENDAS
    $vendasMensais = DB::table('vendas')
        ->select(DB::raw('MONTH(data_emissao) as mes, YEAR(data_emissao) as ano, SUM(valor_total) as total'))
        ->where('empresa_id', $empresa_id)
        ->where('estado', 'APROVADO')
        ->where('tipo_pagamento', '!=', '90')
        ->whereBetween('data_emissao', [$data_inicio, $data_fim])
        ->when($filtro_filial, function($q) use ($filtro_filial) {
            return $filtro_filial === 'matriz' ? $q->whereNull('filial_id') : $q->where('filial_id', $filtro_filial);
        })
        ->groupBy('ano', 'mes')
        ->get();

    // 3. BUSCA AS DEVOLUÇÕES
    $devolucoesMensais = DB::table('compras')
        ->join('natureza_operacaos', 'natureza_operacaos.id', '=', 'compras.natureza_id')
        ->select(DB::raw('MONTH(compras.data_emissao) as mes, YEAR(compras.data_emissao) as ano, SUM(compras.valor) as total'))
        ->where('compras.empresa_id', $empresa_id)
        ->where('compras.estado', 'APROVADO')
        ->whereBetween('compras.data_emissao', [$data_inicio, $data_fim])
        ->where(function($q) {
            $q->where('natureza_operacaos.natureza', 'like', '%Devolução%')
              ->orWhere('natureza_operacaos.natureza', 'like', '%Retorno%')
              ->orWhere('compras.observacao', 'like', '%Devolução%');
        })
        ->when($filtro_filial, function($q) use ($filtro_filial) {
            return $filtro_filial === 'matriz' ? $q->whereNull('compras.filial_id') : $q->where('compras.filial_id', $filtro_filial);
        })
        ->groupBy('ano', 'mes')
        ->get()->keyBy(function($item) {
            return $item->ano . '-' . $item->mes;
        });

    $mesesNomes = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];

    // 4. FORÇA A CRIAÇÃO DA TABELA (Mostra todos os meses da busca, mesmo se for R$ 0)
    $faturamentos_tabela = [];
    $mes_atual_loop = clone $data_inicio;
    
    while ($mes_atual_loop <= $data_fim) {
        $chave = $mes_atual_loop->year . '-' . $mes_atual_loop->month;
        $faturamentos_tabela[$chave] = (object) [
            'mes_nome' => $mesesNomes[$mes_atual_loop->month],
            'ano' => $mes_atual_loop->year,
            'valor' => 0
        ];
        $mes_atual_loop->addMonth();
    }

    // Preenche com os dados que achou no banco
    $total_faturamento = 0;
    foreach ($vendasMensais as $v) {
        $chave = $v->ano . '-' . $v->mes;
        $devolucao = isset($devolucoesMensais[$chave]) ? $devolucoesMensais[$chave]->total : 0;
        $liquido = max(0, $v->total - $devolucao);

        if (isset($faturamentos_tabela[$chave])) {
            $faturamentos_tabela[$chave]->valor = $liquido;
            $total_faturamento += $liquido;
        }
    }

    // Converte de volta para uma lista normal
    $faturamentos = array_values($faturamentos_tabela);

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('faturamento.faturamento_pdf', compact('empresa', 'faturamentos', 'total_faturamento'));
    return $pdf->stream("Demonstrativo_Faturamento.pdf");
}
  
}