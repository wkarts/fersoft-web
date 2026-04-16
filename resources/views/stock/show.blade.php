@extends('default.layout')
@section('content')
<div class="card card-custom">
    <div class="card-header">
        <h3 class="card-title">Histórico de Movimentação: <strong>{{ $item->produto->nome }}</strong></h3>
        <div class="card-toolbar">
            <a href="/estoque" class="btn btn-secondary btn-sm">Voltar</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr class="bg-light">
                        <th>Data/Hora</th>
                        <th>Tipo</th>
                        <th>Origem / Documento</th>
                        <th>Quantidade</th>
                        <th>Saldo no Momento</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimentacoes as $m)
                    <tr>
                        <td>{{ date('d/m/Y H:i', strtotime($m->created_at)) }}</td>
                        <td>
                            <span class="label label-inline {{ $m->tipo == 'entrada' ? 'label-light-success' : 'label-light-danger' }}">
                                {{ strtoupper($m->tipo) }}
                            </span>
                        </td>
                        <td>
                            <span class="text-dark-75 font-weight-bolder">{{ strtoupper($m->origem_tipo ?? 'AJUSTE') }}</span>
                            @if($m->origem_id) <span class="text-muted">#{{ $m->origem_id }}</span> @endif
                        </td>
                        <td class="font-weight-bold {{ $m->tipo == 'entrada' ? 'text-success' : 'text-danger' }}">
                            {{ $m->tipo == 'entrada' ? '+' : '-' }} {{ number_format($m->quantidade, 2, ',', '.') }}
                        </td>
                        <td class="font-weight-boldest text-primary">
                            {{ number_format($m->saldo_momento, 2, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">Nenhuma movimentação registrada para este produto.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection