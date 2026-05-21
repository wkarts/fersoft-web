@extends('relatorios.default80mm')

@section('content')
<style>
    .ticket-imagens{margin:4px 0;text-align:center}.ticket-imagem-item{display:block;margin:3px auto;page-break-inside:avoid}.ticket-imagem-item img{max-width:68mm;max-height:42mm;object-fit:contain}.ticket-imagem-caption{font-size:8px;text-align:center;word-break:break-word}.peso-destaque{font-size:13px;font-weight:bold;text-align:center}.small{font-size:8px}
</style>
<div>
    @foreach($dadosRelatorio as $relatorio)
        <p class="text-center"><strong>Pesagem #{{ $relatorio['pesagem']->id }}</strong></p>
        <p>Status: {{ ucfirst($relatorio['pesagem']->status) }}</p>
        <p>Veículo: {{ $relatorio['pesagem']->veiculo->placa ?? 'N/A' }}</p>
        <p class="peso-destaque">Peso Total: {{ number_format($relatorio['peso_total_pesagem'], 2, ',', '.') }} kg</p>
        <hr class="line">

        @foreach($relatorio['tickets_agrupados'] as $produtoId => $grupo)
            <p><strong>Produto:</strong> {{ $grupo['produto']->nome ?? 'Não informado' }}</p>
            @foreach($grupo['tickets'] as $ticket)
                <p><strong>Ticket #{{ $ticket->id }}</strong> - {{ ucfirst($ticket->tipo) }}</p>
                <p>Peso: {{ number_format($ticket->peso, 2, ',', '.') }} kg</p>
                <p>Data: {{ optional($ticket->created_at)->format('d/m/Y H:i') }}</p>
                @include('relatorios.partials.ticket-imagens', ['ticket' => $ticket, 'tipoRelatorioImagem' => '80mm', 'limiteImagens' => 2])
                <hr class="line">
            @endforeach
        @endforeach
    @endforeach

    <p class="peso-destaque">Peso Total Geral: {{ number_format($pesoTotalGeral, 2, ',', '.') }} kg</p>
</div>
@endsection
