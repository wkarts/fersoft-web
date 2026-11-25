<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Pesagem - #{{ $pesagem->id }}</title>
    <style>
        @page {
            margin: 2mm;
        }
        body {
            font-family: 'Courier New', Courier, monospace; /* Fonte monoespaçada de ticket */
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
            width: 100%;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-weight-bold { font-weight: bold; }
        .mb-1 { margin-bottom: 5px; }
        .mb-2 { margin-bottom: 10px; }
        .mt-2 { margin-top: 10px; }
        .divider { border-top: 1px dashed #000; margin: 10px 0; }

        .logo { max-width: 150px; max-height: 60px; margin-bottom: 5px; }
        .qrcode { width: 120px; height: 120px; margin: 0 auto; }

        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 2px 0; }

        .box-emitente {
            border: 1px solid #000;
            padding: 5px;
            font-size: 9px;
            margin-top: 15px;
            margin-bottom: 25px;
        }
        .assinatura {
            border-top: 1px solid #000;
            width: 90%;
            margin: 30px auto 10px auto;
            text-align: center;
            padding-top: 3px;
        }
    </style>
</head>
<body>

<!-- CABEÇALHO (LOGO E QR CODE) -->
<div class="text-center mb-2">
    @if($emitente && $emitente->logo)
    <img src="{{ env('PATH_URL') . '/imgs/' . $emitente->logo }}" class="logo">
    @else
    <h2 style="margin: 0;">{{ $emitente->razao_social ?? 'SERVIÇO DE PESAGEM' }}</h2>
    @endif
</div>

<div class="text-center mb-1">
    <img src="{{ $qrcodeSvg }}" class="qrcode">
</div>

<div class="text-center font-weight-bold" style="font-size: 10px;">
    Ticket: {{ $pesagem->token }}
</div>

<div class="divider"></div>

<!-- INFORMAÇÕES DA PESAGEM -->
<table>
    <tr>
        <td class="font-weight-bold">Status:</td>
        <td>CONCLUÍDO</td>
    </tr>
    <tr>
        <td class="font-weight-bold">Pesagem ID:</td>
        <td>{{ $pesagem->id }}</td>
    </tr>
    <tr>
        <td class="font-weight-bold">Data:</td>
        <td>{{ \Carbon\Carbon::parse($pesagem->created_at)->format('d/m/Y H:i') }}</td>
    </tr>
    <tr>
        <td class="font-weight-bold">Placa ¹:</td>
        <td>{{ $pesagem->placa_veiculo }}</td>
    </tr>
    <tr>
        <td class="font-weight-bold">Placa ²:</td>
        <td>{{ $pesagem->placa_carreta ?: 'N/A' }}</td>
    </tr>
    <tr>
        <td class="font-weight-bold">Motorista:</td>
        <td>{{ $pesagem->motorista_nome }}</td>
    </tr>
    <tr>
        <td class="font-weight-bold">Tipo:</td>
        <td>SERVIÇO AVULSO</td>
    </tr>
</table>

<div class="mt-2">
    <span class="font-weight-bold">Cliente:</span><br>
    {{ $pesagem->cliente->razao_social }}<br>
    CPF/CNPJ: {{ $pesagem->cliente->cpf_cnpj }}
</div>

<div class="divider"></div>

<!-- PESOS -->
<table>
    <tr>
        <td>Peso Entrada:</td>
        <td class="text-right">{{ number_format($pesagem->peso_bruto, 2, ',', '.') }} kg</td>
    </tr>
    @if($pesagem->tara > 0)
    <tr>
        <td>Peso Saída:</td>
        <td class="text-right">{{ number_format($pesagem->tara, 2, ',', '.') }} kg</td>
    </tr>
    @endif
    <tr>
        <td class="font-weight-bold">Saldo (Peso Líquido):</td>
        <td class="text-right font-weight-bold">{{ number_format($pesagem->peso_liquido, 2, ',', '.') }} kg</td>
    </tr>

    <!-- PREÇO CEGO: Só imprime se for maior que zero -->
    @if($pesagem->valor_servico > 0)
    <tr>
        <td class="font-weight-bold" style="padding-top: 5px;">Valor do Serviço:</td>
        <td class="text-right font-weight-bold" style="padding-top: 5px;">R$ {{ number_format($pesagem->valor_servico, 2, ',', '.') }}</td>
    </tr>
    @endif
</table>

<!-- RODAPÉ: EMITENTE -->
<div class="box-emitente">
    <table style="width: 100%; font-size: 9px;">
        <tr>
            <td colspan="2"><span class="font-weight-bold">Razão Social:</span> {{ $emitente->razao_social ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><span class="font-weight-bold">CNPJ:</span> {{ $emitente->cnpj ?? 'N/A' }}</td>
            <td><span class="font-weight-bold">IE:</span> {{ $emitente->ie ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><span class="font-weight-bold">Município:</span> {{ $emitente->cidade->nome ?? 'N/A' }}</td>
            <td><span class="font-weight-bold">UF:</span> {{ $emitente->cidade->uf ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="font-weight-bold">Fone:</span> {{ $emitente->telefone ?? 'N/A' }}</td>
        </tr>
    </table>
</div>

<!-- ASSINATURA -->
<div class="assinatura">
    Assinatura do Motorista
</div>

</body>
</html>
