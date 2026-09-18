@php
    $title = $title ?? 'Relatório de Despesas';
    
    // Configura o idioma do Carbon para Português (Brasil)
    \Carbon\Carbon::setLocale('pt_BR');
    
    $dataInicioObj = \Carbon\Carbon::parse($dataInicio);
    $dataFimObj = \Carbon\Carbon::parse($dataFim);
    
    // Nome do mês traduzido em Português
    $nomeMesPt = ucfirst($dataInicioObj->translatedFormat('F Y'));
@endphp

@extends('default.layout')

@section('content')
<div class="container-fluid py-4">

    {{-- Filtro superior --}}
    <div class="card shadow-sm border-0 mb-3 no-print">
        <div class="card-body py-2">
            <form action="{{ url('/relatorios-financeiros/despesas-semanal') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <label class="form-label mb-0 fw-bold">Data Início:</label>
                    <input type="date" name="data_inicio" class="form-control form-control-sm" value="{{ $dataInicio }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-0 fw-bold">Data Fim:</label>
                    <input type="date" name="data_fim" class="form-control form-control-sm" value="{{ $dataFim }}">
                </div>
                <div class="col-md-4 mt-3">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-search me-1"></i> Filtrar Período
                    </button>
                    <button type="button" onclick="window.print()" class="btn btn-sm btn-secondary ms-1">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabela Estilo Planilha --}}
    <div class="card shadow-sm border-dark">
        <div class="card-header bg-white text-center border-bottom-0 pt-3 pb-1">
            <h3 class="fw-bold mb-0 text-uppercase">Despesas {{ $nomeMesPt }}</h3>
            <h6 class="fw-bold text-muted">
                SEMANA ({{ $dataInicioObj->format('d/m/Y') }} A {{ $dataFimObj->format('d/m/Y') }})
            </h6>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0 text-nowrap" style="border: 2px solid #000;">
                    <thead style="background-color: #f2f2f2; border-bottom: 2px solid #000;">
                        <tr class="text-uppercase fw-bold text-dark text-center" style="font-size: 0.85rem;">
                            <th style="border: 1px solid #000;" class="text-start">Empresa beneficiária</th>
                            <th style="border: 1px solid #000;" class="text-start">Nº Nota fiscal</th>
                            <th style="border: 1px solid #000;" class="text-start">Valores da nota</th>
                            <th style="border: 1px solid #000;" class="text-start">Classificação</th>
                            <th style="border: 1px solid #000;" class="text-start">Descriminação</th>
                            <th style="border: 1px solid #000;" class="text-center">vencimentos</th>
                            <th style="border: 1px solid #000;" class="text-start">Valores dos boletos</th>
                            <th style="border: 1px solid #000;" class="text-start">Pagador</th>
                        </tr>
                    </thead>
                    <tbody style="font-size: 0.85rem;">
                        @forelse($linhas as $grupoNota)
                            @php
                                $primeiro = $grupoNota->first();
                                
                                // Formata os vencimentos separando por quebra de linha (<br>)
                                $vencimentosHtml = $grupoNota->pluck('data_vencimento')->map(function($d) {
                                    return \Carbon\Carbon::parse($d)->format('d/m/Y');
                                })->implode('<br>');

                                // Formata os valores dos boletos alinhados linha a linha
                                $valoresBoletosHtml = $grupoNota->map(function($item) {
                                    return 'R$ ' . number_format($item->valor_boleto, 2, ',', '.');
                                })->implode('<br>');

                                $totalBoletos = $grupoNota->sum('valor_boleto');
                            @endphp
                            <tr>
                                {{-- Empresa Beneficiária --}}
                                <td class="fw-bold text-start" style="border: 1px solid #d0d0d0;">{{ strtoupper($primeiro->empresa_beneficiaria) }}</td>
                                
                                {{-- Nº Nota Fiscal (Alinhado à Esquerda) --}}
                                <td class="text-start" style="border: 1px solid #d0d0d0;">{{ $primeiro->numero_nota ?? '-' }}</td>
                                
                                {{-- Valores da Nota (Alinhado à Esquerda - Padrão PT-BR) --}}
                                <td class="text-start fw-bold" style="border: 1px solid #d0d0d0;">R$ {{ number_format($totalBoletos, 2, ',', '.') }}</td>
                                
                                {{-- Classificação (GRUPO) --}}
                                <td class="fw-bold text-uppercase text-start" style="border: 1px solid #d0d0d0;">{{ $primeiro->classificacao }}</td>
                                
                                {{-- Descriminação (CATEGORIA) --}}
                                <td class="text-uppercase text-start" style="border: 1px solid #d0d0d0;">{{ $primeiro->descriminacao }}</td>
                                
                                {{-- Vencimentos (Centralizado) --}}
                                <td class="text-center fw-bold text-danger" style="border: 1px solid #d0d0d0; background-color: #fdfdfe; line-height: 1.4;">
                                    {!! $vencimentosHtml !!}
                                </td>
                                
                                {{-- Valores dos Boletos (Alinhado à Esquerda - Padrão PT-BR) --}}
                                <td class="text-start fw-bold" style="border: 1px solid #d0d0d0; line-height: 1.4;">
                                    {!! $valoresBoletosHtml !!}
                                </td>
                                
                                {{-- Pagador (Matriz / Filial) --}}
                                <td class="text-uppercase fw-bold text-start" style="border: 1px solid #d0d0d0;">{{ $primeiro->pagador }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    Nenhuma despesa encontrada para o período de 
                                    <strong>{{ $dataInicioObj->format('d/m/Y') }}</strong> a 
                                    <strong>{{ $dataFimObj->format('d/m/Y') }}</strong>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print, .main-sidebar, .main-header { display: none !important; }
        .content-wrapper { margin-left: 0 !important; }
        table { width: 100% !important; font-size: 10pt !important; }
    }
</style>
@endsection