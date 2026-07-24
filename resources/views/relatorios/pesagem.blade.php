@extends('relatorios.default')

@section('content')
@php
    $emitente = $configEmitente ?? \App\Models\ConfigNota::configStatic();
    $primeiraPesagem = $dadosRelatorio[0]['pesagem'] ?? null;
    $logoBase64 = null;
    $qrPesagemBase64 = null;
    $logoPath = null;
    if (!empty($emitente->logo)) {
        $logoPath = public_path('logos/' . $emitente->logo);
    }
    if (!$logoPath || !is_file($logoPath)) {
        $logoPath = public_path('imgs/slym.png');
    }
    if ($logoPath && is_file($logoPath)) {
        $logoBase64 = 'data:image/png;base64,' . base64_encode(function_exists('safe_file_get_contents') ? safe_file_get_contents($logoPath) : file_get_contents($logoPath));
    }

    if ($primeiraPesagem) {
        $qrUrl = url('/getTicket/withToken/relPrn80mm/' . ($primeiraPesagem->token ?? ''));
        try {
            if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
                $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                    ->size(92)
                    ->margin(1)
                    ->generate($qrUrl);
                $qrPesagemBase64 = 'data:image/svg+xml;base64,' . base64_encode((string) $qrSvg);
            } elseif (class_exists(\BaconQrCode\Writer::class)) {
                $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                    new \BaconQrCode\Renderer\RendererStyle\RendererStyle(92),
                    new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                );
                $writer = new \BaconQrCode\Writer($renderer);
                $qrSvg = $writer->writeString($qrUrl);
                $qrPesagemBase64 = 'data:image/svg+xml;base64,' . base64_encode((string) $qrSvg);
            }
        } catch (\Throwable $e) {
            \Log::warning('Falha ao gerar QR Code da pesagem no relatório A4.', [
                'pesagem_id' => $primeiraPesagem->id ?? null,
                'message' => $e->getMessage(),
            ]);
            $qrPesagemBase64 = null;
        }
    }
@endphp

