<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ordem de Saída - #{{ $data->id }}</title>
    <style>
        /* Configurações de Fonte e Layout para Impressão */
        body { 
            font-family: 'Courier New', Courier, monospace; 
            padding: 10px; 
            font-size: 12px; 
            color: #000; 
            line-height: 1.4;
        }
        .ticket { 
            border: 1px solid #000; 
            padding: 15px; 
            max-width: 850px; 
            margin: 0 auto; 
        }
        
        /* Cabeçalho */
        .header { 
            text-align: center; 
            border-bottom: 2px solid #000; 
            padding-bottom: 10px; 
            margin-bottom: 15px; 
        }
        .header h2 { margin: 0; font-size: 18px; text-transform: uppercase; }
        
        /* Estrutura de Colunas */
        .row { display: flex; flex-wrap: wrap; margin-bottom: 5px; }
        .col { flex: 1; min-width: 250px; }
        
        .label { font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .value { border-bottom: 1px solid #ccc; display: inline-block; min-width: 150px; padding-left: 5px; }
        
        /* Seções e Tabelas */
        .section-title { 
            font-weight: bold; 
            background: #f2f2f2; 
            padding: 4px; 
            text-align: center; 
            margin: 15px 0 8px 0; 
            border: 1px solid #000; 
            font-size: 11px;
        }

        .table-print { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 10px; 
        }
        .table-print th, .table-print td { 
            border: 1px solid #000; 
            padding: 4px; 
            text-align: center; 
        }
        .table-print th { background: #f9f9f9; font-size: 10px; }

        /* Rodapé e Assinaturas */
        .divider { border-top: 1px dashed #000; margin: 20px 0; }
        .signatures { 
            display: flex; 
            justify-content: space-between; 
            margin-top: 40px; 
            text-align: center; 
        }
        .signature-box { 
            width: 45%; 
            border-top: 1px solid #000; 
            padding-top: 5px; 
            font-size: 10px; 
        }

        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .ticket { border: 1px solid #000; }
        }
    </style>
</head>
<body>

<div class="no-print" style="text-align: center; margin-bottom: 20px;">
    <button onclick="window.print()" style="padding: 10px 25px; font-weight: bold; cursor: pointer;">
        🖨️ IMPRIMIR ORDEM DE VIAGEM
    </button>
</div>

<div class="ticket">
    <div class="header">
        <h2>Ordem de Saída de Veículo</h2>
        <small>Identificador: #{{ $data->id }} | Emissão: {{ date('d/m/Y H:i') }}</small>
    </div>

    <div class="section-title">1. INFORMAÇÕES DE SAÍDA</div>
    
    <div class="row">
        <div class="col"><span class="label">Veículo:</span> <span class="value">{{ $data->veiculo->placa }} - {{ $data->veiculo->modelo }}</span></div>
        <div class="col"><span class="label">Motorista:</span> <span class="value">{{ $data->motorista->nome }}</span></div>
    </div>

    <div class="row">
        <div class="col"><span class="label">Ajudante:</span> <span class="value">{{ $data->ajudante->nome ?? 'Nenhum' }}</span></div>
        <div class="col"><span class="label">Destino:</span> <span class="value">{{ $data->destino ?? '_______________________' }}</span></div>
    </div>

    <div class="row">
        <div class="col"><span class="label">Data/Hora Saída:</span> <span class="value">{{ \Carbon\Carbon::parse($data->data_hora_saida)->format('d/m/Y H:i') }}</span></div>
        <div class="col"><span class="label">KM Inicial:</span> <span class="value">{{ number_format($data->km_inicial, 0, ',', '.') }} KM</span></div>
    </div>

    <div class="section-title">2. ABASTECIMENTOS REGISTRADOS NO SISTEMA</div>
    <table class="table-print">
        <thead>
            <tr>
                <th>PRODUTO</th>
                <th>TIPO</th>
                <th>DATA</th>
                <th>KM REGISTRO</th>
                <th>LITROS</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data->abastecimentos as $a)
            <tr>
                <td>{{ $a->produto->nome }}</td>
                <td>{{ strtoupper($a->tipo) }}</td>
                <td>{{ \Carbon\Carbon::parse($a->data_abastecimento)->format('d/m/Y') }}</td>
                <td>{{ number_format($a->km_abastecimento, 0, ',', '.') }}</td>
                <td>{{ number_format($a->quantidade, 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5">Nenhum abastecimento prévio registrado.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="section-title">3. PREENCHIMENTO DE RETORNO (MOTORISTA)</div>
    
    <div class="row">
        <div class="col"><span class="label">Data de Chegada:</span> <span class="value">____/____/______</span></div>
        <div class="col"><span class="label">Hora de Chegada:</span> <span class="value">____:____</span></div>
    </div>

    <div class="row">
        <div class="col"><span class="label">KM Final:</span> <span class="value">_______________________</span></div>
        <div class="col"><span class="label">Abasteceu na viagem?</span> <span class="value">( ) Sim &nbsp; ( ) Não</span></div>
    </div>

    <div class="section-title">4. DESPESAS DE VIAGEM (PEDÁGIOS, REFEIÇÃO, ETC.)</div>
    <table class="table-print">
        <thead>
            <tr>
                <th width="45%">DESCRIÇÃO DA DESPESA</th>
                <th width="25%">VALOR (R$)</th>
                <th width="30%">CUPOM/NF</th>
            </tr>
        </thead>
        <tbody>
            <tr><td style="height: 22px;"></td><td></td><td></td></tr>
            <tr><td style="height: 22px;"></td><td></td><td></td></tr>
            <tr><td style="height: 22px;"></td><td></td><td></td></tr>
            <tr><td style="height: 22px;"></td><td></td><td></td></tr>
        </tbody>
    </table>

    <div class="row" style="margin-top: 10px;">
        <div class="col"><span class="label">Observações / Avarias / Ocorrências:</span></div>
    </div>
    <div style="height: 50px; border: 1px solid #000; margin-top: 5px; padding: 5px;"></div>

    <div class="signatures">
        <div class="signature-box">
            ASSINATURA DO MOTORISTA
        </div>
        <div class="signature-box">
            VISTO DO GESTOR DE FROTA
        </div>
    </div>
</div>

</body>
</html>