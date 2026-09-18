@php
    $title = $title ?? 'Comparativo de Despesas';
@endphp

@extends('default.layout')

@section('content')
<div class="container-fluid py-4">

    {{-- Filtro de Mês, Ano e Matriz/Filial --}}
    <div class="card shadow-sm border-0 mb-4 no-print bg-white">
        <div class="card-body py-2">
            <form action="{{ url('/relatorios-financeiros/comparativo-meses') }}" method="GET" class="row g-2 align-items-center">
                
                <div class="col-md-4">
                    <label class="form-label mb-0 fw-bold small">Unidade / Empresa:</label>
                    <select name="filial_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="matriz" {{ ($filialId == 'matriz' || empty($filialId)) ? 'selected' : '' }}>MATRIZ (Principal)</option>
                        <option value="todas" {{ $filialId == 'todas' ? 'selected' : '' }}>-- TODAS AS UNIDADES --</option>
                        @foreach($filiais as $f)
                            <option value="{{ $f->id }}" {{ $filialId == $f->id ? 'selected' : '' }}>
                                FILIAL: {{ $f->nome_fantasia ?? $f->razao_social ?? $f->descricao }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-0 fw-bold small">Ano:</label>
                    <input type="number" name="ano" class="form-control form-control-sm" value="{{ $ano }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-0 fw-bold small">Mês de Referência:</label>
                    <select name="mes" class="form-select form-select-sm">
                        @foreach($mesesNomes as $num => $nome)
                            <option value="{{ sprintf('%02d', $num) }}" {{ sprintf('%02d', $num) == $mes ? 'selected' : '' }}>
                                {{ $nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">
                        <i class="fas fa-filter me-1"></i> Comparar
                    </button>
                    <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary w-100 fw-bold">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabela Estilo Excel da Imagem --}}
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card border-dark shadow-sm">
                
                {{-- Cabeçalho Estilo Excel Azul Claro --}}
                <div class="card-header text-center py-3" style="background-color: #b4c6e7; border-bottom: 2px solid #000;">
                    <h4 class="fw-bold mb-1 text-uppercase text-dark" style="letter-spacing: 0.05em;">
                        LEVANTAMENTO DESPESAS GRUPO {{ $nomeUnidade }}
                    </h4>
                    <h5 class="fw-bold mb-0 text-dark text-uppercase">
                        {{ $nomeMesAtual }}
                    </h5>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0" style="border: 2px solid #000; font-size: 0.9rem;">
                            <thead>
                                <tr class="text-center fw-bold text-dark text-uppercase" style="background-color: #d9e1f2; border-bottom: 2px solid #000;">
                                    <th style="border: 1px solid #000; width: 50%;" class="text-start ps-3">TIPO DESPESA</th>
                                    <th style="border: 1px solid #000; width: 25%;" class="text-end pe-3">VALOR ({{ $nomeMesAtual }})</th>
                                    <th style="border: 1px solid #000; width: 25%;" class="text-end pe-3">MÊS ANTERIOR ({{ $nomeMesAnterior }})</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalAtual = 0;
                                    $totalAnterior = 0;
                                @endphp

                                @forelse($comparativo as $row)
                                    @php
                                        $totalAtual += $row['valor_atual'];
                                        $totalAnterior += $row['valor_anterior'];
                                    @endphp
                                    <tr>
                                        <td class="fw-bold text-uppercase ps-3" style="border: 1px solid #000;">
                                            {{ $row['grupo'] }}
                                        </td>
                                        <td class="text-end pe-3 fw-bold text-dark" style="border: 1px solid #000;">
                                            R$ {{ number_format($row['valor_atual'], 2, ',', '.') }}
                                        </td>
                                        <td class="text-end pe-3 fw-bold text-secondary" style="border: 1px solid #000;">
                                            R$ {{ number_format($row['valor_anterior'], 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">
                                            Nenhuma movimentação registrada no período.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot style="background-color: #f2f2f2; border-top: 2px solid #000;">
                                <tr class="fw-bold text-danger" style="font-size: 1rem;">
                                    <td class="ps-3" style="border: 1px solid #000;">TOTAL GENERALIZADO</td>
                                    <td class="text-end pe-3" style="border: 1px solid #000;">R$ {{ number_format($totalAtual, 2, ',', '.') }}</td>
                                    <td class="text-end pe-3" style="border: 1px solid #000;">R$ {{ number_format($totalAnterior, 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

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