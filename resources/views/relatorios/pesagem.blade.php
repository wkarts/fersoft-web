@extends('relatorios.default')

@section('content')
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h3, .footer h4 {
            margin: 0;
        }
        .summary {
            margin-bottom: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }
        .table th {
            background-color: #f2f2f2;
        }
        .section-title {
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 12px;
            font-weight: bold;
        }
        .assinatura {
            text-align: center;
            margin-top: 20px;
        }

        .qr-carimbo-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border: 1px solid #000;
            padding: 20px;
            margin-top: 20px;
            gap: 10px; /* Espaçamento entre as colunas */
        }

        .carimbo, .qr-code {
            flex: 2; /* Divide igualmente o espaço */
        }

        .carimbo {
            text-align: left; /* Alinha o texto à esquerda */
        }

        .qr-code {
            text-align: right; /* Centraliza o QR Code */
        }

        .qr-code img {
            width: 120px; /* Ajusta o tamanho do QR Code */
            height: auto;
        }

        .qr-code p {
            margin-top: 10px; /* Espaçamento acima do texto */
        }
        .qr-code {
            position: absolute; /* QR Code posicionado de forma independente */
            top: 180px; /* Espaçamento do topo */
            right: 20px; /* Espaçamento da direita */
            text-align: center; /* Centraliza o texto no QR Code */
        }

        .qr-code img {
            width: 100px; /* Tamanho do QR Code */
            height: auto;
        }

        .qr-code p {
            margin-top: 10px; /* Espaço acima do texto */
        }
    </style>

    @foreach($dadosRelatorio as $relatorio)
        <div class="pesagem">
            <h4 class="section-title">Pesagem ID: {{ $relatorio['pesagem']->id }}</h4>
            <p><strong>Veículo:</strong> {{ $relatorio['pesagem']->veiculo->placa ?? 'N/A' }}</p>
            <p><strong>Status:</strong> {{ ucfirst($relatorio['pesagem']->status) }}</p>
            <p><strong>Peso Total da Pesagem:</strong> {{ number_format($relatorio['peso_total_pesagem'], 2, ',', '.') }} kg</p>

            <div class="summary">
                <p><strong>Total Entrada:</strong> {{ number_format($relatorio['pesagem']->tickets->where('tipo', 'entrada')->sum('peso'), 2, ',', '.') }} kg</p>
                <p><strong>Total Saída:</strong> {{ number_format($relatorio['pesagem']->tickets->where('tipo', 'saida')->sum('peso'), 2, ',', '.') }} kg</p>
                <p><strong>Peso Líquido:</strong> {{ number_format(max(0, $relatorio['pesagem']->tickets->where('tipo', 'entrada')->sum('peso') - $relatorio['pesagem']->tickets->where('tipo', 'saida')->sum('peso')), 2, ',', '.') }} kg</p>
                <p><strong>Descontos:</strong> {{ number_format($relatorio['pesagem']->descontos ?? 0, 2, ',', '.') }} kg</p>
                <p><strong>Peso Final:</strong> {{ number_format($relatorio['pesagem']->peso_final ?? 0, 2, ',', '.') }} kg</p>
            </div>

            <h5 class="section-title">Detalhes por Produto</h5>
            @foreach($relatorio['tickets_agrupados'] as $produtoId => $grupo)
                @php
                    $produto = $grupo['produto'];
                @endphp

                <h6><strong>Produto:</strong> {{ $produto->nome ?? 'Produto não informado' }}</h6>
                <div class="summary">
                    <p><strong>Peso Bruto:</strong> {{ number_format($grupo['peso_total'], 2, ',', '.') }} kg</p>
                    <p><strong>Total Entrada:</strong> {{ number_format($grupo['tickets']->where('tipo', 'entrada')->sum('peso'), 2, ',', '.') }} kg</p>
                    <p><strong>Total Saída:</strong> {{ number_format($grupo['tickets']->where('tipo', 'saida')->sum('peso'), 2, ',', '.') }} kg</p>
                    <p><strong>Peso Líquido:</strong> {{ number_format(max(0, $grupo['tickets']->where('tipo', 'entrada')->sum('peso') - $grupo['tickets']->where('tipo', 'saida')->sum('peso')), 2, ',', '.') }} kg</p>
                </div>

                <table class="table">
                    <thead>
                    <tr>
                        <th>ID do Ticket</th>
                        <th>Tipo</th>
                        <th>Peso (kg)</th>
                        <th>Data</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($grupo['tickets'] as $ticket)
                        <tr>
                            <td>{{ $ticket->id }}</td>
                            <td>{{ ucfirst($ticket->tipo) }}</td>
                            <td>{{ number_format($ticket->peso, 2, ',', '.') }}</td>
                            <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endforeach

            <hr>
        </div>
    @endforeach

    <div class="footer">
        <h4>Total Geral</h4>
        <p><strong>Peso Total Geral:</strong> {{ number_format($pesoTotalGeral, 2, ',', '.') }} kg</p>

        <div class="qr-carimbo-container">
            <!-- Coluna Esquerda: Carimbo -->
            <div class="carimbo">
                <p><strong>Razão Social:</strong> {{ $configEmitente->razao_social ?? 'N/A' }}</p>
                <p><strong>CNPJ:</strong> {{ $configEmitente->cnpj ?? 'N/A' }}</p>
                <p><strong>IE:</strong> {{ $configEmitente->ie ?? 'N/A' }}</p>
                <p><strong>Município:</strong> {{ $configEmitente->municipio ?? 'N/A' }}</p>
                <p><strong>UF:</strong> {{ $configEmitente->uf ?? 'N/A' }}</p>
                <p><strong>E-mail:</strong> {{ $configEmitente->email ?? 'N/A' }}</p>
                <p><strong>Fone:</strong> {{ $configEmitente->fone ?? 'N/A' }}</p>
            </div>
            <!-- Coluna Direita: QR Code -->
            <div class="qr-code">
                <img src="data:image/png;base64,{{ base64_encode(QrCode::size(100)->generate(env('URL_PESAGEM_TOKEN').'/getTicket/withToken/relPrn80mm/'.$dadosRelatorio[0]['pesagem']->token)) }}" alt="QR Code">
                <p><strong>Token:</strong> {{ $dadosRelatorio[0]['pesagem']->token }}</p>
            </div>
        </div>

        <div class="assinatura">
            <p>__________________________________________</p>
            <p>Assinatura</p>
        </div>

    </div>
@endsection
