<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo - OS #{{ $os->id }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #333;
            background-color: #f9f9f9; /* Fundo cinza na tela */
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px; /* Limita a largura para ficar parecido com uma folha A4 */
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .header { text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 20px; margin-bottom: 30px; }
        .header h2 { margin: 0 0 5px 0; color: #000; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 2px 0; color: #555; }

        .box { border: 1px solid #eee; padding: 20px; margin-bottom: 20px; border-radius: 4px; background-color: #fafafa; }
        .box-title { font-weight: bold; text-transform: uppercase; margin-top: 0; color: #444; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 15px;}

        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        td { padding: 8px 0; border-bottom: 1px solid #eee; }
        .td-value { text-align: right; font-weight: bold; }

        .total-box {
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            padding: 15px;
            text-align: right;
            font-size: 22px;
            font-weight: bold;
            color: #2e7d32;
            border-radius: 4px;
        }
        .assinatura { margin-top: 80px; text-align: center; }
        .linha { border-top: 1px solid #000; width: 300px; margin: 0 auto 10px auto; }

        /* Esconde o fundo cinza e sombras na hora que for pra impressora */
        @media print {
            body { background-color: #fff; padding: 0; }
            .container { box-shadow: none; border: none; padding: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>{{ $config->nome ?? 'NOME DA ÓTICA' }}</h2>
        <p>CNPJ: {{ $config->cnpj ?? '___.___.___-__' }} | Tel: {{ $config->telefone ?? '' }}</p>
        <h3 style="margin-top: 20px; background: #333; color: #fff; display: inline-block; padding: 5px 15px; border-radius: 20px;">
            COMPROVANTE DE COMPRA - OS #{{ str_pad($os->id, 5, '0', STR_PAD_LEFT) }}
        </h3>
    </div>

    <div class="box">
        <h4 class="box-title">Dados do Cliente</h4>
        <table style="border: none;">
            <tr>
                <td style="border: none;"><strong>Nome:</strong> {{ $os->cliente->razao_social ?? 'Não informado' }}</td>
                <td style="border: none; text-align: right;"><strong>CPF/CNPJ:</strong> {{ $os->cliente->cpf_cnpj ?? 'Não informado' }}</td>
            </tr>
            <tr>
                <td style="border: none;"><strong>Data do Pedido:</strong> {{ \Carbon\Carbon::parse($os->data)->format('d/m/Y') }}</td>
                <td style="border: none; text-align: right;"><strong>Previsão de Retirada:</strong> {{ \Carbon\Carbon::parse($os->data_entrega)->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h4 class="box-title">Detalhamento dos Produtos</h4>
        <table>
            @if($os->armacao)
                <tr>
                    <td>{{ $os->armacao }} (Qtd: {{ $os->qtd_armacao ?? 1 }})</td>
                    <td class="td-value">R$ {{ number_format($os->valor_armacao ?? 0, 2, ',', '.') }}</td>
                </tr>
            @endif

            @if($os->lente)
                <tr>
                    <td>
                        {{ $os->lente }} (Qtd: {{ $os->qtd_lente ?? 1 }})<br>
                        <small style="color: #777;">Tipo: {{ $os->tipo_lente ?? 'N/A' }} | Tratamento: {{ $os->tratamento ?? 'N/A' }}</small>
                    </td>
                    <td class="td-value">R$ {{ number_format($os->valor_lente ?? 0, 2, ',', '.') }}</td>
                </tr>
            @endif
        </table>

        <p style="margin-top: 15px;"><strong>Forma de Pagamento:</strong> {{ $os->forma_pagamento ?? 'A Combinar / Balcão' }}</p>
    </div>

    <div class="total-box">
        TOTAL GERAL: R$ {{ number_format(($os->valor_armacao ?? 0) + ($os->valor_lente ?? 0), 2, ',', '.') }}
    </div>

    <div class="assinatura">
        <div class="linha"></div>
        Assinatura do Cliente
        <p style="font-size: 11px; color: #888; margin-top: 5px;">Declaro ter conferido e estar ciente com as informações acima.</p>
    </div>
</div>

<script>
    window.onload = function() {
        setTimeout(function() { window.print(); }, 500);
    };
</script>
</body>
</html>
