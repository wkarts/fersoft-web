@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header">
        <h3 class="card-title">Detalhe do Ajuste #{{ $ajuste->id }}</h3>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <a href="/estoque/ajustes" class="btn btn-light">Voltar</a>
        </div>

        <div class="row mb-4">
            <div class="col-md-3"><strong>Data:</strong> {{ optional($ajuste->data_ref)->format('d/m/Y') }}</div>
            <div class="col-md-3"><strong>Filial:</strong> {{ $ajuste->filial_id ?? 'Todas' }}</div>
            <div class="col-md-3"><strong>Usuário:</strong> {{ $ajuste->usuario_id }}</div>
            <div class="col-md-3"><strong>Empresa:</strong> {{ $ajuste->empresa_id }}</div>
            <div class="col-md-12 mt-2"><strong>Observação:</strong> {{ $ajuste->observacao ?: '—' }}</div>
        </div>

        <h5>Itens do ajuste</h5>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produto</th>
                        <th>Tipo</th>
                        <th>Quantidade</th>
                        <th>Custo Unitário</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($ajuste->itens ?? []) as $i => $item)
                        <tr>
                            <td>{{ $i }}</td>
                            <td>{{ $produtos[$item['produto_id']]->nome ?? ('Produto #'.$item['produto_id']) }}</td>
                            <td>{{ strtoupper($item['tipo']) }}</td>
                            <td>{{ number_format((float)($item['quantidade'] ?? 0), 4, ',', '.') }}</td>
                            <td>{{ isset($item['custo_unitario']) && $item['custo_unitario'] !== null ? 'R$ '.number_format((float)$item['custo_unitario'], 6, ',', '.') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <h5>Movimentos gerados no ledger</h5>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Contexto</th>
                        <th>Tipo</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Custo Unitário</th>
                        <th>Valor Total</th>
                        <th>Usuário</th>
                        <th>Idempotency Key</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimentos as $mov)
                        <tr>
                            <td>{{ optional($mov->movimentado_em)->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $mov->contexto }}</td>
                            <td>{{ strtoupper($mov->tipo) }}</td>
                            <td>{{ $mov->produto_id }}</td>
                            <td>{{ number_format((float)$mov->quantidade, 4, ',', '.') }}</td>
                            <td>{{ $mov->custo_unitario !== null ? 'R$ '.number_format((float)$mov->custo_unitario, 6, ',', '.') : '—' }}</td>
                            <td>{{ $mov->valor_total !== null ? 'R$ '.number_format((float)$mov->valor_total, 2, ',', '.') : '—' }}</td>
                            <td>{{ $mov->usuario_id ?? '—' }}</td>
                            <td><small>{{ $mov->idempotency_key }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">Sem movimentos vinculados ao ajuste.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
