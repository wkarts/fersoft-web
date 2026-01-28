{{-- resources/views/relatorios/nfe_numeracao_gaps/pdf.blade.php --}}
    <!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório Analítico de Saltos de Numeração de NF-e</title>
    <style>
        @page {
            margin: 20px 25px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #333;
        }

        h1, h2, h3, h4 {
            margin: 0;
            padding: 0;
        }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-left   { text-align: left; }
        .text-nowrap { white-space: nowrap; }

        .header {
            text-align: center;
            margin-bottom: 8px;
        }

        .header h1 {
            font-size: 14px;
            margin-bottom: 2px;
        }

        .header small {
            font-size: 9px;
            color: #666;
        }

        .filters,
        .summary {
            margin-bottom: 6px;
            font-size: 9px;
        }

        .filters table,
        .summary table {
            width: 100%;
            border-collapse: collapse;
        }

        .filters td,
        .summary td,
        .summary th {
            padding: 2px 4px;
            vertical-align: top;
        }

        .summary th {
            background-color: #f3f3f3;
            font-weight: 600;
            border: 1px solid #ccc;
        }

        .summary td {
            border: 1px solid #ccc;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .table th,
        .table td {
            border: 1px solid #ccc;
            padding: 2px 3px;
        }

        .table thead th {
            background-color: #f3f3f3;
            font-weight: 600;
            font-size: 9px;
        }

        .table tbody td {
            font-size: 8.5px;
        }

        .badge {
            display: inline-block;
            padding: 1px 3px;
            border-radius: 3px;
            border: 1px solid #999;
            font-size: 8px;
        }

        .badge-origin-v {
            background-color: #e3f2fd;
            color: #0d47a1;
        }

        .badge-origin-c {
            background-color: #e8f5e9;
            color: #1b5e20;
        }

        .badge-yes {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-no {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .badge-status {
            background-color: #f8f9fa;
            color: #333;
        }

        .legend {
            margin-top: 6px;
            font-size: 8.5px;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Relatório Analítico de Saltos de Numeração de NF-e</h1>
    <small>
        Gerado em {{ now()->format('d/m/Y H:i') }}
        @if(!empty($filters['filial_id']))
            &nbsp;|&nbsp; Filial: {{ $filters['filial_nome'] ?? $filters['filial_id'] }}
        @else
            &nbsp;|&nbsp; Filial: Matriz / Todas
        @endif
    </small>
</div>

{{-- Resumo dos filtros principais --}}
<div class="filters">
    <table>
        <tr>
            <td>
                <strong>Série:</strong>
                {{ $filters['serie'] ?: 'Todas' }}
            </td>
            <td>
                <strong>Período:</strong>
                @if($filters['data_inicial'] || $filters['data_final'])
                    {{ $filters['data_inicial'] ? \Carbon\Carbon::parse($filters['data_inicial'])->format('d/m/Y') : 'Início' }}
                    até
                    {{ $filters['data_final'] ? \Carbon\Carbon::parse($filters['data_final'])->format('d/m/Y') : 'Hoje' }}
                @else
                    Não informado
                @endif
            </td>
            <td>
                <strong>Apenas sem chave:</strong>
                {{ !empty($filters['somente_sem_chave']) ? 'Sim' : 'Não' }}
            </td>
        </tr>
    </table>
</div>

{{-- RESUMO ANALÍTICO --}}
@if(!empty($summary))
    <div class="summary">
        <table>
            <tr>
                <td style="width: 28%;">
                    <table>
                        <tr>
                            <th colspan="2" class="text-left">Totais gerais</th>
                        </tr>
                        <tr>
                            <td>Total de gaps:</td>
                            <td class="text-right">
                                {{ number_format($summary['total_gaps'], 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td>Qtde total de números pulados:</td>
                            <td class="text-right">
                                {{ number_format($summary['total_numeros'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </td>

                <td style="width: 36%;">
                    <table>
                        <tr>
                            <th colspan="3" class="text-left">Resumo por filial</th>
                        </tr>
                        <tr>
                            <th>Filial</th>
                            <th class="text-right">Gaps</th>
                            <th class="text-right">Nº pulados</th>
                        </tr>
                        @foreach($summary['por_filial'] as $filialLabel => $dados)
                            <tr>
                                <td>{{ $filialLabel ?: 'Matriz / Sem filial' }}</td>
                                <td class="text-right">
                                    {{ number_format($dados['gaps'], 0, ',', '.') }}
                                </td>
                                <td class="text-right">
                                    {{ number_format($dados['numeros_pulados'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </td>

                <td style="width: 36%;">
                    <table>
                        <tr>
                            <th colspan="3" class="text-left">Resumo por série</th>
                        </tr>
                        <tr>
                            <th>Série</th>
                            <th class="text-right">Gaps</th>
                            <th class="text-right">Nº pulados</th>
                        </tr>
                        @foreach($summary['por_serie'] as $serie => $dados)
                            <tr>
                                <td>{{ $serie }}</td>
                                <td class="text-right">
                                    {{ number_format($dados['gaps'], 0, ',', '.') }}
                                </td>
                                <td class="text-right">
                                    {{ number_format($dados['numeros_pulados'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>
    </div>
@endif

{{-- Tabela analítica detalhada --}}
<table class="table">
    <thead>
    <tr>
        <th rowspan="2" class="text-center">Filial</th>
        <th rowspan="2" class="text-center">Série</th>

        {{-- agora 5 colunas (antes tinham 6) --}}
        <th colspan="5" class="text-center">Documento anterior</th>
        <th colspan="5" class="text-center">Documento atual</th>
        <th colspan="3" class="text-center">Faixa pulada</th>
    </tr>
    <tr>
        {{-- Documento anterior --}}
        <th class="text-center">Número</th>
        <th class="text-center">Origem<br>(V/C)</th>
        <th class="text-center">Data</th>
        <th class="text-center">Chave</th>
        <th class="text-center">Status</th>

        {{-- Documento atual --}}
        <th class="text-center">Número</th>
        <th class="text-center">Origem<br>(V/C)</th>
        <th class="text-center">Data</th>
        <th class="text-center">Chave</th>
        <th class="text-center">Status</th>

        {{-- Faixa pulada --}}
        <th class="text-center">Nº inicial</th>
        <th class="text-center">Nº final</th>
        <th class="text-center">Qtde</th>
    </tr>
    </thead>
    <tbody>
    @forelse($registros as $registro)
        @php
            $origemAnterior = $registro->origem_usado_anterior ?? '';
            $origemAtual    = $registro->origem_usado_atual ?? '';
        @endphp
        <tr>
            {{-- Filial / Série --}}
            <td class="text-nowrap">{{ $registro->filial_label }}</td>
            <td class="text-center text-nowrap">{{ $registro->serie }}</td>

            {{-- Documento anterior --}}
            <td class="text-right">
                {{ $registro->numero_usado_anterior }}
            </td>
            <td class="text-center">
                @if($origemAnterior === 'v' || $origemAnterior === 'V')
                    <span class="badge badge-origin-v" title="Venda">V</span>
                @elseif($origemAnterior === 'c' || $origemAnterior === 'C')
                    <span class="badge badge-origin-c" title="Compra">C</span>
                @else
                    {{ strtoupper(substr($origemAnterior, 0, 1)) ?: '-' }}
                @endif
            </td>
            <td class="text-center">
                {{ optional($registro->data_doc_anterior)->format('d/m/Y') }}
            </td>
            <td class="text-nowrap">
                @if(empty($registro->chave_nfe_anterior))
                    <span class="badge badge-yes">Sem chave</span>
                @else
                    {{ $registro->chave_nfe_anterior }}
                @endif
            </td>
            <td class="text-center">
                <span class="badge badge-status">
                    {{ $registro->status_nfe_anterior ?? '-' }}
                </span>
            </td>

            {{-- Documento atual --}}
            <td class="text-right">
                {{ $registro->numero_usado_atual }}
            </td>
            <td class="text-center">
                @if($origemAtual === 'v' || $origemAtual === 'V')
                    <span class="badge badge-origin-v" title="Venda">V</span>
                @elseif($origemAtual === 'c' || $origemAtual === 'C')
                    <span class="badge badge-origin-c" title="Compra">C</span>
                @else
                    {{ strtoupper(substr($origemAtual, 0, 1)) ?: '-' }}
                @endif
            </td>
            <td class="text-center">
                {{ optional($registro->data_doc_atual)->format('d/m/Y') }}
            </td>
            <td class="text-nowrap">
                @if(empty($registro->chave_nfe_atual))
                    <span class="badge badge-yes">Sem chave</span>
                @else
                    {{ $registro->chave_nfe_atual }}
                @endif
            </td>
            <td class="text-center">
                <span class="badge badge-status">
                    {{ $registro->status_nfe_atual ?? '-' }}
                </span>
            </td>

            {{-- Faixa pulada --}}
            <td class="text-right">
                {{ $registro->numero_inicial_pulado }}
            </td>
            <td class="text-right">
                {{ $registro->numero_final_pulado }}
            </td>
            <td class="text-right">
                {{ $registro->quantidade_pulada }}
            </td>
        </tr>
    @empty
        <tr>
            {{-- agora são 15 colunas no total --}}
            <td colspan="15" class="text-center">
                Nenhum registro encontrado para os filtros informados.
            </td>
        </tr>
    @endforelse
    </tbody>
</table>

<div class="legend">
    <strong>Legenda:</strong>
    Origem: V = Venda, C = Compra.
    "Sem chave" indica documento sem chave de NF-e associada, exibido diretamente na coluna <strong>Chave</strong>.
</div>
</body>
</html>
