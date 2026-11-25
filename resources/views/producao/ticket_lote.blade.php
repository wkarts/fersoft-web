<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Produção #{{ $numeroTicket ?? '' }} - {{ $lote->codigo_lote }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            margin: 0;
            padding: 10px;
            color: #000;
        }
        .ticket {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
        th, td { border-bottom: 1px dashed #000; padding: 6px 0; }
        .divider { border-top: 1px dashed #000; margin: 12px 0; }

        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
<div class="ticket">
    <!-- CABEÇALHO -->
    <div class="text-center bold" style="font-size: 15px;">
        {{ $configNota->nome_fantasia ?? 'FERSOFT ERP' }}
    </div>
    <div class="text-center">
        CNPJ: {{ $configNota->cnpj ?? 'N/D' }}<br>
        TICKET DE PRODUÇÃO / CORTE @if(!empty($numeroTicket)) #{{ $numeroTicket }} @endif
    </div>

    <div class="divider"></div>

    <!-- DADOS DO LOTE -->
    <div><span class="bold">Lote:</span> {{ $lote->codigo_lote }}</div>
    <div><span class="bold">Data/Hora:</span> {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</div>
    <div><span class="bold">Origem:</span> {{ $lote->fornecedor->razao_social ?? 'INTERNO / AVULSO' }}</div>

    <div class="divider"></div>

    <!-- ITENS PROCESSADOS NESTE TICKET -->
    <div class="bold text-center">ITENS PROCESSADOS</div>

    <table>
        <thead>
        <tr>
            <th style="text-align: left;">Produto/Colab.</th>
            <th class="text-right">Pesos (kg)</th>
        </tr>
        </thead>
        <tbody>
        @forelse($itens as $item)
            <tr>
                <td>
                    {{ $item->produto->nome ?? 'Produto ID '.$item->produto_id }}<br>
                    <small>Colab: {{ $item->funcionario->nome ?? 'N/I' }}</small>
                </td>
                <td class="text-right" style="white-space: nowrap;">
                    B: {{ number_format($item->peso, 2, ',', '.') }}<br>
                    T: {{ number_format($item->peso_bag, 2, ',', '.') }}<br>
                    <span class="bold">L: {{ number_format($item->peso_liquido, 2, ',', '.') }}</span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="2" class="text-center">Nenhum item vinculado a este ticket.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="divider"></div>

    <!-- TOTAIS -->
    <table style="border: none;">
        <tr>
            <td style="border: none;" class="bold">TOTAL BRUTO:</td>
            <td style="border: none;" class="text-right">{{ number_format($totalPeso, 2, ',', '.') }} kg</td>
        </tr>
        <tr>
            <td style="border: none;" class="bold">TOTAL TARA:</td>
            <td style="border: none;" class="text-right">{{ number_format($totalTara, 2, ',', '.') }} kg</td>
        </tr>
        <tr>
            <td style="border: none; font-size: 15px;" class="bold">TOTAL LÍQUIDO:</td>
            <td style="border: none; font-size: 15px;" class="text-right bold">{{ number_format($totalLiquido, 2, ',', '.') }} kg</td>
        </tr>
    </table>

    <div class="divider"></div>
    <div class="text-center" style="margin-top: 30px;">
        _________________________________<br>
        Assinatura do Operador/Gestor
    </div>

    <div class="text-center no-print" style="margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #3699ff; color: #fff; border: none; border-radius: 4px;">
            🖨️ Imprimir Ticket
        </button>
        <br><br>
        <button onclick="window.close()" style="padding: 8px 15px; font-size: 14px; cursor: pointer; background: #e4e6ef; border: none; border-radius: 4px;">
            Fechar Janela
        </button>
    </div>
</div>

<script>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    }
</script>
</body>
</html>
