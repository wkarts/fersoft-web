<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $title ?? 'Relatório de Pesagem' }}</title>
    <style>
        @page { margin: 7mm 8mm 12mm 8mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; font-size: 7.7px; line-height: 1.12; color: #111827; }
        table { width: 100%; border-collapse: collapse; }
        .rp-page { page-break-after: always; }
        .rp-page:last-child { page-break-after: auto; }
        .rp-header { table-layout: fixed; border-bottom: 1.5px solid #2563eb; margin-bottom: 5px; }
        .rp-header td { vertical-align: middle; padding-bottom: 3px; }
        .rp-logo-cell { width: 21%; text-align: left; }
        .rp-title-cell { width: 56%; text-align: center; }
        .rp-qr-cell { width: 23%; text-align: right; }
        .rp-logo { max-width: 115px; max-height: 42px; object-fit: contain; }
        .rp-brand-fallback { font-size: 10px; font-weight: 700; line-height: 1.1; color: #374151; overflow-wrap: anywhere; }
        .rp-qr { width: 50px; height: 50px; object-fit: contain; border: 1px solid #d1d5db; padding: 2px; }
        .rp-title { font-size: 16px; font-weight: 700; color: #1f2937; margin: 0 0 1px; }
        .rp-subtitle { font-size: 7.8px; color: #4b5563; margin: 0; overflow-wrap: anywhere; }
        .rp-token { margin-top: 2px; font-size: 6.3px; color: #4b5563; word-break: break-all; }
        .rp-section { margin-top: 4px; }
        .rp-section-title { font-size: 9.2px; font-weight: 700; color: #1d4ed8; border-bottom: 1px solid #bfdbfe; padding-bottom: 1px; margin-bottom: 2px; }
        .rp-info { table-layout: fixed; border: 1px solid #d1d5db; }
        .rp-info td { padding: 3px 4px; border-right: 1px solid #e5e7eb; vertical-align: top; overflow-wrap: anywhere; }
        .rp-info td:last-child { border-right: 0; }
        .rp-label { display: block; font-size: 5.9px; color: #6b7280; text-transform: uppercase; margin-bottom: 1px; }
        .rp-value { font-size: 7.8px; font-weight: 700; }
        .rp-summary { table-layout: fixed; margin-top: 4px; }
        .rp-summary td { border: 1px solid #dbeafe; background: #eff6ff; text-align: center; padding: 2px 2px; }
        .rp-summary .num { font-size: 9.6px; font-weight: 700; color: #1d4ed8; white-space: nowrap; }
        .rp-summary .money { color: #047857; }
        .rp-product { margin-top: 4px; border: 1px solid #d1d5db; page-break-inside: auto; }
        .rp-product-head { background: #f8fafc; padding: 2px 4px; border-bottom: 1px solid #e5e7eb; }
        .rp-product-name { font-size: 8.6px; font-weight: 700; color: #111827; }
        .rp-product-metrics { table-layout: fixed; }
        .rp-product-metrics td { padding: 1px 4px; border-right: 1px solid #e5e7eb; }
        .rp-product-metrics td:last-child { border-right: 0; }
        .rp-tickets { table-layout: fixed; border-top: 1px solid #e5e7eb; }
        .rp-tickets th { padding: 2px 3px; background: #f3f4f6; font-size: 6.2px; text-align: left; border-right: 1px solid #d1d5db; }
        .rp-tickets td { padding: 1px 3px; font-size: 6.6px; border-top: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; vertical-align: middle; }
        .rp-tickets th:last-child, .rp-tickets td:last-child { border-right: 0; }
        .rp-evid-title { padding: 2px 4px; font-weight: 700; font-size: 6.8px; background: #fbfdff; border-top: 1px solid #e5e7eb; }
        .rp-evidence-grid { table-layout: fixed; }
        .rp-evidence-grid > tbody > tr { page-break-inside: avoid; }
        .rp-evidence-grid > tbody > tr > td { width: 50%; padding: 2px 4px; vertical-align: top; border-top: 1px solid #eef2f7; }
        .rp-evidence-grid > tbody > tr > td + td { border-left: 1px solid #eef2f7; }
        .rp-evidence-label { font-size: 6.5px; font-weight: 700; margin-bottom: 2px; color: #374151; }
        .rp-images-table { table-layout: fixed; }
        .rp-image-cell { width: 50%; padding: 1px; text-align: center; vertical-align: top; }
        .rp-image-cell img { width: 100%; max-height: 31mm; object-fit: cover; border: 1px solid #d1d5db; }
        .rp-image-caption { font-size: 5.3px; color: #4b5563; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .rp-no-image { color: #9ca3af; font-size: 6px; padding: 2px 0; }
        .rp-footer { margin-top: 5px; border-top: 1px solid #9ca3af; padding-top: 2px; page-break-inside: avoid; }
        .rp-footer-grid { table-layout: fixed; }
        .rp-footer-grid td { vertical-align: top; padding: 1px 3px; font-size: 6.7px; overflow-wrap: anywhere; }
        .rp-sign { text-align: center; margin-top: 6px; font-size: 6.8px; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
@php
    $emitente = $configEmitente ?? \App\Models\ConfigNota::configStatic();
@endphp

@foreach($dadosRelatorio as $relatorio)
    @php
        $pesagem = $relatorio['pesagem'];
        $resumo = $relatorio['resumo'] ?? \App\Support\PesagemReportCalculator::summarize($pesagem);
        $exibirValores = (bool) ($emitente->pesagem_exibir_valores_relatorio ?? true);
        $exibirChavePix = (bool) ($emitente->pesagem_exibir_chave_pix_relatorio ?? false);
        $exibirImagens = (bool) ($emitente->pesagem_imprimir_imagens_a4 ?? true);
        $chavePix = $pesagem->tipo === 'compra'
            ? ($pesagem->fornecedor->pix ?? '')
            : ($pesagem->cliente->pix ?? '');

        $logoBase64 = null;
        $logoPath = !empty($emitente->logo) ? public_path('logos/' . $emitente->logo) : null;
        if ($logoPath && is_file($logoPath)) {
            $logoConteudo = function_exists('safe_file_get_contents')
                ? safe_file_get_contents($logoPath)
                : file_get_contents($logoPath);
            if ($logoConteudo !== false) {
                $logoBase64 = 'data:image/png;base64,' . base64_encode($logoConteudo);
            }
        }

        $qrPesagemBase64 = null;
        try {
            $qrUrl = url('/getTicket/withToken/relPrn80mm/' . ($pesagem->token ?? ''));
            if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
                $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(76)->margin(0)->generate($qrUrl);
                $qrPesagemBase64 = 'data:image/svg+xml;base64,' . base64_encode((string) $qrSvg);
            } elseif (class_exists(\BaconQrCode\Writer::class)) {
                $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                    new \BaconQrCode\Renderer\RendererStyle\RendererStyle(76),
                    new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                );
                $writer = new \BaconQrCode\Writer($renderer);
                $qrPesagemBase64 = 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($qrUrl));
            }
        } catch (\Throwable $e) {
            $qrPesagemBase64 = null;
        }
    @endphp

    <div class="rp-page">
        <table class="rp-header">
            <tr>
                <td class="rp-logo-cell">
                    @if($logoBase64)
                        <img class="rp-logo" src="{{ $logoBase64 }}" alt="Logo">
                    @else
                        <div class="rp-brand-fallback">{{ $emitente->nome_fantasia ?? $emitente->razao_social ?? 'Emitente' }}</div>
                    @endif
                </td>
                <td class="rp-title-cell">
                    <div class="rp-title">Relatório de Pesagem</div>
                    <div class="rp-subtitle">{{ $emitente->razao_social ?? 'Emitente não informado' }}</div>
                    <div class="rp-subtitle">Pesagem #{{ $pesagem->id }} · {{ ucfirst((string) $pesagem->status) }} · {{ optional($pesagem->created_at)->format('d/m/Y H:i') }}</div>
                </td>
                <td class="rp-qr-cell">
                    @if($qrPesagemBase64)
                        <img class="rp-qr" src="{{ $qrPesagemBase64 }}" alt="QR Code">
                    @endif
                    <div class="rp-token">{{ $pesagem->token ?? '' }}</div>
                </td>
            </tr>
        </table>

        <table class="rp-info">
            <tr>
                <td width="27%"><span class="rp-label">Cliente / Fornecedor</span><span class="rp-value">{{ $pesagem->cliente->razao_social ?? $pesagem->cliente->nome ?? $pesagem->fornecedor->razao_social ?? $pesagem->fornecedor->nome ?? 'N/A' }}</span></td>
                <td width="10%"><span class="rp-label">Tipo</span><span class="rp-value">{{ ucfirst((string) ($pesagem->tipo ?? '')) }}</span></td>
                <td width="13%"><span class="rp-label">Placa 1</span><span class="rp-value">{{ $pesagem->veiculo->placa ?? $pesagem->placa_veiculo ?? 'N/A' }}</span></td>
                <td width="13%"><span class="rp-label">Placa 2</span><span class="rp-value">{{ $pesagem->placa_carreta ?: 'N/A' }}</span></td>
                <td width="{{ $exibirChavePix ? '22%' : '37%' }}"><span class="rp-label">Motorista</span><span class="rp-value">{{ $pesagem->motorista->nome ?? $pesagem->motorista_nome ?? 'N/A' }}</span></td>
                @if($exibirChavePix)
                    <td width="15%"><span class="rp-label">Chave PIX</span><span class="rp-value">{{ $chavePix !== '' ? $chavePix : 'Não informada' }}</span></td>
                @endif
            </tr>
        </table>

        <table class="rp-summary">
            <tr>
                <td><span class="rp-label">Peso Inicial</span><div class="num">{{ number_format($resumo['peso_inicial'], 2, ',', '.') }} kg</div></td>
                <td><span class="rp-label">Peso Final</span><div class="num">{{ number_format($resumo['peso_final'], 2, ',', '.') }} kg</div></td>
                <td><span class="rp-label">Peso Líquido</span><div class="num">{{ number_format($resumo['peso_liquido_total'], 2, ',', '.') }} kg</div></td>
                <td><span class="rp-label">Descontos</span><div class="num">{{ number_format($resumo['descontos'], 2, ',', '.') }} kg</div></td>
                <td><span class="rp-label">Peso Final Líquido</span><div class="num">{{ number_format($resumo['peso_final_liquido'], 2, ',', '.') }} kg</div></td>
                @if($exibirValores)
                    <td><span class="rp-label">Valor Total</span><div class="num money">R$ {{ number_format($resumo['valor_total_operacao'], 2, ',', '.') }}</div></td>
                @endif
            </tr>
        </table>

        <div class="rp-section">
            <div class="rp-section-title">Produtos, tickets e evidências</div>

            @foreach($resumo['produtos'] as $grupo)
                @php
                    $ticketsGrupo = collect($grupo['tickets'] ?? [])->values();
                    $entradaTicket = $ticketsGrupo->first(function ($ticket) {
                        return ($ticket->tipo ?? null) === 'entrada';
                    });
                    $saidaTicket = $ticketsGrupo->reverse()->first(function ($ticket) {
                        return ($ticket->tipo ?? null) === 'saida';
                    });
                    $entradaApresentacao = $entradaTicket ? (float) $entradaTicket->peso : (float) ($grupo['entrada'] ?? 0);
                    $saidaApresentacao = $saidaTicket ? (float) $saidaTicket->peso : (float) ($grupo['saida'] ?? 0);
                @endphp

                <div class="rp-product">
                    <div class="rp-product-head">
                        <span class="rp-product-name">{{ $grupo['produto']->nome ?? 'Produto não informado' }}</span>
                    </div>

                    <table class="rp-product-metrics">
                        <tr>
                            <td><span class="rp-label">Entrada</span><span class="rp-value">{{ number_format($entradaApresentacao, 2, ',', '.') }} kg</span></td>
                            <td><span class="rp-label">Saída</span><span class="rp-value">{{ number_format($saidaApresentacao, 2, ',', '.') }} kg</span></td>
                            <td><span class="rp-label">Recipiente</span><span class="rp-value">{{ number_format($grupo['peso_bag'], 2, ',', '.') }} kg</span></td>
                            <td><span class="rp-label">Peso Líquido</span><span class="rp-value">{{ number_format($grupo['peso_liquido'], 2, ',', '.') }} kg</span></td>
                        </tr>
                    </table>

                    <table class="rp-tickets">
                        <thead>
                        <tr>
                            <th width="9%">Ticket</th>
                            <th width="12%">Tipo</th>
                            <th width="18%">Leitura</th>
                            <th width="15%">Recip.</th>
                            <th width="20%">Início</th>
                            <th width="20%">Fim</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($ticketsGrupo as $ticket)
                            <tr>
                                <td>#{{ $ticket->id }}</td>
                                <td>{{ ucfirst((string) $ticket->tipo) }}</td>
                                <td>{{ number_format($ticket->peso, 2, ',', '.') }} kg</td>
                                <td>{{ number_format($ticket->peso_bag ?? 0, 2, ',', '.') }} kg</td>
                                <td>{{ $ticket->inicio ? \Carbon\Carbon::parse($ticket->inicio)->format('d/m H:i') : optional($ticket->created_at)->format('d/m H:i') }}</td>
                                <td>{{ $ticket->fim ? \Carbon\Carbon::parse($ticket->fim)->format('d/m H:i') : '—' }}</td>
                                <td>{{ ucfirst((string) $ticket->status) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    @if($exibirImagens)
                        <div class="rp-evid-title">Evidências fotográficas</div>
                        <table class="rp-evidence-grid">
                            @foreach($ticketsGrupo->chunk(2) as $linhaTickets)
                                <tr>
                                    @foreach($linhaTickets as $ticket)
                                        <td @if($linhaTickets->count() === 1) colspan="2" @endif>
                                            <div class="rp-evidence-label">Ticket #{{ $ticket->id }} · {{ ucfirst((string) $ticket->tipo) }}</div>
                                            @include('relatorios.partials.ticket-imagens-compactas', [
                                                'ticket' => $ticket,
                                                'configNota' => $emitente,
                                                'limiteImagens' => 4,
                                                'preferirEmbed' => true,
                                            ])
                                        </td>
                                    @endforeach

                                </tr>
                            @endforeach
                        </table>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="rp-footer">
            <table class="rp-footer-grid">
                <tr>
                    <td width="62%">
                        <strong>{{ $emitente->razao_social ?? 'N/A' }}</strong><br>
                        CNPJ: {{ $emitente->cnpj ?? 'N/A' }} · IE: {{ $emitente->ie ?? 'N/A' }}<br>
                        {{ $emitente->municipio ?? 'N/A' }} / {{ $emitente->uf ?? $emitente->UF ?? 'N/A' }} · {{ $emitente->fone ?? 'N/A' }}<br>
                        <span class="muted">{{ $emitente->email ?? '' }}</span>
                    </td>
                    <td width="38%" style="text-align:right">
                        <strong>Total Líquido: {{ number_format($resumo['peso_liquido_total'], 2, ',', '.') }} kg</strong><br>
                        @if($exibirValores)
                            <strong>Valor Total: R$ {{ number_format($resumo['valor_total_operacao'], 2, ',', '.') }}</strong><br>
                        @endif
                    </td>
                </tr>
            </table>
            <div class="rp-sign">__________________________________________<br>Assinatura</div>
        </div>
    </div>
@endforeach
</body>
</html>
