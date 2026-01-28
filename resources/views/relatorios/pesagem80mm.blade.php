@extends('relatorios.default80mm')

@section('content')
    <div>
        @foreach($dadosRelatorio as $relatorio)
            <p><strong>Pesagem ID:</strong> {{ $relatorio['pesagem']->id }}</p>
            <p><strong>Veículo:</strong> {{ $relatorio['pesagem']->veiculo->placa ?? 'N/A' }}</p>
            <p><strong>Status:</strong> {{ ucfirst($relatorio['pesagem']->status) }}</p>
            <p><strong>Peso Total:</strong> {{ number_format($relatorio['peso_total_pesagem'], 2, ',', '.') }} kg</p>
            <hr class="line">

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
                <hr class="line">
            @endforeach
        @endforeach

        <p><strong>Peso Total Geral:</strong> {{ number_format($pesoTotalGeral, 2, ',', '.') }} kg</p>
    </div>
@endsection
