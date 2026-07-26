<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Estoque Físico</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        h2 { text-align: center; margin-bottom: 5px; font-size: 18px; }
        .periodo { text-align: center; margin-bottom: 20px; font-size: 14px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-danger { color: #d9534f; }
        .text-success { color: #5cb85c; }
        .bg-dark { background-color: #333; color: white; font-weight: bold; }
    </style>
</head>
<body>

    <h2>Relatório Analítico - Estoque Físico</h2>
    <div class="periodo">Período: {{ date('d/m/Y', strtotime($dataInicial)) }} a {{ date('d/m/Y', strtotime($dataFinal)) }}</div>

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Ticket</th>
                <th>Tipo</th>
                <th>Parceiro (Fornecedor/Cliente)</th>
                <th>Produto</th>
                <th class="text-end">Peso Bruto (KG)</th>
                <th class="text-end">Impureza (KG)</th>
                <th class="text-end">Peso Líq. (KG)</th>
                <th class="text-end">R$/KG</th>
                <th class="text-end">Total (R$)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($analitico as $linha)
                <tr>
                    <td class="text-center">{{ date('d/m/Y', strtotime($linha->data_movimento)) }}</td>
                    <td class="text-center">#{{ $linha->ticket_id ?? $linha->pesagem_id }}</td>
                    <td class="text-center">{{ ucfirst($linha->tipo) }}</td>
                    <td>{{ $linha->fornecedor_nome ?? $linha->cliente_nome ?? 'Movimento Manual' }}</td>
                    <td>{{ $linha->produto_nome }}</td>
                    <td class="text-end">{{ number_format($linha->peso_bruto, 2, ',', '.') }}</td>
                    <td class="text-end text-danger">{{ number_format($linha->peso_impureza, 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($linha->quantidade, 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($linha->valor_unitario, 4, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($linha->valor_total, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-dark">
                <td colspan="5" class="text-end"><strong>TOTAIS GERAIS:</strong></td>
                <td class="text-end"><strong>{{ number_format($analitico->sum('peso_bruto'), 2, ',', '.') }}</strong></td>
                <td class="text-end"><strong>{{ number_format($analitico->sum('peso_impureza'), 2, ',', '.') }}</strong></td>
                <td class="text-end"><strong>{{ number_format($analitico->sum('quantidade'), 2, ',', '.') }}</strong></td>
                <td></td>
                <td class="text-end"><strong>{{ number_format($analitico->sum('valor_total'), 2, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>

</body>
</html>