@php
    $title = $title ?? 'Resumo Setorial de Despesas';

    $mesesNomes = [
        '01' => 'JANEIRO', '02' => 'FEVEREIRO', '03' => 'MARÇO', '04' => 'ABRIL',
        '05' => 'MAIO', '06' => 'JUNHO', '07' => 'JULHO', '08' => 'AGOSTO',
        '09' => 'SETEMBRO', '10' => 'OUTUBRO', '11' => 'NOVEMBRO', '12' => 'DEZEMBRO'
    ];
    list($anoRef, $mesRef) = explode('-', $mesAno);
    $nomeMesAno = ($mesesNomes[$mesRef] ?? '') . ' DE ' . $anoRef;
@endphp

@extends('default.layout')

@section('content')
    <div class="container-fluid py-4">

        {{-- Filtros Superiores (Ocultos na Impressão) --}}
        <div class="card shadow-sm border-0 mb-4 rounded-4 no-print bg-white">
            <div class="card-body p-4">
                <form action="{{ route('relatorios.setorial') }}" method="GET" class="row g-3 align-items-end">

                    <div class="col-md-4">
                        <label class="form-label text-secondary fw-bold small mb-1"><i class="fas fa-building me-1 text-primary"></i> Unidade / Empresa:</label>
                        <select name="filial_id" class="form-select shadow-none border-light bg-light rounded-3 py-2">
                            <option value="matriz" {{ ($filialId == 'matriz') ? 'selected' : '' }}>MATRIZ (Principal)</option>
                            <option value="todas" {{ ($filialId == 'todas' || empty($filialId)) ? 'selected' : '' }}>-- TODAS AS UNIDADES (Consolidado) --</option>
                            @foreach($filiais as $f)
                                <option value="{{ $f->id }}" {{ $filialId == $f->id ? 'selected' : '' }}>
                                    FILIAL: {{ $f->descricao ?? $f->nome_fantasia ?? $f->razao_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label text-secondary fw-bold small mb-1"><i class="fas fa-calendar-alt me-1 text-primary"></i> Mês de Referência:</label>
                        <input type="month" name="mes" class="form-control shadow-none border-light bg-light rounded-3 py-2" value="{{ $mesAno }}">
                    </div>

                    <div class="col-md-5 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm rounded-3 py-2">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                        <a href="{{ route('relatorios.setorial.excel', request()->all()) }}" class="btn btn-success w-100 fw-bold shadow-sm rounded-3 py-2 text-white">
                            <i class="fas fa-file-excel me-1"></i> Excel
                        </a>
                        <button type="button" onclick="window.print()" class="btn btn-outline-secondary w-100 fw-bold shadow-sm rounded-3 py-2">
                            <i class="fas fa-print me-1"></i> Imprimir / PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Relatório Setorial com Novo Tema Moderno --}}
        <div class="row justify-content-center">
            <div class="col-xl-9 col-lg-10">
                <div class="card shadow-lg border-0 rounded-4 bg-white print-card overflow-hidden">

                    <div class="card-header bg-dark text-white text-center py-4 px-4">
                        <h4 class="fw-bold text-uppercase mb-1" style="letter-spacing: 0.08em; color: #fff;">RESUMO DAS DESPESAS DO MÊS</h4>
                        <p class="text-white-50 fw-semibold mb-2 fs-6">PERÍODO: <span class="text-white">{{ $nomeMesAno }}</span></p>
                        <span class="badge bg-primary text-white fw-bold px-3 py-2 rounded-pill fs-6 shadow-sm">
                        <i class="fas fa-map-marker-alt me-1"></i> {{ $nomeUnidade }}
                    </span>
                    </div>

                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-custom align-middle mb-0">
                                <tbody>
                                @forelse($estruturado as $grupoNome => $dadosGrupo)
                                    {{-- Linha de Destaque do Grupo (Tema Moderno Escuro/Azulado com Fonte Grande e Negrito) --}}
                                    <tr class="tr-grupo">
                                        <td class="td-grupo-nome text-uppercase">
                                            <i class="fas fa-folder-open me-2 text-warning"></i> {{ $grupoNome }}
                                        </td>
                                        <td class="td-grupo-valor text-end">
                                            R$ {{ number_format($dadosGrupo['total_grupo'], 2, ',', '.') }}
                                        </td>
                                    </tr>

                                    {{-- Itens / Categorias do Grupo --}}
                                    @foreach($dadosGrupo['categorias'] as $catNome => $valCat)
                                        <tr class="tr-categoria">
                                            <td class="td-cat-nome">
                                                <i class="fas fa-angle-right text-muted me-2 small"></i> {{ $catNome }}
                                            </td>
                                            <td class="td-cat-valor text-end">
                                                R$ {{ number_format($valCat, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Espaçador entre grupos --}}
                                    <tr class="tr-espaco"><td colspan="2"></td></tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center py-5 text-muted">
                                            <i class="fas fa-folder-open fa-2x mb-2 text-black-50"></i>
                                            <p class="mb-0">Nenhuma despesa encontrada para o mês e unidade selecionados.</p>
                                        </td>
                                    </tr>
                                @endforelse

                                {{-- Rodapé de Total Geral Destacado --}}
                                <tr class="tr-total-geral">
                                    <td class="text-uppercase">
                                        <i class="fas fa-calculator me-2 text-warning"></i> TOTAL DE DESPESAS DO MÊS
                                    </td>
                                    <td class="text-end">
                                        R$ {{ number_format($totalGeral, 2, ',', '.') }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <style>
        /* Estilização customizada avançada para forçar negrito, alinhamento e fontes grandes */
        .table-custom tr.tr-grupo td {
            background-color: #2c3e50 !important;
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 1.1rem !important;
            padding: 14px 16px !important;
            border-top: 2px solid #1a252f;
            border-bottom: 2px solid #1a252f;
        }

        .table-custom tr.tr-categoria td {
            background-color: #f8f9fa;
            color: #333333;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 10px 16px;
            border-bottom: 1px solid #edf2f7;
        }

        .table-custom tr.tr-categoria td.td-cat-valor {
            font-weight: 700 !important;
            color: #2d3748 !important;
            text-align: right !important;
        }

        .table-custom tr.tr-espaco td {
            background-color: transparent !important;
            height: 12px;
            border: none !important;
            padding: 0 !important;
        }

        .table-custom tr.tr-total-geral td {
            background-color: #1a202c !important;
            color: #ffffff !important;
            font-weight: 900 !important;
            font-size: 1.25rem !important;
            padding: 16px !important;
            text-align: left;
            border-top: 3px solid #000;
        }

        .table-custom tr.tr-total-geral td.text-end {
            text-align: right !important;
            color: #f56565 !important; /* Destaque em vermelho suave para o total geral */
        }

        @media print {
            .no-print, .main-sidebar, .main-header, .btn { display: none !important; }
            body { background-color: #ffffff !important; padding: 0 !important; }
            .container-fluid { padding: 0 !important; }
            .print-card { box-shadow: none !important; border: none !important; width: 100% !important; }
            .table-custom tr.tr-grupo td { background-color: #e2e8f0 !important; color: #000000 !important; -webkit-print-color-adjust: exact; }
            .table-custom tr.tr-total-geral td { background-color: #cbd5e0 !important; color: #000000 !important; -webkit-print-color-adjust: exact; }
        }
    </style>
@endsection
