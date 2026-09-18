@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        <h4>Detalhes da Conta a Receber: #{{ $conta->id }}</h4>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <p><strong>Cliente:</strong> {{ $conta->cliente->razao_social ?? 'Não informado' }}</p>
                <p><strong>Referência:</strong> {{ $conta->referencia }}</p>
                <p><strong>Valor Integral:</strong> R$ {{ number_format($conta->valor_integral, 2, ',', '.') }}</p>
                <p><strong>Valor Recebido:</strong> R$ {{ number_format($conta->valor_recebido, 2, ',', '.') }}</p>
                <p><strong>Nº Nota Fiscal:</strong> {{ $conta->numero_nota_fiscal > 0 ? $conta->numero_nota_fiscal : ($conta->nf_numero > 0 ? $conta->nf_numero : '--') }}</p>
                <p><strong>Criado por:</strong> {{ $conta->usuario->nome ?? 'Sistema' }}</p>
                <p><strong>Data de Criação:</strong> {{ $conta->created_at ? $conta->created_at->format('d/m/Y H:i') : '--' }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Editado por:</strong> {{ $conta->usuarioEdit->nome ?? '--' }}</p>
                <p><strong>Última Alteração:</strong> {{ $conta->updated_at ? $conta->updated_at->format('d/m/Y H:i') : '--' }}</p>
                @if($conta->status)
                    <p><strong>Baixado por:</strong> {{ $conta->usuarioBaixa->nome ?? 'Sistema' }}</p>
                    <p><strong>Data do Recebimento:</strong> {{ $conta->data_recebimento ? \Carbon\Carbon::parse($conta->data_recebimento)->format('d/m/Y') : 'N/A' }}</p>
                    <p><strong>Status:</strong> <span class="badge badge-success">Pago</span></p>
                @else
                    <p><strong>Status:</strong> <span class="badge badge-danger">Pendente</span></p>
                @endif
                <p><strong>Observação:</strong> {{ $conta->observacao ?? '--' }}</p>
            </div>
        </div>
        <a href="{{ url()->previous() }}" class="btn btn-secondary mt-5"><i class="la la-arrow-left"></i> Voltar</a>
    </div>
</div>
@endsection