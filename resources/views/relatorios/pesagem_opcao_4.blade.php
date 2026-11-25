<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Pesagem - #{{ $pesagem->id }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 10mm 15mm 10mm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            line-height: 1.3;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo-img {
            max-width: 110px;
            max-height: 50px;
            object-fit: contain;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .company-details {
            font-size: 9px;
            color: #475569;
            margin-top: 2px;
        }
        .report-title {
            font-size: 11px;
            font-weight: bold;
            color: #2563eb;
            margin-top: 4px;
            text-transform: uppercase;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 8px;
            margin-bottom: 10px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 2px 0;
            font-size: 9.5px;
            vertical-align: top;
        }
        .info-table td strong {
            color: #0f172a;
        }
        .produto-bloco {
            margin-bottom: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .produto-titulo {
            background-color: #0f172a;
            color: #ffffff;
            padding: 5px 8px;
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            border: 1px solid #e2e8f0;
            padding: 5px 4px;
            text-align: center;
            font-size: 9px;
        }
        .table th {
            background-color: #f1f5f9;
            color: #475569;
            text-transform: uppercase;
            font-weight: bold;
        }
        /* Estilos para as imagens dos tickets no relatório */
        .evid-container {
            padding: 6px 8px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        .evid-title {
            font-size: 9px;
            font-weight: bold;
            color: #475569;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .ticket-imagens {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .ticket-imagem-item {
            border: 1px solid #cbd5e1;
            padding: 3px;
            border-radius: 4px;
            background: #fff;
            display: inline-block;
        }
        .ticket-imagem-item img {
            max-width: 140px;
            max-height: 95px;
            object-fit: contain;
            display: block;
        }
        .ticket-imagem-caption {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
            text-align: center;
        }
        .totais-gerais {
            margin-top: 10px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px 10px;
            float: right;
            width: 260px;
        }
        .footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            height: 10mm;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
            text-align: center;
            font-size: 8px;
            color: #64748b;
        }
    </style>
</head>
<body>

@php
    $logoBase64 = null;
    $logoNome = $emitente->logo ?? null;
    if ($logoNome) {
        $logoPath = public_path('logos/' . $logoNome);
        if (is_file($logoPath)) {
            $logoConteudo = @file_get_contents($logoPath);
            if ($logoConteudo !== false) {
                $logoBase64 = 'data:image/png;base64,' . base64_encode($logoConteudo);
            }
        }
    }

    $razaoSocial = $emitente->razao_social ?? $emitente->nome ?? 'EMITENTE';
    $cnpj = $emitente->cnpj ?? '';
    $ie = $emitente->ie ?? '';
    $fone = $emitente->fone ?? $emitente->telefone ?? '';
    $rua = $emitente->rua ?? $emitente->endereco ?? '';
    $numero = $emitente->numero ?? '';
    $bairro = $emitente->bairro ?? '';
    $cidade = $emitente->cidade ?? $emitente->municipio ?? '';
    $uf = $emitente->uf ?? $emitente->UF ?? '';

    // Data do registro + hora da primeira pesagem (ticket)
    $dataRegistroFormatada = !empty($pesagem->dt_registro)
        ? \Carbon\Carbon::parse($pesagem->dt_registro)->format('d/m/Y')
        : optional($pesagem->created_at)->format('d/m/Y');

    $primeiroTicketGeral = $pesagem->tickets
        ? $pesagem->tickets->sortBy(function ($t) {
            return $t->inicio ?? $t->created_at ?? $t->id;
        })->first()
        : null;

    $horaPrimeiraPesagem = null;
    if ($primeiroTicketGeral && (!empty($primeiroTicketGeral->inicio) || !empty($primeiroTicketGeral->created_at))) {
        $horaPrimeiraPesagem = \Carbon\Carbon::parse($primeiroTicketGeral->inicio ?? $primeiroTicketGeral->created_at)->format('H:i');
    } elseif (!empty($pesagem->created_at)) {
        $horaPrimeiraPesagem = \Carbon\Carbon::parse($pesagem->created_at)->format('H:i');
    }

    // Nome do balanceiro (usuário que fez a pesagem)
    $usuarioBalanceiro = $pesagem->usuario
        ?? (!empty($pesagem->usuario_id) ? \App\Models\Usuario::find($pesagem->usuario_id) : null);
    $nomeBalanceiro = $usuarioBalanceiro->nome ?? $usuarioBalanceiro->name ?? 'N/A';

    // Data e hora da impressão
    $dataHoraImpressao = now()->format('d/m/Y H:i');
@endphp

<div class="header">
    <table class="header-table">
        <tr>
            @if($logoBase64)
                <td width="15%" style="text-align: left;">
                    <img src="{{ $logoBase64 }}" class="logo-img" alt="Logo">
                </td>
                <td width="85%" style="text-align: center;">
            @else
                <td width="100%" style="text-align: center;">
                    @endif
                    <div class="company-name">{{ $razaoSocial }}</div>
                    <div class="company-details">
                        CNPJ: {{ $cnpj }} @if($ie) | IE: {{ $ie }} @endif @if($fone) | Tel: {{ $fone }} @endif<br>
                        @if($rua || $bairro || $cidade)
                            {{ $rua }}{{ $numero ? ', ' . $numero : '' }}{{ $bairro ? ' - ' . $bairro : '' }}{{ $cidade ? ' - ' . $cidade : '' }}{{ $uf ? '/' . $uf : '' }}
                        @endif
                    </div>
                    <div class="report-title">Relatório de Pesagem por Item #{{ $pesagem->id }}</div>
                </td>
        </tr>
    </table>
</div>

<div class="info-box">
    <table class="info-table">
        <tr>
            <td width="33%"><strong>Operação:</strong> {{ ucfirst($pesagem->tipo) }}</td>
            <td width="33%"><strong>Status:</strong> {{ ucfirst($pesagem->status) }}</td>
            <td width="34%"><strong>Data Registro / Hora:</strong> {{ $dataRegistroFormatada ?? 'N/A' }}{{ $horaPrimeiraPesagem ? ' ' . $horaPrimeiraPesagem : '' }}</td>
        </tr>
        <tr>
            <td><strong>Cliente / Fornecedor:</strong> {{ $pesagem->cliente->razao_social ?? $pesagem->fornecedor->razao_social ?? 'N/A' }}</td>
            <td><strong>Veículo (Placa):</strong> {{ $pesagem->veiculo->placa ?? $pesagem->placa_veiculo ?? 'N/A' }}{{ $pesagem->placa_carreta ? ' / ' . $pesagem->placa_carreta : '' }}</td>
            <td><strong>Motorista:</strong> {{ $pesagem->motorista->nome ?? $pesagem->motorista_nome ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Balanceiro:</strong> {{ $nomeBalanceiro }}</td>
            <td><strong>Data / Hora Impressão:</strong> {{ $dataHoraImpressao }}</td>
        </tr>
    </table>
</div>

@foreach($resumo['produtos'] as $grupo)
    @php
        $produtoNome = $grupo['produto']->nome ?? 'ITEM / PRODUTO DIVERSO';
        $ticketsGrupo = collect($grupo['tickets'] ?? []);

        $ticketEntrada = $ticketsGrupo->firstWhere('tipo', 'entrada');
        $ticketSaida = $ticketsGrupo->firstWhere('tipo', 'saida');
        $primeiroTicket = $ticketsGrupo->first();

        $pesoBrutoItem = $ticketEntrada ? (float)$ticketEntrada->peso : ($grupo['entrada'] > 0 ? $grupo['entrada'] : $grupo['peso_liquido']);
        $pesoTaraVeiculo = $ticketSaida ? (float)$ticketSaida->peso : 0;
        $pesoBag = $grupo['peso_bag'] ?? 0;

        $liquidoItem = $grupo['peso_liquido'];

        $vlrUnitario = $ticketEntrada->valor_unitario ?? $primeiroTicket->valor_unitario ?? 0;
        $precoTotalItem = $liquidoItem * $vlrUnitario;
    @endphp

    <div class="produto-bloco">
        <div class="produto-titulo">{{ strtoupper($produtoNome) }}</div>
        <table class="table">
            <thead>
            <tr>
                <th>Peso Cheio (Entrada)</th>
                <th>Veículo Vazio (Tara)</th>
                <th>Peso Recipiente (Bag)</th>
                <th>Vlr Líquido</th>
                <th>Preço Unitário</th>
                <th>Preço Total R$</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{{ number_format($pesoBrutoItem, 2, ',', '.') }} kg</td>
                <td>{{ $pesoTaraVeiculo > 0 ? number_format($pesoTaraVeiculo, 2, ',', '.') . ' kg' : '—' }}</td>
                <td>{{ number_format($pesoBag, 2, ',', '.') }} kg</td>
                <td><strong>{{ number_format($liquidoItem, 2, ',', '.') }} kg</strong></td>
                <td>R$ {{ number_format($vlrUnitario, 2, ',', '.') }}</td>
                <td><strong style="color: #16a34a;">R$ {{ number_format($precoTotalItem, 2, ',', '.') }}</strong></td>
            </tr>
            </tbody>
        </table>

        <!-- Seção para renderizar as fotos/evidências dos tickets vinculados a este produto -->
        @foreach($ticketsGrupo as $ticket)
            @if(method_exists($ticket, 'imagens') || !empty($ticket->imagens_persistidas_json) || !empty($ticket->balanca_evidence_json))
                <div class="evid-container">
                    <div class="evid-title">Evidências Fotográficas — Ticket #{{ $ticket->id }} ({{ ucfirst($ticket->tipo) }})</div>
                    @include('relatorios.partials.ticket-imagens', ['ticket' => $ticket, 'tipoRelatorioImagem' => 'a4'])
                </div>
            @endif
        @endforeach
    </div>
@endforeach

<div class="totais-gerais">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 2px 0; font-size: 9.5px;"><strong>Peso Líquido Total:</strong></td>
            <td style="padding: 2px 0; font-size: 9.5px; text-align: right;">{{ number_format($resumo['peso_liquido_total'], 2, ',', '.') }} kg</td>
        </tr>
        <tr>
            <td style="padding: 5px 0 2px 0; font-size: 10.5px; border-top: 1px solid #cbd5e1; color: #16a34a;"><strong>Valor Total da Operação:</strong></td>
            <td style="padding: 5px 0 2px 0; font-size: 11.5px; text-align: right; border-top: 1px solid #cbd5e1; color: #16a34a;"><strong>R$ {{ number_format($resumo['valor_total_operacao'], 2, ',', '.') }}</strong></td>
        </tr>
    </table>
</div>

<div class="footer">
    FerSoft ERP &bull; Balanceiro: {{ $nomeBalanceiro }} &bull; Impresso em {{ $dataHoraImpressao }}
</div>

</body>
</html>
