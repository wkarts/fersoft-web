<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Medição #{{ $fatura->id }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 11px;
            color: #7f8c8d;
        }
        .badge {
            background-color: #3498db;
            color: white;
            padding: 5px 10px;
            font-weight: bold;
            border-radius: 4px;
            text-align: right;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 1px solid #bdc3c7;
            padding-bottom: 4px;
            margin-top: 20px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th {
            background-color: #2c3e50;
            color: white;
            text-align: left;
            padding: 8px;
            font-size: 11px;
            text-transform: uppercase;
        }
        table.data-table td {
            border: 1px solid #dfe6e9;
            padding: 8px;
            font-size: 11px;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f1f2f6;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-box {
            width: 300px;
            float: right;
            background-color: #f8f9fa;
            border: 1px solid #ced6e0;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
        .totals-box table {
            width: 100%;
        }
        .totals-box td {
            padding: 4px;
            font-size: 12px;
        }
        .total-geral {
            font-weight: bold;
            color: #2c3e50;
            font-size: 14px;
            border-top: 1px solid #b2bec3;
        }
        .clear {
            clear: both;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #b2bec3;
            border-top: 1px solid #dfe6e9;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="title">Medição e Faturamento</div>
                    <div class="subtitle">Controle de Execução de Serviços e Locações</div>
                </td>
                <td style="text-align: right;">
                    <span style="font-size: 14px; font-weight: bold; color: #3498db;">#{{ $fatura->id }}</span><br>
                    <span class="subtitle">Data: {{ date('d/m/Y', strtotime($fatura->data_faturamento ?? $fatura->created_at)) }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-box">
        <table>
            <tr>
                <td style="width: 60%;">
                    <strong>Cliente:</strong> {{ $fatura->cliente->razao_social ?? $fatura->cliente->nome ?? 'N/D' }}<br>
                    <strong>CNPJ / CPF:</strong> {{ $fatura->cliente->cnpj ?? $fatura->cliente->cpf ?? 'N/D' }}
                </td>
                <td style="width: 40%;">
                    <strong>Contrato Vinculado:</strong> {{ $fatura->contrato ? 'Contrato #' . ($fatura->contrato->numero_contrato ?? $fatura->contrato->id) : 'Lançamento Avulso' }}<br>
                    <strong>Status:</strong> {{ $fatura->status }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Itens da Medição -->
    @if($fatura->contrato && $fatura->contrato->itens->count() > 0)
    <div class="section-title">1. Itens da Medição (Serviços e Locações)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Tipo</th>
                <th style="width: 45%;">Item / Descrição</th>
                <th style="width: 15%;" class="text-center">Quantidade</th>
                <th style="width: 12%;" class="text-right">Val. Unit.</th>
                <th style="width: 13%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fatura->contrato->itens as $item)
            <tr>
                <td>{{ $item->tipo_item }}</td>
                <td>{{ $item->servico->nome ?? $item->produto->nome ?? 'N/D' }}</td>
                <td class="text-center">{{ number_format($item->quantidade ?? $item->quantidade_prevista, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($item->valor_total, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Equipe Envolvida -->
    @if($fatura->funcionarios->count() > 0)
    <div class="section-title">2. Equipe / Funcionários Envolvidos</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40%;">Funcionário</th>
                <th style="width: 30%;">Função</th>
                <th style="width: 10%;" class="text-center">Qtd / Diárias</th>
                <th style="width: 10%;" class="text-right">Val. Diária</th>
                <th style="width: 10%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fatura->funcionarios as $f)
            <tr>
                <td>{{ $f->funcionario->nome ?? 'N/D' }}</td>
                <td>{{ $f->funcao }}</td>
                <td class="text-center">{{ number_format($f->diarias, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($f->valor_diaria, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($f->valor_total, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($fatura->observacao)
    <div class="section-title">Observações</div>
    <div class="info-box" style="font-size: 11px; color: #555;">
        {{ $fatura->observacao }}
    </div>
    @endif

    <!-- Totais -->
    <div class="totals-box">
        <table>
            <tr>
                <td>Valor Bruto:</td>
                <td class="text-right">R$ {{ number_format($fatura->valor_total, 2, ',', '.') }}</td>
            </tr>
            @if($fatura->valor_retencao > 0)
            <tr>
                <td>Retenções:</td>
                <td class="text-right">R$ {{ number_format($fatura->valor_retencao, 2, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-geral">
                <td>Valor Líquido:</td>
                <td class="text-right">R$ {{ number_format($fatura->valor_liquido ?? $fatura->valor_total, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>
    <div class="clear"></div>

    <div class="footer">
        Gerado por Fersoft ERP - Sistema de Gestão Empresarial &bull; Página 1 de 1
    </div>

</body>
</html>