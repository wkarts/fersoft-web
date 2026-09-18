<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            margin: 0;
            padding: 0;
            width: 80ch;
        }
        .center { text-align: center; }
        .line { border-top: 1px dashed #000; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 2px 4px; }
        .logo { text-align: center; margin-bottom: 5px; }
        .footer { text-align: center; margin-top: 10px; }
        .ticket-imagens{display:flex;gap:8px;flex-wrap:wrap;margin-top:6px}
        .ticket-imagem-item{border:1px solid #ddd;padding:4px;border-radius:4px;page-break-inside:avoid}
        .ticket-imagem-item img{max-width:180px;max-height:120px;object-fit:contain}
        .ticket-imagem-caption{font-size:9px;color:#555;margin-top:2px}
        .ticket-imagens-80mm .ticket-imagem-item img{max-width:260px;max-height:180px}
    </style>
</head>
<body>
<div class="logo">
    <img src="{{ public_path('images/logo.png') }}" alt="Logo da Empresa" style="width: 60px; height: auto;">
</div>

<div class="center">
    <h3>{{ $title }}</h3>
    @if($data_inicial && $data_final)
        <p>Período: {{ $data_inicial }} - {{ $data_final }}</p>
    @endif
    <p>Data: {{ now()->format('d/m/Y H:i') }}</p>
</div>

<div class="line"></div>

@php
    $pesoTotalGeralApresentacao = 0;
@endphp
@foreach($dadosRelatorio as $relatorio)
    @php
        $pesagem = $relatorio['pesagem'];
        $resumo = \App\Support\PesagemReportCalculator::summarize($pesagem);
        $pesoTotalGeralApresentacao += (float) $resumo['peso_liquido_total'];
        $exibirValores = (bool) (($configEmitente ?? null)->pesagem_exibir_valores_relatorio ?? true);
    @endphp

    <p><strong>Pesagem ID:</strong> {{ $pesagem->id }}</p>
    <p><strong>Veículo:</strong> {{ $pesagem->veiculo->placa ?? 'N/A' }}</p>
    <p><strong>Status:</strong> {{ ucfirst($pesagem->status) }}</p>
    <p><strong>Peso Inicial:</strong> {{ number_format($resumo['peso_inicial'], 2, ',', '.') }} kg</p>
    <p><strong>Peso Final:</strong> {{ number_format($resumo['peso_final'], 2, ',', '.') }} kg</p>
    <p><strong>Peso Líquido Total:</strong> {{ number_format($resumo['peso_liquido_total'], 2, ',', '.') }} kg</p>
    @if($resumo['descontos'] > 0)
        <p><strong>Descontos:</strong> {{ number_format($resumo['descontos'], 2, ',', '.') }} kg</p>
    @endif
    <p><strong>Peso Final Líquido:</strong> {{ number_format($resumo['peso_final_liquido'], 2, ',', '.') }} kg</p>
    @if($exibirValores)
        <p><strong>Valor Total da Operação:</strong> R$ {{ number_format($resumo['valor_total_operacao'], 2, ',', '.') }}</p>
    @endif
    <div class="line"></div>

    @foreach($resumo['produtos'] as $grupo)
        <p><strong>Produto:</strong> {{ $grupo['produto']->nome ?? 'Não informado' }}</p>
        <p>
            Entrada: {{ number_format($grupo['entrada'], 2, ',', '.') }} kg |
            Saída: {{ number_format($grupo['saida'], 2, ',', '.') }} kg |
            Líquido: {{ number_format($grupo['peso_liquido'], 2, ',', '.') }} kg
        </p>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Tipo</th>
                <th>Leitura (kg)</th>
                <th>Recip. (kg)</th>
                <th>Data</th>
            </tr>
            </thead>
            <tbody>
            @foreach($grupo['tickets'] as $ticket)
                <tr>
                    <td>{{ $ticket->id }}</td>
                    <td>{{ ucfirst((string) $ticket->tipo) }}</td>
                    <td>{{ number_format($ticket->peso, 2, ',', '.') }}</td>
                    <td>{{ number_format($ticket->peso_bag ?? 0, 2, ',', '.') }}</td>
                    <td>{{ optional($ticket->created_at)->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td colspan="5">@include('relatorios.partials.ticket-imagens', ['ticket' => $ticket, 'tipoRelatorioImagem' => '80mm', 'limiteImagens' => 2])</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="line"></div>
    @endforeach
@endforeach

<div class="footer">
    <p>Peso Líquido Total Geral: {{ number_format($pesoTotalGeralApresentacao, 2, ',', '.') }} kg</p>
    <p>Relatório gerado automaticamente.</p>
    <p>© {{ now()->year }} - Sua Empresa</p>
</div>
</body>
</html>
