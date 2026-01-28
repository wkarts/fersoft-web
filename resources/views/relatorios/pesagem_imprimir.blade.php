@extends('relatorios.default')

@section('content')
    <h3>{{ $title }}</h3>
    <p>Veículo: {{ $pesagem->veiculo->placa ?? 'N/A' }}</p>
    <p>Status: {{ ucfirst($pesagem->status) }}</p>

    <h4>Detalhes por Produto</h4>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Produto</th>
            <th>Peso Total (kg)</th>
            <th>Tickets</th>
        </tr>
        </thead>
        <tbody>
        @foreach($ticketsAgrupados as $grupo)
            <tr>
                <td>{{ $grupo['produto']->nome ?? 'Produto não informado' }}</td>
                <td>{{ number_format($grupo['peso_total'], 2, ',', '.') }}</td>
                <td>
                    <ul>
                        @foreach($grupo['tickets'] as $ticket)
                            <li>ID: {{ $ticket->id }}, Peso: {{ number_format($ticket->peso, 2, ',', '.') }} kg</li>
                        @endforeach
                    </ul>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
