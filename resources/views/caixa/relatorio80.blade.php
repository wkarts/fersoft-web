{{-- resources/views/caixa/relatorio80.blade.php --}}
    <!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* impressão contínua em bobina, sem paginação */
        @page {
            margin: 0;
            size: 80mm auto;
            page-break-after: avoid;
        }
        html, body {
            width: 80mm;
            margin: 0;
            padding: 0;
            page-break-after: avoid;
            page-break-before: avoid;
            page-break-inside: avoid;
        }
        /* evita quebra dentro de tabelas */
        table, thead, tbody, tr, td, th {
            page-break-inside: avoid !important;
        }
        body {
            font-family: monospace;
            font-size: 10px;
        }
        .header { text-align: center; margin-bottom: 1mm; }
        .header .logo { max-width: 30mm; height: auto; margin-bottom: 0.5mm; }
        .header .company { font-weight: bold; font-size: 11px; line-height: 1.1; }
        .header .info    { font-size: 8px; line-height: 1.1; }

        .title { text-align: center; font-weight: bold; font-size: 12px; margin-bottom: 1mm; }
        .sep   { border-bottom: 1px solid #000; margin: 0.5mm 0; }

        table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }
        thead { display: table-header-group; }
        thead th {
            font-weight: bold;
            font-size: 10px;
            padding: 0 2px 1mm;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            overflow: hidden;
        }
        td {
            padding: 0 2px;
            font-size: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        tr.client-row td { height: 6mm; font-weight: bold; vertical-align: top; }
        tr.data-row   td { height: 4mm; }
        tr.total-row  td { height: 4mm; font-weight: bold; }

        /* larguras fixas em milímetros */
        .col-id    { width: 8mm;  text-align: left; }
        .col-data  { width: 28mm; text-align: left; }
        .col-hora  { width: 20mm; text-align: left; }
        .col-valor { width: 24mm; text-align: right; }

        .totals {
            margin-top: 1mm;
            font-size: 9px;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 1mm;
        }
        .totals div {
            display: flex;
            align-items: center;
        }
        .totals .label {
            /* ocupa todo o espaço até o valor */
        }
        .totals .value {
            margin-left: auto;
            text-align: right;
        }

        .footer {
            text-align: center;
            font-size: 9px;
            margin-top: 4mm; /* mais espaço antes da assinatura */
        }
    </style>
</head>
<body>

<div class="header">
    @if($logoData)
        <img class="logo" src="data:{{ $logoMime }};base64,{{ $logoData }}" alt="Logo">
    @endif
    <div class="company">{{ $config->razao_social }}</div>
    <div class="info">
        CNPJ: {{ $config->cnpj }} | IE: {{ $config->ie }}<br>
        {{ $config->logradouro }}, {{ $config->numero }} – {{ $config->bairro }}<br>
        {{ $config->cidade }}-{{ $config->uf }} CEP {{ $config->cep }}
    </div>
</div>

<div class="title">FECHAMENTO DE CAIXA</div>
<div class="sep"></div>

<table>
    <thead>
    <tr>
        <th class="col-id">ID</th>
        <th class="col-data">Data</th>
        <th class="col-hora">Hora</th>
        <th class="col-valor">Valor</th>
    </tr>
    </thead>
    <tbody>
    @php
        $grupos = collect($vendas)->groupBy(fn($v) => $v->cliente->id ?? 0);
    @endphp

    @foreach($grupos as $clienteId => $itens)
        @php
            $cliente = $itens->first()->cliente;
            $totalCliente = $itens->sum(fn($v) =>
                ($v->consignado||$v->rascunho||$v->estado=='CANCELADO')
                    ? 0
                    : (isset($v->cpf)
                        ? $v->valor_total
                        : ($v->valor_total - $v->desconto + $v->acrescimo)
                      )
            );
            $rawDoc = preg_replace('/\D/', '', $cliente->cpf_cnpj ?? '');
            if(strlen($rawDoc) === 11) {
                $label = 'CPF';
                $docFmt = preg_replace(
                    '/(\d{3})(\d{3})(\d{3})(\d{2})/',
                    '$1.$2.$3-$4',
                    $rawDoc
                );
            } elseif(strlen($rawDoc) === 14) {
                $label = 'CNPJ';
                $docFmt = preg_replace(
                    '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/',
                    '$1.$2.$3/$4-$5',
                    $rawDoc
                );
            } else {
                $label = '';
                $docFmt = '';
            }
        @endphp

        {{-- cliente --}}
        <tr class="client-row">
            <td colspan="4">
                @if($label)
                    {{ $label }}: {{ $docFmt }}<br>
                @endif
                {{ $cliente->razao_social ?? '-' }}
            </td>
        </tr>

        {{-- lançamentos --}}
        @foreach($itens as $v)
            @php
                $dt  = \Carbon\Carbon::parse($v->created_at);
                $raw = (!$v->consignado && !$v->rascunho && $v->estado!='CANCELADO')
                    ? (isset($v->cpf)
                        ? $v->valor_total
                        : $v->valor_total - $v->desconto + $v->acrescimo)
                    : 0;
                $valor = number_format($raw,2,',','.');
            @endphp
            <tr class="data-row">
                <td class="col-id">{{ $v->id }}</td>
                <td class="col-data">{{ $dt->format('d/m/Y') }}</td>
                <td class="col-hora">{{ $dt->format('H:i:s') }}</td>
                <td class="col-valor">{{ $valor }}</td>
            </tr>
        @endforeach

        {{-- total por cliente --}}
        <tr class="total-row">
            <td class="col-id"></td>
            <td class="col-data"></td>
            <td class="col-hora">Total:</td>
            <td class="col-valor">{{ number_format($totalCliente,2,',','.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="totals">
    <div>
        <span class="label">Total Geral:</span>
        <span class="value">{{ number_format($somaVendas,2,',','.') }}</span>
    </div>
    @foreach($somaTiposPagamento as $key => $val)
        @if($val > 0)
            <div>
                <span class="label">{{ \App\Models\VendaCaixa::getTipoPagamento($key) }}:</span>
                <span class="value">{{ number_format($val,2,',','.') }}</span>
            </div>
        @endif
    @endforeach
</div>

<div class="footer">
    <br><br><br><br>
    Assinatura<br><br><br>
    _____________________________________________________<br><br><br><br>
    Impresso em {{ \Carbon\Carbon::now()->format('d/m/Y') }} às {{ \Carbon\Carbon::now()->format('H:i:s') }}
</div>

</body>
</html>
