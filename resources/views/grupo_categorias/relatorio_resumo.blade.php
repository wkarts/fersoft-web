@php
    $title = $title ?? 'Resumo de Despesas';
    
    $mesesNomes = [
        1 => 'JANEIRO', 2 => 'FEVEREIRO', 3 => 'MARÇO', 4 => 'ABRIL',
        5 => 'MAIO', 6 => 'JUNHO', 7 => 'JULHO', 8 => 'AGOSTO',
        9 => 'SETEMBRO', 10 => 'OUTUBRO', 11 => 'NOVEMBRO', 12 => 'DEZEMBRO'
    ];
@endphp

@extends('default.layout')

@section('content')
<div class="container-fluid py-3">

    {{-- Filtro Dashboard --}}
    <div class="card shadow-sm border-0 mb-4 rounded-3 no-print bg-white">
        <div class="card-body py-3">
            <form action="{{ url('/relatorios-financeiros/resumo-despesas') }}" method="GET" class="row g-3 align-items-end">
                
                <div class="col-md-4">
                    <label class="form-label text-muted fw-bold small mb-1">Unidade / Empresa:</label>
                    <select name="filial_id" class="form-select form-select-sm shadow-none border-secondary-subtle" onchange="this.form.submit()">
                        <option value="matriz" {{ ($filialId == 'matriz' || empty($filialId)) ? 'selected' : '' }}>MATRIZ (Principal)</option>
                        <option value="todas" {{ $filialId == 'todas' ? 'selected' : '' }}>-- TODAS AS UNIDADES (Consolidado) --</option>
                        @foreach($filiais as $f)
                            <option value="{{ $f->id }}" {{ $filialId == $f->id ? 'selected' : '' }}>
                                FILIAL: {{ $f->nome_fantasia ?? $f->razao_social ?? $f->descricao }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label text-muted fw-bold small mb-1">Ano:</label>
                    <input type="number" name="ano" class="form-control form-control-sm shadow-none border-secondary-subtle" value="{{ $ano }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label text-muted fw-bold small mb-1">Mês Referência:</label>
                    <select name="mes" class="form-select form-select-sm shadow-none border-secondary-subtle">
                        @foreach($mesesNomes as $num => $nome)
                            <option value="{{ sprintf('%02d', $num) }}" {{ sprintf('%02d', $num) == $mes ? 'selected' : '' }}>
                                {{ $nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold shadow-sm">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary w-100 fw-bold">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Layout em 3 Colunas Idêntico ao Excel --}}
    <div class="row g-3">

        {{-- COLUNA 1: DESPESAS POR CLASSIFICAÇÃO COM FROTA EMBUTIDA ABAIXO --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-header bg-white border-bottom text-center py-3">
                    <h6 class="fw-bold mb-1 text-dark text-uppercase tracking-wide">DESPESAS POR CLASSIFICAÇÃO</h6>
                    <span class="badge bg-danger-subtle text-danger fw-bold border border-danger-subtle px-3 py-1">
                        {{ $nomeUnidade }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <tbody class="border-top-0">
                                {{-- 1. GRUPOS NORMAIS (SEM FROTA) --}}
                                @forelse($gruposSemFrota as $grupoNome => $valorGrupo)
                                    <tr>
                                        <td class="ps-3 fw-semibold text-uppercase text-dark">{{ $grupoNome ?: 'OUTROS / SEM GRUPO' }}</td>
                                        <td class="text-end pe-3 fw-bold text-dark">R$ {{ number_format($valorGrupo, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center py-3 text-muted">Sem despesas operacionais no mês.</td>
                                    </tr>
                                @endforelse

                                {{-- SUBTOTAL SEM FROTA --}}
                                <tr class="table-light border-top border-bottom border-dark">
                                    <td class="ps-3 fw-bold text-danger">SUBTOTAL (SEM FROTA)</td>
                                    <td class="text-end pe-3 fw-bold text-danger">R$ {{ number_format($totalSemFrota, 2, ',', '.') }}</td>
                                </tr>

                                {{-- CABEÇALHO DIVISOR FROTA --}}
                                <tr class="table-secondary border-top border-dark text-center">
                                    <td colspan="2" class="fw-bold text-uppercase py-2 text-dark">DESPESAS FROTA</td>
                                </tr>

                                {{-- 2. CATEGORIAS DE FROTA --}}
                                @forelse($categoriasFrota as $catNome => $valorCat)
                                    <tr>
                                        <td class="ps-3 fw-semibold text-uppercase text-dark">{{ $catNome }}</td>
                                        <td class="text-end pe-3 fw-bold text-dark">R$ {{ number_format($valorCat, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center py-2 text-muted">Sem despesas de frota no mês.</td>
                                    </tr>
                                @endforelse

                                {{-- SUBTOTAL FROTA --}}
                                <tr class="table-light border-top border-dark">
                                    <td class="ps-3 fw-bold text-danger">SUBTOTAL FROTA</td>
                                    <td class="text-end pe-3 fw-bold text-danger">R$ {{ number_format($totalFrota, 2, ',', '.') }}</td>
                                </tr>

                                {{-- VALOR TOTAL COM FROTA --}}
                                <tr class="table-danger border-top border-2 border-danger">
                                    <td class="ps-3 fw-bold text-uppercase">VALOR TOTAL (GERAL)</td>
                                    <td class="text-end pe-3 fw-bold text-danger">R$ {{ number_format($totalGeralComFrota, 2, ',', '.') }}</td>
                                </tr>

                                {{-- LINHA EM DESTAQUE DO MÊS SELECIONADO --}}
                                <tr class="table-warning border-top border-warning fw-bold text-dark">
                                    <td class="ps-3">{{ $mesesNomes[(int)$mes] }}</td>
                                    <td class="text-end pe-3 text-danger">R$ {{ number_format($totalGeralComFrota, 2, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- COLUNA 2: DESPESAS POR MÊS E CAIXINHA --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100 rounded-3">
                <div class="card-header bg-white border-bottom text-center py-3">
                    <h6 class="fw-bold mb-1 text-dark text-uppercase tracking-wide">DESPESAS POR MÊS</h6>
                    <span class="badge bg-danger-subtle text-danger fw-bold border border-danger-subtle px-3 py-1">
                        {{ $nomeUnidade }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr class="text-secondary small fw-bold">
                                    <th class="ps-3">MÊS</th>
                                    <th class="text-end pe-3">VALOR</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mesesNomes as $num => $nomeMes)
                                    <tr class="{{ sprintf('%02d', $num) == $mes ? 'table-warning fw-bold' : '' }}">
                                        <td class="ps-3 fw-semibold">{{ $nomeMes }}</td>
                                        <td class="text-end pe-3 fw-bold">
                                            {{ $despesasPorMes[$num] > 0 ? 'R$ ' . number_format($despesasPorMes[$num], 2, ',', '.') : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-group-divider">
                                <tr class="fw-bold text-danger bg-light">
                                    <td class="ps-3">TOTAL</td>
                                    <td class="text-end pe-3">R$ {{ number_format($totalAno, 2, ',', '.') }}</td>
                                </tr>
                                <tr class="fw-bold text-primary bg-light">
                                    <td class="ps-3">MÉDIA</td>
                                    <td class="text-end pe-3">R$ {{ number_format($mediaMensal, 2, ',', '.') }}</td>
                                </tr>
                                @foreach($mesesNomes as $num => $nomeMes)
                                    @if($caixinhaPorMes[$num] > 0)
                                        <tr class="table-warning border-top border-warning fw-bold text-dark">
                                            <td class="ps-3"><i class="fas fa-coins me-1 text-warning"></i> CAIXINHA {{ $nomeMes }}</td>
                                            <td class="text-end pe-3 text-danger">R$ {{ number_format($caixinhaPorMes[$num], 2, ',', '.') }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .tracking-wide { letter-spacing: 0.05em; }
    @media print {
        .no-print, .main-sidebar, .main-header { display: none !important; }
        .content-wrapper { margin-left: 0 !important; }
        .col-lg-6 { width: 50% !important; float: left !important; }
    }
</style>
@endsection