<style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111827; }
    .rp-header { border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 14px; }
    .rp-header-table { width: 100%; border-collapse: collapse; }
    .rp-logo-cell { width: 90px; vertical-align: top; text-align: center; }
    .rp-logo { max-width: 78px; max-height: 60px; object-fit: contain; }
    .rp-qr { width: 74px; height: 74px; object-fit: contain; border: 1px solid #d1d5db; padding: 3px; border-radius: 4px; }
    .rp-qr-caption { font-size: 7px; color: #4b5563; margin-top: 2px; text-align: center; }
    .rp-title { font-size: 20px; font-weight: 700; color: #1f2937; margin: 0 0 4px; }
    .rp-subtitle { font-size: 11px; color: #6b7280; margin: 0; }
    .rp-token { text-align: right; font-size: 10px; color: #374151; vertical-align: top; }
    .rp-box { border: 1px solid #d1d5db; border-radius: 6px; padding: 8px; margin-bottom: 10px; }
    .rp-section-title { font-size: 13px; color: #1d4ed8; font-weight: 700; margin: 12px 0 6px; border-bottom: 1px solid #dbeafe; padding-bottom: 4px; }
    .rp-grid { width: 100%; border-collapse: collapse; }
    .rp-grid td { padding: 3px 4px; vertical-align: top; }
    .rp-label { color: #6b7280; font-size: 9px; text-transform: uppercase; }
    .rp-value { font-weight: 600; }
    .rp-inline-pix { display: inline; margin-left: 8px; color: #374151; font-size: 10px; font-weight: 600; }
    .rp-kpis { width: 100%; border-collapse: collapse; margin: 8px 0 12px; }
    .rp-kpis td { border: 1px solid #dbeafe; background: #eff6ff; padding: 7px; text-align: center; }
    .rp-kpis .n { font-size: 15px; font-weight: 700; color: #1d4ed8; }
    .rp-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .rp-table th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 5px; font-size: 10px; text-align: left; }
    .rp-table td { border: 1px solid #e5e7eb; padding: 5px; font-size: 10px; vertical-align: top; }
    .rp-ticket-images-row td { background: #fbfdff; }
    .ticket-imagens { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
    .ticket-imagem-item { border: 1px solid #d1d5db; padding: 4px; border-radius: 5px; page-break-inside: avoid; background: #fff; display: inline-block; margin-right: 6px; margin-bottom: 6px; }
    .ticket-imagem-item img { max-width: 155px; max-height: 105px; object-fit: contain; display: block; }
    .ticket-imagem-caption { font-size: 8px; color: #4b5563; margin-top: 2px; max-width: 155px; overflow-wrap: break-word; }
    .rp-footer { margin-top: 20px; border-top: 1px solid #d1d5db; padding-top: 8px; font-size: 10px; color: #374151; }
    .rp-sign { margin-top: 28px; text-align: center; }
    .rp-page-break { page-break-after: always; }
</style>

<div class="rp-header">
    <table class="rp-header-table">
        <tr>
            <td class="rp-logo-cell">
                @if($qrPesagemBase64)
                    <img class="rp-qr" src="{{ $qrPesagemBase64 }}" alt="QR Code da Pesagem">
                    <div class="rp-qr-caption">QR Code da pesagem</div>
                @elseif($logoBase64)
                    <img class="rp-logo" src="{{ $logoBase64 }}" alt="Logo">
                @endif
            </td>
            <td>
                <h1 class="rp-title">Relatório de Pesagem</h1>
                <p class="rp-subtitle">{{ $emitente->razao_social ?? 'Emitente não informado' }}</p>
                <p class="rp-subtitle">Emitido em {{ now()->format('d/m/Y H:i') }}</p>
            </td>
            <td class="rp-token">
                @if($primeiraPesagem)
                    <strong>Pesagem #{{ $primeiraPesagem->id }}</strong><br>
                    Token: {{ $primeiraPesagem->token ?? 'N/A' }}
                @endif
            </td>
        </tr>
    </table>
</div>

@foreach($dadosRelatorio as $relatorio)
    @php
        $pesagem = $relatorio['pesagem'];
        $entrada = $pesagem->tickets->where('tipo', 'entrada')->sum('peso');
        $saida = $pesagem->tickets->where('tipo', 'saida')->sum('peso');
        $liquido = max(0, $entrada - $saida);
        $exibirValoresTicket = (bool) ($emitente->usar_valores_ticket_pesagem ?? false);
        $exibirChavePix = (bool) ($emitente->pesagem_exibir_chave_pix_relatorio ?? true);
        $valorDoTicket = static function ($ticket): float {
            $pesoLiquidoTicket = max(0, (float) $ticket->peso - (float) ($ticket->peso_bag ?? 0));
            $valorUnitario = (float) ($ticket->valor_unitario ?? 0) > 0
                ? (float) $ticket->valor_unitario
                : (float) ($ticket->produto->valor_venda ?? $ticket->produto->valor_compra ?? 0);

            return (float) ($ticket->valor_total ?? 0) > 0
                ? (float) $ticket->valor_total
                : ($pesoLiquidoTicket * max(0, $valorUnitario));
        };
        $valorEntrada = $pesagem->tickets->where('tipo', 'entrada')->sum($valorDoTicket);
        $valorSaida = $pesagem->tickets->where('tipo', 'saida')->sum($valorDoTicket);
        $valorAvulsa = $pesagem->tickets->where('tipo', 'avulsa')->sum($valorDoTicket);
        $valorTotalTicket = abs(($valorEntrada + $valorAvulsa) - $valorSaida);
        $chavePix = $pesagem->tipo === 'compra'
            ? ($pesagem->fornecedor->pix ?? '')
            : ($pesagem->cliente->pix ?? '');
    @endphp

    <div class="rp-box">
        <div class="rp-section-title">Dados da Pesagem</div>
        <table class="rp-grid">
            <tr>
                <td width="25%"><div class="rp-label">Pesagem</div><div class="rp-value">#{{ $pesagem->id }}</div></td>
                <td width="25%"><div class="rp-label">Status</div><div class="rp-value">{{ ucfirst($pesagem->status) }}</div></td>
                <td width="25%"><div class="rp-label">Veículo</div><div class="rp-value">{{ $pesagem->veiculo->placa ?? 'N/A' }}</div></td>
                <td width="25%"><div class="rp-label">Motorista</div><div class="rp-value">{{ $pesagem->motorista->nome ?? 'N/A' }}</div></td>
            </tr>
            <tr>
                <td colspan="2"><div class="rp-label">Cliente / Fornecedor</div><div class="rp-value">{{ $pesagem->cliente->razao_social ?? $pesagem->cliente->nome ?? $pesagem->fornecedor->razao_social ?? $pesagem->fornecedor->nome ?? 'N/A' }}@if($exibirChavePix)<span class="rp-inline-pix">| Chave PIX: {{ $chavePix !== '' ? $chavePix : 'Não informada' }}</span>@endif</div></td>
                <td><div class="rp-label">Tipo</div><div class="rp-value">{{ ucfirst((string) ($pesagem->tipo ?? '')) }}</div></td>
                <td><div class="rp-label">Data</div><div class="rp-value">{{ optional($pesagem->created_at)->format('d/m/Y H:i') }}</div></td>
            </tr>
        </table>
    </div>

    <table class="rp-kpis">
        <tr>
            <td><div class="rp-label">Total Entrada</div><div class="n">{{ number_format($entrada, 2, ',', '.') }} kg</div></td>
            <td><div class="rp-label">Total Saída</div><div class="n">{{ number_format($saida, 2, ',', '.') }} kg</div></td>
            <td><div class="rp-label">Peso Líquido</div><div class="n">{{ number_format($liquido, 2, ',', '.') }} kg</div></td>
            <td><div class="rp-label">Peso Final</div><div class="n">{{ number_format($pesagem->peso_final ?? $relatorio['peso_total_pesagem'], 2, ',', '.') }} kg</div></td>
        </tr>
        @if($exibirValoresTicket)
            <tr>
                <td colspan="4"><div class="rp-label">Valor Total dos Tickets</div><div class="n">R$ {{ number_format($valorTotalTicket, 2, ',', '.') }}</div></td>
            </tr>
        @endif
    </table>

    <div class="rp-section-title">Tickets / Produtos</div>
    @foreach($relatorio['tickets_agrupados'] as $produtoId => $grupo)
        <div class="rp-label">Produto</div>
        <div class="rp-value" style="margin-bottom:5px">{{ $grupo['produto']->nome ?? 'Produto não informado' }}</div>
        <table class="rp-table">
            <thead>
                <tr>
                    <th width="9%">Ticket</th>
                    <th width="14%">Tipo</th>
                    <th width="16%">Peso</th>
                    <th width="16%">Recip.</th>
                    <th width="16%">Líquido</th>
                    <th width="16%">Data</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grupo['tickets'] as $ticket)
                    @php($pesoLiquidoTicket = max(0, (float) $ticket->peso - (float) ($ticket->peso_bag ?? 0)))
                    <tr>
                        <td>#{{ $ticket->id }}</td>
                        <td>{{ ucfirst((string) $ticket->tipo) }}</td>
                        <td>{{ number_format($ticket->peso, 2, ',', '.') }} kg</td>
                        <td>{{ number_format($ticket->peso_bag ?? 0, 2, ',', '.') }} kg</td>
                        <td>{{ number_format($pesoLiquidoTicket, 2, ',', '.') }} kg</td>
                        <td>{{ optional($ticket->created_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ ucfirst((string) $ticket->status) }}</td>
                    </tr>
                    <tr class="rp-ticket-images-row">
                        <td colspan="7">
                            <strong>Evidências do ticket #{{ $ticket->id }}</strong>
                            @include('relatorios.partials.ticket-imagens', ['ticket' => $ticket, 'tipoRelatorioImagem' => 'a4', 'limiteImagens' => 6])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
@endforeach

<div class="rp-footer">
    <table class="rp-grid">
        <tr>
            <td width="60%">
                <strong>{{ $emitente->razao_social ?? 'N/A' }}</strong><br>
                CNPJ: {{ $emitente->cnpj ?? 'N/A' }} | IE: {{ $emitente->ie ?? 'N/A' }}<br>
                Município: {{ $emitente->municipio ?? 'N/A' }} / {{ $emitente->uf ?? $emitente->UF ?? 'N/A' }}<br>
                E-mail: {{ $emitente->email ?? 'N/A' }} | Fone: {{ $emitente->fone ?? 'N/A' }}
            </td>
            <td style="text-align:right">
                <strong>Total Geral</strong><br>
                {{ number_format($pesoTotalGeral, 2, ',', '.') }} kg
            </td>
        </tr>
    </table>
    <div class="rp-sign">
        __________________________________________<br>
        Assinatura
    </div>
</div>
@endsection
