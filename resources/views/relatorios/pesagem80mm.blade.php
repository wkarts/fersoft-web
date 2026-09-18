@extends('relatorios.default80mm')

@section('content')
    <style>
        .ticket-imagens{margin:4px 0;text-align:center}.ticket-imagem-item{display:block;margin:3px auto;page-break-inside:avoid}.ticket-imagem-item img{max-width:68mm;max-height:42mm;object-fit:contain}.ticket-imagem-caption{font-size:8px;text-align:center;word-break:break-word}.peso-destaque{font-size:13px;font-weight:bold;text-align:center}.small{font-size:8px}
    </style>
    <div>
        @php
            $pesoTotalGeralApresentacao = 0;
        @endphp
        @foreach($dadosRelatorio as $relatorio)
            @php
                $pesagem = $relatorio['pesagem'];
                $resumo = \App\Support\PesagemReportCalculator::summarize($pesagem);
                $pesoTotalGeralApresentacao += (float) $resumo['peso_liquido_total'];
            @endphp
            <p class="text-center"><strong>Pesagem #{{ $pesagem->id }}</strong></p>
            <p>Status: {{ ucfirst($pesagem->status) }}</p>
            <p>Veículo: {{ $pesagem->veiculo->placa ?? 'N/A' }}</p>
            <p>Peso Inicial: {{ number_format($resumo['peso_inicial'], 2, ',', '.') }} kg</p>
            <p>Peso Final: {{ number_format($resumo['peso_final'], 2, ',', '.') }} kg</p>
            <p class="peso-destaque">Peso Líquido Total: {{ number_format($resumo['peso_liquido_total'], 2, ',', '.') }} kg</p>
            @if((bool) (($configEmitente ?? null)->pesagem_exibir_valores_relatorio ?? true))
                <p><strong>Valor Total da Operação:</strong> R$ {{ number_format($resumo['valor_total_operacao'], 2, ',', '.') }}</p>
            @endif
            <hr class="line">

            @foreach($resumo['produtos'] as $grupo)
                <p><strong>Produto:</strong> {{ $grupo['produto']->nome ?? 'Não informado' }}</p>
                <p>Entrada: {{ number_format($grupo['entrada'], 2, ',', '.') }} kg</p>
                <p>Saída: {{ number_format($grupo['saida'], 2, ',', '.') }} kg</p>
                <p><strong>Líquido:</strong> {{ number_format($grupo['peso_liquido'], 2, ',', '.') }} kg</p>
                @foreach($grupo['tickets'] as $ticket)
                    <p class="small"><strong>Ticket #{{ $ticket->id }}</strong> - {{ ucfirst($ticket->tipo) }} - Leitura: {{ number_format($ticket->peso, 2, ',', '.') }} kg</p>
                    @include('relatorios.partials.ticket-imagens', ['ticket' => $ticket, 'tipoRelatorioImagem' => '80mm', 'limiteImagens' => 2])
                @endforeach
                <hr class="line">
            @endforeach
        @endforeach

        <p class="peso-destaque">Peso Líquido Total Geral: {{ number_format($pesoTotalGeralApresentacao, 2, ',', '.') }} kg</p>
    </div>
@endsection
