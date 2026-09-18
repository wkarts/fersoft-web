<!DOCTYPE html>
<html>
<head>
    <title>Fatura de Locação #{{$locacao->id}}</title>
    <style type="text/css">
        body { font-family: sans-serif; font-size: 12px; }
        .box { border: 1px solid #000; padding: 8px; margin-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; text-align: center; }
        .table-itens { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table-itens th, .table-itens td { border: 1px solid #000; padding: 6px; text-align: left; }
        .bg-head { background-color: #eee; }
    </style>
</head>
<body>
<div class="box">
    <div class="title">FATURA DE LOCAÇÃO DE BENS MÓVEIS</div>
    <div style="text-align: center; font-size: 10px;">(Isento de ISSQN conforme Súmula Vinculante 31 do STF)</div>
</div>

<table style="width: 100%" class="box">
    <tr>
        <td style="width: 60%">
            <strong>EMITENTE / LOCADOR:</strong><br>
            <strong>{{$config->razao_social}}</strong><br>
            CNPJ: {{str_replace(" ", "", $config->cnpj)}} - IE: {{$config->ie}}<br>
            {{$config->logradouro}}, {{$config->numero}} - {{$config->bairro}} - {{$config->municipio}} ({{$config->UF}})
        </td>
        <td style="width: 40%; text-align: right;">
            <span style="font-size: 18px; font-weight: bold;">FATURA Nº {{$locacao->id}}</span><br>
            Data Emissão: {{ date('d/m/Y') }}<br>
            Vencimento: {{ \Carbon\Carbon::parse($locacao->primeiro_vencimento)->format('d/m/Y') }}
        </td>
    </tr>
</table>

<div class="box">
    <strong>DESTINATÁRIO / LOCATÁRIO:</strong><br>
    Nome / Razão Social: <strong>{{$locacao->cliente->razao_social}}</strong><br>
    CPF/CNPJ: {{$locacao->cliente->cpf_cnpj}} | Tel: {{$locacao->cliente->telefone}}<br>
    Endereço Obra/Entrega: {{$locacao->rua_entrega}}, {{$locacao->numero_entrega}} - {{$locacao->bairro_entrega}}
</div>

<table class="table-itens">
    <thead>
    <tr class="bg-head">
        <th>Item / Equipamento</th>
        <th>Cód. Patrimônio</th>
        <th>Período Locação</th>
        <th>Cálculo</th>
        <th>Valor Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($locacao->itens as $i)
        <tr>
            <td>{{ $i->produto->nome }}</td>
            <td>{{ $i->codigo_patrimonio ?? '--' }}</td>
            <td>{{ \Carbon\Carbon::parse($locacao->inicio)->format('d/m/Y') }} até {{ $locacao->fim != '1969-12-31' ? \Carbon\Carbon::parse($locacao->fim)->format('d/m/Y') : 'Em Aberto' }}</td>
            <td>{{ strtoupper($locacao->tipo_calculo) }}</td>
            <td>R$ {{ number_format($i->valor, 2, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<br>
<table style="width: 100%">
    <tr>
        <td style="width: 60%">
            <strong>FORMA DE PAGAMENTO:</strong> {{ strtoupper($locacao->forma_pagamento) }} ({{$locacao->quantidade_parcelas}}x)<br>
            <strong>OBSERVAÇÕES:</strong> {{ $locacao->observacao ?? 'Sem observações.' }}
        </td>
        <td style="width: 40%; text-align: right;" class="box">
            Subtotal Itens: R$ {{ number_format($locacao->total - $locacao->valor_frete, 2, ',', '.') }}<br>
            Frete / Mobilização: R$ {{ number_format($locacao->valor_frete, 2, ',', '.') }}<br>
            <hr>
            <strong style="font-size: 16px;">VALOR TOTAL: R$ {{ number_format($locacao->total, 2, ',', '.') }}</strong>
        </td>
    </tr>
</table>

<br><br><br>
<table style="width: 100%; text-align: center;">
    <tr>
        <td>_____________________________________<br>{{$config->razao_social}}</td>
        <td>_____________________________________<br>{{$locacao->cliente->razao_social}}</td>
    </tr>
</table>
</body>
</html>
