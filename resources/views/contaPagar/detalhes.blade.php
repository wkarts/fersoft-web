
@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        <h4>Detalhes da Conta: #{{ $conta->id }}</h4>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <p><strong>Referência:</strong> {{ $conta->referencia }}</p>
                <p><strong>Valor Integral:</strong> R$ {{ number_format($conta->valor_integral, 2, ',', '.') }}</p>
                <p><strong>Criado por:</strong> {{ $conta->usuario->nome ?? 'Sistema' }}</p>
                <p><strong>Data de Criação:</strong> {{ $conta->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Última alteraçãoo:</strong> {{ $conta->updated_at->format('d/m/Y H:i') }}</p>
                @if($conta->usuarioBaixa)
                    <p><strong>Baixado por:</strong> {{ $conta->usuarioBaixa->nome }}</p>
                    <p><strong>Data da Baixa:</strong> {{ $conta->data_pagamento ? \Carbon\Carbon::parse($conta->data_pagamento)->format('d/m/Y H:i') : 'N/A' }}</p> : 'N/A' }}</p>
                @else
                    <p><strong>Status:</strong> <span class="badge badge-warning">Pendente</span></p>
                @endif
            </div>
        </div>
        <a href="{{ url()->previous() }}" class="btn btn-secondary mt-5">Voltar</a>
    </div>
</div>
@endsection