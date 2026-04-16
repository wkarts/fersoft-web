<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>OS #{{ $manutencao->manutencao_id }} - {{ $manutencao->veiculo->placa }}</title>
    <style>
        /* CONFIGURAÇÃO PARA A4 E PDF */
        @page { size: A4; margin: 15mm; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; margin: 0; color: #333; background-color: #fff; }
        
        .header-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 20px; }
        .empresa-dados { line-height: 1.4; }
        .razao-social { font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .os-badge { background: #000; color: #fff; padding: 10px; text-align: center; border-radius: 5px; }
        
        .section-title { background: #f2f2f2; padding: 7px; font-weight: bold; text-transform: uppercase; font-size: 12px; border: 1px solid #ccc; margin-top: 15px; }
        .info-table { width: 100%; margin-top: 5px; border: 1px solid #ccc; }
        .info-table td { padding: 8px; font-size: 12px; border: 1px solid #eee; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th { background: #333; color: #fff; padding: 8px; font-size: 11px; text-align: left; }
        .items-table td { padding: 8px; font-size: 11px; border-bottom: 1px solid #ddd; }
        
        .total-container { margin-top: 20px; text-align: right; }
        .total-box { display: inline-block; border: 2px solid #000; padding: 10px; min-width: 200px; }
        
        .assinaturas { margin-top: 50px; width: 100%; }
        .assinatura-box { border-top: 1px solid #000; width: 45%; text-align: center; padding-top: 5px; font-size: 11px; }

        /* Estilo para impressão */
        @media print {
            .no-print { display: none; }
            body { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <table class="header-table">
    <tr>
        <td width="25%">
            @if($config->logo)
                {{-- Caminho direto para public/logos --}}
                <img src="{{ asset('logos/' . $config->logo) }}" alt="Logo" style="max-width: 150px; max-height: 90px; object-fit: contain;">
            @endif
        </td>
        <td width="50%" class="empresa-dados">
            <div class="razao-social">{{ $config->razao_social }}</div>
            <div style="font-size: 11px;">
                <strong>CNPJ:</strong> {{ $config->cnpj }}<br>
                {{ $config->logradouro }}, {{ $config->numero }} {{ $config->complemento }}<br>
                {{ $config->bairro }} - {{ $config->municipio }} / {{ $config->uf }}
            </div>
        </td>
        <td width="25%" align="right">
            <div class="os-badge">
                ORDEM DE SERVIÇO<br>
                <span style="font-size: 22px;">#{{ $manutencao->manutencao_id }}</span>
            </div>
        </td>
    </tr>
</table>

    <div class="section-title">Informações do Veículo e Serviço</div>
    <table class="info-table" cellspacing="0">
        <tr>
            <td><strong>VEÍCULO:</strong> {{ $manutencao->veiculo->placa }} - {{ $manutencao->veiculo->marca }}</td>
            <td><strong>KM REGISTRO:</strong> {{ number_format($manutencao->km_registro, 0, ',', '.') }} KM</td>
            <td><strong>DATA:</strong> {{ \Carbon\Carbon::parse($manutencao->data_manutencao)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><strong>MECÂNICO:</strong> {{ $manutencao->responsavel->nome ?? 'N/A' }}</td>
            <td><strong>STATUS:</strong> {{ $manutencao->status }}</td>
            <td><strong>PRIORIDADE:</strong> {{ $manutencao->prioridade }}</td>
        </tr>
    </table>

    <div class="section-title">Descrição / Problema Relatado</div>
    <div style="border: 1px solid #ccc; padding: 10px; font-size: 12px; min-height: 40px;">
        {{ $manutencao->descricao ?? 'Sem descrição informada.' }}
    </div>

    @if($manutencao->checklist)
    <div class="section-title">Checklist do Veículo</div>
    <div style="border: 1px solid #ccc; padding: 10px; font-size: 12px;">
        @if(is_array($manutencao->checklist))
            • {{ implode(' &nbsp; • ', $manutencao->checklist) }}
        @else
            {{ $manutencao->checklist }}
        @endif
    </div>
    @endif

    <div class="section-title">Peças, Materiais e Mão de Obra</div>
    <table class="items-table">
        <thead>
            <tr>
                <th>DESCRIÇÃO</th>
                <th width="80" style="text-align: center;">QTD</th>
                <th width="100" style="text-align: right;">VALOR UNIT.</th>
                <th width="100" style="text-align: right;">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($manutencao->itens as $item)
            <tr>
                <td>{{ $item->produto->nome ?? $item->descricao }}</td>
                <td align="center">{{ number_format($item->quantity ?? $item->quantidade, 2, ',', '.') }}</td>
                <td align="right">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                <td align="right">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-container">
        <div class="total-box">
            <span style="font-size: 12px;">VALOR TOTAL DA OS</span><br>
            <span style="font-size: 24px; font-weight: bold;">R$ {{ number_format($manutencao->custo, 2, ',', '.') }}</span>
        </div>
    </div>

    <table class="assinaturas" cellspacing="0">
        <tr>
            <td class="assinatura-box">Assinatura do Responsável</td>
            <td width="10%"></td>
            <td class="assinatura-box">Assinatura do Motorista/Cliente</td>
        </tr>
    </table>

    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #777;">
        Relatório gerado em {{ date('d/m/Y H:i:s') }}
    </div>

    <div class="no-print" style="position: fixed; top: 20px; right: 20px;">
        <button onclick="window.print();" style="padding: 10px 20px; cursor: pointer; background: #27ae60; color: #fff; border: none; border-radius: 5px;">Imprimir OS / Salvar PDF</button>
    </div>

</body>
</html>