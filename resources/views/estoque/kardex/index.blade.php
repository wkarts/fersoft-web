@extends('LAYOUT_BASE')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header">
        <h3 class="card-title">Kardex (Ledger) - Resumo por Produto</h3>
    </div>
    <div class="card-body">
        <form method="get" action="/estoque/kardex" class="form mb-4">
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
                <div class="form-group col-md-2">
                    <label>Filial</label>
                    <select name="filial_id" class="form-control">
                        <option value="">Todas</option>
                        @foreach($filiais as $filial)
                            <option value="{{ $filial->id }}" {{ (string)$filtros['filial_id'] === (string)$filial->id ? 'selected' : '' }}>{{ $filial->descricao }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Produto</label>
                    <select name="produto_id" class="form-control">
                        <option value="">Todos</option>
                        @foreach($produtosFiltro as $produto)
                            <option value="{{ $produto->id }}" {{ (string)$filtros['produto_id'] === (string)$produto->id ? 'selected' : '' }}>{{ $produto->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-1 d-flex align-items-end">
                    <button class="btn btn-primary btn-block" type="submit">Filtrar</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Saldo Inicial</th>
                        <th>Entradas</th>
                        <th>Saídas</th>
                        <th>Saldo Final</th>
                        <th>Custo Médio</th>
                        <th>Valor Total Estimado</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($resumo as $row)
                    <tr>
                        <td>{{ $row['produto_nome'] }}</td>
                        <td>{{ number_format($row['saldo_inicial'], 4, ',', '.') }}</td>
                        <td>{{ number_format($row['entradas'], 4, ',', '.') }}</td>
                        <td>{{ number_format($row['saidas'], 4, ',', '.') }}</td>
                        <td>{{ number_format($row['saldo_final'], 4, ',', '.') }}</td>
                        <td>R$ {{ number_format($row['custo_medio'], 6, ',', '.') }}</td>
                        <td>R$ {{ number_format($row['valor_total_estimado'], 2, ',', '.') }}</td>
                        <td>
                            <a class="btn btn-sm btn-light-primary" href="/estoque/kardex/{{ $row['produto_id'] }}?data_inicio={{ $filtros['data_inicio'] }}&data_fim={{ $filtros['data_fim'] }}&contexto={{ $filtros['contexto'] }}&filial_id={{ $filtros['filial_id'] }}">
                                Analítico
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">Sem dados para os filtros selecionados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
