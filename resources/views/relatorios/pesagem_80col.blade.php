<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            margin: 0;
            padding: 0;
            width: 80ch; /* Limita a largura a 80 colunas */
        }

        .center {
            text-align: center;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 2px 4px;
        }

        .summary {
            text-align: right;
            margin-top: 10px;
        }

        .logo {
            text-align: center;
            margin-bottom: 5px;
        }

        .footer {
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<!-- Cabeçalho com Logo -->
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

<!-- Conteúdo do Relatório -->
@foreach($dadosRelatorio as $relatorio)
    <p><strong>Pesagem ID:</strong> {{ $relatorio['pesagem']->id }}</p>
    <p><strong>Veículo:</strong> {{ $relatorio['pesagem']->veiculo->placa ?? 'N/A' }}</p>
    <p><strong>Status:</strong> {{ ucfirst($relatorio['pesagem']->status) }}</p>
    <p><strong>Peso Total:</strong> {{ number_format($relatorio['peso_total_pesagem'], 2, ',', '.') }} kg</p>
    <div class="line"></div>

    @foreach($relatorio['tickets_agrupados'] as $produtoId => $grupo)
        <p><strong>Produto:</strong> {{ $grupo['produto']->nome ?? 'Não informado' }}</p>
        <p><strong>Peso Total:</strong> {{ number_format($grupo['peso_total'], 2, ',', '.') }} kg</p>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Peso (kg)</th>
                <th>Data</th>
            </tr>
            </thead>
            <tbody>
            @foreach($grupo['tickets'] as $ticket)
                <tr>
                    <td>{{ $ticket->id }}</td>
                    <td>{{ number_format($ticket->peso, 2, ',', '.') }}</td>
                    <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="line"></div>
    @endforeach
@endforeach

<!-- Rodapé -->
<div class="footer">
    <p>Peso Total Geral: {{ number_format($pesoTotalGeral, 2, ',', '.') }} kg</p>
    <p>Relatório gerado automaticamente.</p>
    <p>© {{ now()->year }} - Sua Empresa</p>
</div>
</body>
</html>
