<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 5px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        .title { font-size: 13px; font-weight: bold; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="text-center">
        @if($logoData)
            <img src="data:{{ $logoMime }};base64,{{ $logoData }}" style="max-height: 40px;"><br>
        @endif
        <span class="title">{{ $config->razao_social ?? 'EMPRESA' }}</span><br>
        <span>CNPJ: {{ $config->cnpj ?? '' }}</span>
        <div class="line"></div>
        <span class="bold" style="font-size: 12px;">RELATÓRIO DE FECHAMENTO DE CAIXA</span><br>
        <span>Turno ID: #{{ $abertura->id }}</span>
    </div>

    <div class="line"></div>

    <table>
        <tr>
            <td>Abertura:</td>
            <td class="text-right">{{ \Carbon\Carbon::parse($abertura->created_at)->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>Fechamento:</td>
            <td class="text-right">{{ \Carbon\Carbon::parse($abertura->updated_at)->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>Operador:</td>
            <td class="text-right">{{ $abertura->usuario->nome ?? '--' }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="bold">MOVIMENTO FINANCEIRO:</div>
    <table>
        <tr>
            <td>Valor de Abertura (Troco):</td>
            <td class="text-right">R$ {{ number_format($abertura->valor, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>(+) Total de Suprimentos:</td>
            <td class="text-right">R$ {{ number_format($somaSuprimento, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>(-) Total de Sangrias:</td>
            <td class="text-right">R$ {{ number_format($somaSangria, 2, ',', '.') }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="bold">TOTAL POR FORMA DE PAGAMENTO:</div>
    <table>
        @foreach($somaTiposPagamento as $key => $tp)
            @if($tp > 0)
            <tr>
                <td>{{ \App\Models\VendaCaixa::getTipoPagamento($key) }}:</td>
                <td class="text-right">R$ {{ number_format($tp, 2, ',', '.') }}</td>
            </tr>
            @endif
        @endforeach
    </table>

    <div class="line"></div>

    <table>
        <tr style="font-size: 12px;">
            <td class="bold">TOTAL GERAL DE VENDAS:</td>
            <td class="text-right bold">R$ {{ number_format($somaVendas, 2, ',', '.') }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <div style="background-color: #eee; padding: 6px; text-align: center;">
        <span style="font-size: 11px;" class="bold">VALOR EM DINHEIRO NO CAIXA (GAVETA):</span><br>
        <span style="font-size: 14px;" class="bold">R$ {{ number_format($dinheiroEmGaveta, 2, ',', '.') }}</span>
    </div>

    <div class="line"></div>
    <div class="text-center" style="font-size: 9px; color: #555;">
        Gerado por FerSoft ERP<br>
        {{ date('d/m/Y H:i:s') }}
    </div>
</body>
</html>
