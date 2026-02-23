@extends('LAYOUT_BASE')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header">
        <h3 class="card-title">Kardex Analítico - {{ $produto->nome }}</h3>
    </div>
    <div class="card-body">
        <form method="get" class="form mb-4">
            <div class="form-row">
                <div class="form-group col-md-2">
                    <label>Data início</label>
                    <input type="date" name="data_inicio" class="form-control" value="{{ $filtros['data_inicio'] }}">
                </div>
                <div class="form-group col-md-2">
                    <label>Data fim</label>
                    <input type="date" name="data_fim" class="form-control" value="{{ $filtros['data_fim'] }}">
                </div>
                <div class="form-group col-md-2">
                    <label>Contexto</label>
                    <select name="contexto" class="form-control">
                        @foreach(['TODOS','ERP','PESAGEM'] as $ctx)
                            <option value="{{ $ctx }}" {{ $filtros['contexto'] === $ctx ? 'selected' : '' }}>{{ $ctx }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Filial</label>
                    <select name="filial_id" class="form-control">
                        <option value="">Todas</option>
                        @foreach($filiais as $filial)
                            <option value="{{ $filial->id }}" {{ (string)$filtros['filial_id'] === (string)$filial->id ? 'selected' : '' }}>{{ $filial->descricao }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary mr-2" type="submit">Filtrar</button>
                    <a class="btn btn-light" href="/estoque/kardex">Voltar</a>
                </div>
            </div>
        </form>

        <div class="alert alert-light-primary">
            <strong>Saldo inicial do período:</strong> {{ number_format($saldoInicial, 4, ',', '.') }}
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Tipo</th>
                        <th>Contexto</th>
                        <th>Quantidade</th>
                        <th>Custo Unitário</th>
                        <th>Valor Total</th>
                        <th>Origem</th>
                        <th>Usuário</th>
                        <th>Saldo Progressivo</th>
                        <th>Metadata</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($movimentos as $mov)
                    <tr>
                        <td>{{ optional($mov->movimentado_em)->format('d/m/Y H:i:s') }}</td>
                        <td>{{ strtoupper($mov->tipo) }}</td>
                        <td>{{ $mov->contexto }}</td>
                        <td>{{ number_format($mov->quantidade, 4, ',', '.') }}</td>
                        <td>{{ $mov->custo_unitario !== null ? 'R$ '.number_format($mov->custo_unitario, 6, ',', '.') : '—' }}</td>
                        <td>{{ $mov->valor_total !== null ? 'R$ '.number_format($mov->valor_total, 2, ',', '.') : '—' }}</td>
                        <td>{{ $mov->origem_tipo }}#{{ $mov->origem_id }}</td>
                        <td>{{ $mov->usuario_id ?? '—' }}</td>
                        <td>{{ number_format($mov->saldo_progressivo, 4, ',', '.') }}</td>
                        <td>
                            @if(!empty($mov->metadata))
                                <details>
                                    <summary>Ver</summary>
                                    <pre class="mb-0">{{ json_encode($mov->metadata, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
                                    @if(!empty($mov->metadata['produto_origem_id']) || !empty($mov->metadata['produto_destino_id']))
                                        <div class="mt-2 text-info">
                                            Referência: origem {{ $mov->metadata['produto_origem_id'] ?? '—' }} → destino {{ $mov->metadata['produto_destino_id'] ?? '—' }}
                                        </div>
                                    @endif
                                </details>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center">Sem movimentos no período.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $movimentos->links() }}
    </div>
</div>
@endsection
