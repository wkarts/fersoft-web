<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Extrato da Conta Bancária</title>
    <style>
        @page { margin: 1.2cm; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #2b2b2b;
            margin: 0;
            padding: 0;
        }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .logo-img { max-width: 120px; max-height: 60px; }
        .title { font-size: 14px; font-weight: 800; text-transform: uppercase; text-align: center; }
        .empresa-info { text-align: center; font-size: 10px; color: #555; }

        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .table-data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-data th {
            background-color: #343a40;
            color: #ffffff;
            font-weight: bold;
            padding: 6px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }
        .table-data td { padding: 6px; border-bottom: 1px solid #dee2e6; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .credito { color: #198754; font-weight: bold; }
        .debito { color: #dc3545; font-weight: bold; }
        .saldo-box { margin-top: 15px; text-align: right; font-size: 11px; font-weight: bold; }
    </style>
</head>
<body>

<!-- Cabeçalho -->
<table class="header-table">
    <tr>
        <td width="25%">
            @if(!empty($caminhoLogo))
                <img class="logo-img" src="{{ $caminhoLogo }}">
            @else
                <strong>{{ $empresa->nome ?? 'EMPRESA' }}</strong>
            @endif
        </td>
        <td width="50%" class="text-center">
            <div class="title">Extrato Bancário</div>
            <div class="empresa-info">
                {{ $empresa->nome ?? 'Empresa' }} | CNPJ: {{ $config->cnpj ?? $empresa->cnpj ?? '-' }}
            </div>
        </td>
        <td width="25%" class="text-right">
            <small>Período:</small><br>
            <strong>{{ $data_inicio ? date('d/m/Y', strtotime($data_inicio)) : 'Início' }} a {{ $data_final ? date('d/m/Y', strtotime($data_final)) : date('d/m/Y') }}</strong>
        </td>
    </tr>
</table>

<!-- Detalhes da Conta -->
<div class="info-box">
    <table width="100%">
        <tr>
            <td><strong>Conta:</strong> {{ $item->nome }}</td>
            <td><strong>Agência:</strong> {{ $item->agencia ?? '-' }}</td>
            <td><strong>Nº Conta:</strong> {{ $item->conta ?? '-' }}</td>
            <td class="text-right"><strong>Saldo Anterior:</strong> R$ {{ number_format($saldo_anterior, 2, ',', '.') }}</td>
        </tr>
    </table>
</div>

<!-- Tabela de Movimentações -->
<table class="table-data">
    <thead>
    <tr>
        <th width="12%">Data</th>
        <th width="48%">Descrição</th>
        <th width="15%" class="text-center">Tipo</th>
        <th width="25%" class="text-right">Valor (R$)</th>
    </tr>
    </thead>
    <tbody>
    @php $saldoAcumulado = $saldo_anterior; @endphp
    @forelse($movimentacoes as $transacao)
        @php
            if ($transacao->tipo == 'entrada') {
                $saldoAcumulado += $transacao->valor;
            } else {
                $saldoAcumulado -= $transacao->valor;
            }
        @endphp
        <tr>
            <td class="text-center">{{ date('d/m/Y', strtotime($transacao->data_pagamento ?? $transacao->created_at)) }}</td>
            <td>{{ $transacao->descricao }}</td>
            <td class="text-center">
                @if($transacao->tipo == 'entrada')
                    <span class="credito">ENTRADA (+)</span>
                @else
                    <span class="debito">SAÍDA (-)</span>
                @endif
            </td>
            <td class="text-right">
                        <span class="{{ $transacao->tipo == 'entrada' ? 'credito' : 'debito' }}">
                            {{ $transacao->tipo == 'entrada' ? '+' : '-' }} R$ {{ number_format($transacao->valor, 2, ',', '.') }}
                        </span>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="4" class="text-center" style="padding: 15px; color: #777;">
                Nenhuma movimentação registrada no período selecionado.
            </td>
        </tr>
    @endforelse
    </tbody>
</table>

<!-- Saldo Final -->
<div class="saldo-box">
    Saldo Atual em {{ date('d/m/Y') }}:
    <span class="{{ $saldoAcumulado >= 0 ? 'credito' : 'debito' }}">
            R$ {{ number_format($saldoAcumulado, 2, ',', '.') }}
        </span>
</div>

</body>
</html>
