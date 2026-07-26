<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Conferência</title>
    <style>
        /* Força a impressão em Paisagem e remove margens chatas do navegador */
        @page { size: landscape; margin: 10mm; }
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 0; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .resumo { display: flex; justify-content: space-between; margin-bottom: 15px; font-weight: bold; }
        .badge { padding: 3px 6px; border-radius: 4px; font-size: 10px; }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h2>Relatório de Conferência Fiscal e Financeira</h2>
        <p>Período: {{ date('d/m/Y', strtotime(request('data_inicial', date('Y-m-01')))) }} a {{ date('d/m/Y', strtotime(request('data_final', date('Y-m-t')))) }}</p>
    </div>

    <div class="resumo">
        <span>Qtd. Notas: {{ $qtdNotas }}</span>
        <span>Total NF-e: R$ {{ number_format($totalNFe, 2, ',', '.') }}</span>
        <span>Total NFC-e: R$ {{ number_format($totalNFCe, 2, ',', '.') }}</span>
        <span>Total CT-e: R$ {{ number_format($totalCTe, 2, ',', '.') }}</span>
        <span>Total Geral: R$ {{ number_format($totalValor, 2, ',', '.') }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 70px;">Data</th>
                <th class="text-center" style="width: 80px;">Tipo</th>
                <th class="text-right" style="width: 80px;">Número</th>
                <th>Cliente / Fornecedor</th>
                <th class="text-right" style="width: 100px;">Valor Nota</th>
                <th class="text-right" style="width: 100px;">Recebido</th>
                <th class="text-right" style="width: 100px;">Em Aberto</th>
                <th class="text-center" style="width: 90px;">Situação</th>
                <th class="text-center" style="width: 90px;">Financeiro</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notas as $n)
            <tr>
                <td>{{ \Carbon\Carbon::parse($n['data'])->format('d/m/Y') }}</td>
                <td class="text-center">{{ $n['tipo'] }}</td>
                <td class="text-right">{{ $n['numero'] }}</td>
                <td>{{ $n['cliente'] }}</td>
                <td class="text-right">R$ {{ number_format($n['valor'], 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($n['valor_recebido'], 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($n['valor_aberto'], 2, ',', '.') }}</td>
                <td class="text-center">{{ $n['situacao'] }}</td>
                <td class="text-center">{{ $n['integrado'] ? 'Integrado' : ($n['bloqueia_integracao'] ? 'N/A' : 'Pendente') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
