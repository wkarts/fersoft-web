@extends('default.layout', ['title' => 'Extrato de Adiantamentos'])

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Extrato: <b>{{ $pessoa->razao_social }}</b></h3>
            <div class="card-tools">
                <a href="{{ route('adiantamentos.index') }}" class="btn btn-default btn-sm">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr class="bg-light">
                        <th>Data</th>
                        <th>Descrição / Documento</th>
                        <th>Entrada (+)</th>
                        <th>Saída (-)</th>
                        <th>Saldo do Item</th>
                    </tr>
                </thead>
                <tbody>
    @php 
        $saldoAcumulado = 0; 
    @endphp

    {{-- 
       IMPORTANTE: Para o saldo acumulado fazer sentido, 
       os lançamentos devem estar em ordem CRONOLÓGICA (do mais antigo para o novo)
    --}}
    @foreach($lancamentos->sortBy('data') as $l)
        {{-- 1. Soma o Adiantamento realizado --}}
        @php $saldoAcumulado += $l->valor_total; @endphp
        <tr>
            <td>{{ date('d/m/Y', strtotime($l->data)) }}</td>
            <td>Adiantamento Realizado</td>
            <td class="text-success">R$ {{ number_format($l->valor_total, 2, ',', '.') }}</td>
            <td>-</td>
            <td class="font-weight-bold">R$ {{ number_format($saldoAcumulado, 2, ',', '.') }}</td>
        </tr>
        
        {{-- 2. Subtrai as utilizações (baixas) deste adiantamento --}}
        @foreach($l->movimentacoes as $mov)
            @php $saldoAcumulado -= $mov->valor; @endphp
            <tr class="text-muted">
                <td>{{ date('d/m/Y', strtotime($mov->data)) }}</td>
                <td>
                    <i class="fas fa-level-up-alt fa-rotate-90"></i> 
                    Baixa: {{ $tipo == 'cliente' ? 'Venda' : 'Compra' }} 
                    #{{ $mov->conta_receber_id ?? $mov->conta_pagar_id }}
                </td>
                <td>-</td>
                <td class="text-danger">R$ {{ number_format($mov->valor, 2, ',', '.') }}</td>
                <td class="font-weight-bold">R$ {{ number_format($saldoAcumulado, 2, ',', '.') }}</td>
            </tr>
        @endforeach
    @endforeach
</tbody>
            </table>
        </div>
    </div>
</div>
@endsection