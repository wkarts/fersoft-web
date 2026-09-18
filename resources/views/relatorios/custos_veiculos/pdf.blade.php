<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Apuração de Custos por Veículo</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #0284c7; padding-bottom: 8px; }
        .header h2 { margin: 0; color: #0f172a; text-transform: uppercase; }
        .header p { margin: 3px 0 0; color: #64748b; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #0284c7; color: white; padding: 6px; text-align: left; font-size: 10px; }
        td { padding: 5px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        .text-right { text-align: right; }
        .group-header { background-color: #f1f5f9; font-weight: bold; font-size: 11px; }
        .total-row { font-weight: bold; background-color: #e2e8f0; }
    </style>
</head>
<body>

<div class="header">
    <h2>Apuração de Custos por Veículos</h2>
    <p>
        <strong>Regime:</strong> {{ strtoupper($regime) }} |
        <strong>Período:</strong> {{ date('d/m/Y', strtotime($dataInicio)) }} até {{ date('d/m/Y', strtotime($dataFim)) }} |
        <strong>Visão:</strong> {{ ucfirst($tipoVisao) }}
    </p>
</div>

{{-- Visão 1: Resumido por Veículo --}}
@if($tipoVisao === 'resumido')
    <table>
        <thead>
        <tr>
            <th>Placa</th>
            <th>Veículo</th>
            <th class="text-right">Qtd. Títulos</th>
            <th class="text-right">Total Custo (R$)</th>
        </tr>
        </thead>
        <tbody>
        @php $grandTotal = 0; @endphp
        @foreach($dados as $item)
            @php $grandTotal += $item->total_custo; @endphp
            <tr>
                <td><strong>{{ $item->placa }}</strong></td>
                <td>{{ $item->marca }} {{ $item->modelo }}</td>
                <td class="text-right">{{ $item->total_titulos }}</td>
                <td class="text-right">{{ number_format($item->total_custo, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="3">TOTAL GERAL</td>
            <td class="text-right">{{ number_format($grandTotal, 2, ',', '.') }}</td>
        </tr>
        </tbody>
    </table>
@endif

{{-- Visão 2: Analítico por Categoria --}}
@if($tipoVisao === 'categoria')
    @foreach($dados as $placa => $categorias)
        <table>
            <thead>
            <tr class="group-header">
                <th colspan="3">VEÍCULO (PLACA): {{ $placa }}</th>
            </tr>
            <tr>
                <th>Categoria da Conta</th>
                <th class="text-right">Qtd. Títulos</th>
                <th class="text-right">Total (R$)</th>
            </tr>
            </thead>
            <tbody>
            @php $subtotal = 0; @endphp
            @foreach($categorias as $cat)
                @php $subtotal += $cat->total_custo; @endphp
                <tr>
                    <td>{{ $cat->categoria_nome }}</td>
                    <td class="text-right">{{ $cat->total_titulos }}</td>
                    <td class="text-right">{{ number_format($cat->total_custo, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">TOTAL DO VEÍCULO</td>
                <td class="text-right">{{ number_format($subtotal, 2, ',', '.') }}</td>
            </tr>
            </tbody>
        </table>
        <br>
    @endforeach
@endif

{{-- Visão 3: Analítico Lançamentos --}}
@if($tipoVisao === 'lancamentos')
    @foreach($dados as $placa => $lancamentos)
        <table>
            <thead>
            <tr class="group-header">
                <th colspan="6">VEÍCULO (PLACA): {{ $placa }}</th>
            </tr>
            <tr>
                <th>Vencimento / Pgto</th>
                <th>NF / Referência</th>
                <th>Categoria</th>
                <th>Status</th>
                <th class="text-right">Valor Integral</th>
                <th class="text-right">Valor Pago</th>
            </tr>
            </thead>
            <tbody>
            @php $totalIntegral = 0; $totalPago = 0; @endphp
            @foreach($lancamentos as $lanc)
                @php
                    $totalIntegral += $lanc->valor_integral;
                    $totalPago += $lanc->valor_pago;
                @endphp
                <tr>
                    <td>
                        {{ date('d/m/Y', strtotime($lanc->data_vencimento)) }}
                        @if($lanc->data_pagamento) <br><small>Pg: {{ date('d/m/Y', strtotime($lanc->data_pagamento)) }}</small> @endif
                    </td>
                    <td>NF: {{ $lanc->numero_nota_fiscal }} <br><small>{{ $lanc->referencia }}</small></td>
                    <td>{{ $lanc->categoria_nome }}</td>
                    <td>{{ $lanc->status == 1 ? 'Pago' : 'Pendente' }}</td>
                    <td class="text-right">{{ number_format($lanc->valor_integral, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($lanc->valor_pago, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4">TOTAL DO VEÍCULO</td>
                <td class="text-right">{{ number_format($totalIntegral, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totalPago, 2, ',', '.') }}</td>
            </tr>
            </tbody>
        </table>
        <br>
    @endforeach
@endif

</body>
</html>
