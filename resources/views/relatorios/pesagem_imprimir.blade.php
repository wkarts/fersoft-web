
<style>
.ticket-imagens{display:flex;gap:8px;flex-wrap:wrap;margin-top:6px}.ticket-imagem-item{border:1px solid #ddd;padding:4px;border-radius:4px;page-break-inside:avoid}.ticket-imagem-item img{max-width:180px;max-height:120px;object-fit:contain}.ticket-imagem-caption{font-size:9px;color:#555;margin-top:2px}.ticket-imagens-80mm .ticket-imagem-item img{max-width:260px;max-height:180px}
</style>
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
                            @include('relatorios.partials.ticket-imagens', ['ticket' => $ticket, 'tipoRelatorioImagem' => 'a4'])
                        @endforeach
                    </ul>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
