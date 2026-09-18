<style>
    .ticket-imagens{display:flex;gap:8px;flex-wrap:wrap;margin-top:6px}.ticket-imagem-item{border:1px solid #ddd;padding:4px;border-radius:4px;page-break-inside:avoid}.ticket-imagem-item img{max-width:180px;max-height:120px;object-fit:contain}.ticket-imagem-caption{font-size:9px;color:#555;margin-top:2px}.ticket-imagens-80mm .ticket-imagem-item img{max-width:260px;max-height:180px}
</style>
@extends('relatorios.default')

@section('content')
    @php
        $resumo = \App\Support\PesagemReportCalculator::summarize($pesagem);
    @endphp
    <h3>{{ $title }}</h3>
    <p>Veículo: {{ $pesagem->veiculo->placa ?? 'N/A' }}</p>
    <p>Status: {{ ucfirst($pesagem->status) }}</p>
    <p><strong>Peso Inicial:</strong> {{ number_format($resumo['peso_inicial'], 2, ',', '.') }} kg</p>
    <p><strong>Peso Final:</strong> {{ number_format($resumo['peso_final'], 2, ',', '.') }} kg</p>
    <p><strong>Peso Líquido Total:</strong> {{ number_format($resumo['peso_liquido_total'], 2, ',', '.') }} kg</p>
    @if((bool) (($configEmitente ?? null)->pesagem_exibir_valores_relatorio ?? true))
        <p><strong>Valor Total da Operação:</strong> R$ {{ number_format($resumo['valor_total_operacao'], 2, ',', '.') }}</p>
    @endif

    <h4>Detalhes por Produto</h4>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Produto</th>
            <th>Entrada</th>
            <th>Saída</th>
            <th>Peso Líquido</th>
            <th>Tickets</th>
        </tr>
        </thead>
        <tbody>
        @foreach($resumo['produtos'] as $grupo)
            <tr>
                <td>{{ $grupo['produto']->nome ?? 'Produto não informado' }}</td>
                <td>{{ number_format($grupo['entrada'], 2, ',', '.') }} kg</td>
                <td>{{ number_format($grupo['saida'], 2, ',', '.') }} kg</td>
                <td>{{ number_format($grupo['peso_liquido'], 2, ',', '.') }} kg</td>
                <td>
                    <ul>
                        @foreach($grupo['tickets'] as $ticket)
                            <li>ID: {{ $ticket->id }}, {{ ucfirst($ticket->tipo) }}, leitura: {{ number_format($ticket->peso, 2, ',', '.') }} kg</li>
                            @include('relatorios.partials.ticket-imagens', ['ticket' => $ticket, 'tipoRelatorioImagem' => 'a4'])
                        @endforeach
                    </ul>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
