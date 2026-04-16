<!DOCTYPE html>
<html>
<head>
    <title>Relatório de Inventário</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $empresa->nome_fantasia }}</h2>
        <h3>Relatório de Inventário Consolidado - {{ $mes }}/{{ $ano }}</h3>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cód. Barras</th>
                <th>Produto</th>
                <th>S. Inicial</th>
                <th>Entradas</th>
                <th>Saídas</th>
                <th>Saldo Atual</th>
                <th>Custo Unit.</th>
                <th>Custo Total</th>
                <th style="width: 100px">Conf. Física</th>
            </tr>
        </thead>
        <tbody>
            @php $totalGeral = 0; @endphp
            @foreach($estoque as $e)
            @php $totalGeral += ($e->quantidade * $e->valor_compra); @endphp
            <tr>
                <td>{{ $e->codBarras }}</td>
                <td>{{ $e->produto_nome }}</td>
                <td class="text-right">{{ number_format($e->saldo_inicial ?? 0, 2, ',', '.') }}</td>
                <td class="text-right">--</td> <td class="text-right">--</td>
                <td class="text-right"><strong>{{ number_format($e->quantidade, 2, ',', '.') }}</strong></td>
                <td class="text-right">R$ {{ number_format($e->valor_compra, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($e->quantidade * $e->valor_compra, 2, ',', '.') }}</td>
                <td>[ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ]</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; text-align: right;">
        <strong>VALOR TOTAL DO STOCK EM CUSTO: R$ {{ number_format($totalGeral, 2, ',', '.') }}</strong>
    </div>
</body>
</html>