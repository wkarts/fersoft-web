<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Extrato Bancário - {{ $item->nome }}</title>
    <style>
        /* Estilos para visualização em A4 na tela */
        body {
            background: #e0e0e0;
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
        }

        .page {
            width: 21cm;
            min-height: 29.7cm;
            padding: 1.2cm;
            margin: 1cm auto;
            background: white;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }

        /* Cabeçalho da Empresa */
        .table-header { width: 100%; margin-bottom: 10px; border: none; }
        .logo { max-width: 130px; max-height: 90px; }
        .empresa-info { text-align: center; line-height: 1.4; color: #444; }
        .empresa-nome { font-size: 16px; font-weight: 800; color: #000; text-transform: uppercase; }

        .titulo-relatorio {
            text-align: center;
            background: #f8f9fa;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 8px;
            margin: 15px 0;
            font-weight: bold;
            font-size: 13px;
            letter-spacing: 1px;
        }

        /* Tabela de Extrato com alinhamento preciso */
        table.extrato {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .extrato th {
            border-bottom: 2px solid #000;
            padding: 10px 5px;
            text-align: left;
            font-weight: bold;
            color: #000;
        }
        .extrato td {
            padding: 8px 5px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        /* Definição das Larguras das Colunas */
        .col-data { width: 12%; }
        .col-desc { width: 38%; }
        .col-entrada { width: 16%; text-align: right; }
        .col-saida { width: 16%; text-align: right; }
        .col-saldo { width: 18%; text-align: right; }

        .text-right { text-align: right; padding-right: 5px !important; }
        .bold { font-weight: bold; }

        .debito { color: #d9534f; } /* Vermelho suave para saídas/débitos */

        @media print {
            body { background: none; margin: 0; padding: 0; }
            .page {
                margin: 0;
                box-shadow: none;
                width: 100%;
                padding: 0;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="page">
        {{-- Cabeçalho Identidade Visual --}}
        <table class="table-header">
            <tr>
                <td width="20%">
                    @if($empresa->configNota && $empresa->configNota->logo)
                        <img class="logo" src="data:image/png;base64,{{ base64_encode(safe_file_get_contents(public_path('logos/').$empresa->configNota->logo)) }}">
                    @else
                        <img class="logo" src="{{ public_path('imgs/logo.png') }}">
                    @endif
                </td>
                <td width="80%" class="empresa-info">
                    <span class="empresa-nome">{{ $empresa->nome }}</span><br>
                    {{ $empresa->rua }}, {{ $empresa->numero }} - {{ $empresa->bairro }}<br>
                    {{ $empresa->cidade }} ({{ $empresa->cidade->uf ?? 'BA' }}) - {{ $empresa->cep }} | CNPJ: {{ $empresa->cnpj }}<br>
                    Fone: {{ $empresa->telefone }}
                </td>
            </tr>
        </table>

        <div class="titulo-relatorio">EXTRATO BANCÁRIO</div>

        <div style="margin-bottom: 20px; font-size: 11px; line-height: 1.6;">
            <strong>CONTA:</strong> {{ $item->nome }} | <strong>AGÊNCIA:</strong> {{ $item->agencia }} | <strong>CONTA:</strong> {{ $item->conta }}<br>
            <strong>PERÍODO:</strong> {{ $data_inicio ? date('d/m/Y', strtotime($data_inicio)) : 'Início' }} até {{ $data_final ? date('d/m/Y', strtotime($data_final)) : date('d/m/Y') }}
        </div>

        <table class="extrato">
            <thead>
                <thead class="thead-light">
    <tr>
        <th width="15%">DATA</th>
        <th width="40%">DESCRIÇÃO</th>
        <th width="15%" style="text-align: right;">ENTRADA</th>
        <th width="15%" style="text-align: right;">SAÍDA</th>
        <th width="15%" style="text-align: right;">SALDO</th>
    </tr>
</thead>
            </thead>
            <tbody>
                {{-- Saldo Anterior --}}
                <tr class="bold">
                    <td></td>
                    <td>SALDO ANTERIOR AO PERÍODO</td>
                    <td></td>
                    <td></td>
                    <td class="text-right">
                        {{ number_format(abs($saldo_anterior), 2, ',', '.') }}
                        {{ $saldo_anterior >= 0 ? 'C' : 'D' }}
                    </td>
                </tr>

                @php $saldo_acumulado = $saldo_anterior; @endphp

                @foreach($movimentacoes as $m)
                    @php
                        if($m->tipo == 'entrada') {
                            $saldo_acumulado += $m->valor;
                            $entrada = $m->valor;
                            $saida = null;
                        } else {
                            $saldo_acumulado -= $m->valor;
                            $entrada = null;
                            $saida = $m->valor;
                        }
                    @endphp
                    <tr>
                        <td>{{ date('d/m/Y', strtotime($m->data_pagamento ?? $m->created_at)) }}</td>
                        <td style="text-transform: uppercase; font-size: 10px;">{{ $m->descricao }}</td>
                        <td class="text-right">
                            {{ $entrada ? number_format($entrada, 2, ',', '.') : '' }}
                        </td>
                        <td class="text-right">
                            {{ $saida ? number_format($saida, 2, ',', '.') : '' }}
                        </td>
                        <td class="text-right bold {{ $saldo_acumulado < 0 ? 'debito' : '' }}">
                            {{ number_format(abs($saldo_acumulado), 2, ',', '.') }}
                            {{ $saldo_acumulado >= 0 ? 'C' : 'D' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bold" style="background-color: #f8f9fa;">
                    <td colspan="4" class="text-right" style="padding: 12px 5px;">SALDO FINAL EM {{ date('d/m/Y', strtotime($data_final ?? date('Y-m-d'))) }}:</td>
                    <td class="text-right" style="font-size: 13px;">
                        {{ number_format(abs($saldo_acumulado), 2, ',', '.') }}
                        {{ $saldo_acumulado >= 0 ? 'C' : 'D' }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

</body>
</html>
