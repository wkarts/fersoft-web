@extends('default.layout')

@section('content')
<div class="card card-custom">
    <div class="card-header">
        <h3 class="card-title">Detalhes da Requisição #{{ $requisicao->id }}</h3>
        <div class="card-toolbar">
            <a href="{{ route('requisicoes.index') }}" class="btn btn-light-primary font-weight-bold">Voltar</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-5">
            <div class="col-md-4">
                <label class="font-weight-bold">Funcionário:</label>
                <p>{{ $requisicao->funcionario->nome ?? 'N/A' }}</p>
            </div>
            <div class="col-md-4">
                <label class="font-weight-bold">Data/Hora:</label>
                <p>{{ \Carbon\Carbon::parse($requisicao->data_requisicao)->format('d/m/Y H:i') }}</p>
            </div>
            <div class="col-md-4">
                <label class="font-weight-bold">Unidade:</label>
                <p>{{ $requisicao->unidade }}</p>
            </div>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Quantidade</th>
                </tr>
            </thead>
            <tbody>
                {{-- Aqui usamos $item, mas dentro do loop correto --}}
                @foreach($requisicao->itens as $item)
                <tr>
                    <td>{{ $item->produto->nome ?? 'Produto não encontrado' }}</td>
                    <td>{{ number_format($item->quantidade, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection