@extends('LAYOUT_BASE')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header">
        <h3 class="card-title">Ajustes de Estoque (Ledger)</h3>
    </div>
    <div class="card-body">
        <form method="get" action="/estoque/ajustes" class="form mb-4">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Data início</label>
                    <input type="date" name="data_inicio" class="form-control" value="{{ $filtros['data_inicio'] }}">
                </div>
                <div class="form-group col-md-3">
                    <label>Data fim</label>
                    <input type="date" name="data_fim" class="form-control" value="{{ $filtros['data_fim'] }}">
                </div>
                <div class="form-group col-md-4">
                    <label>Filial</label>
                    <select name="filial_id" class="form-control">
                        <option value="">Todas</option>
                        @foreach($filiais as $filial)
                            <option value="{{ $filial->id }}" {{ (string)$filtros['filial_id'] === (string)$filial->id ? 'selected' : '' }}>{{ $filial->descricao }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary btn-block" type="submit">Filtrar</button>
                </div>
            </div>
        </form>

        <div class="mb-3">
            <a class="btn btn-success" href="/estoque/ajustes/novo">Novo ajuste</a>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Filial</th>
                        <th>Usuário</th>
                        <th>Qtd itens</th>
                        <th>Observação</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($ajustes as $ajuste)
                    <tr>
                        <td>{{ $ajuste->id }}</td>
                        <td>{{ optional($ajuste->data_ref)->format('d/m/Y') }}</td>
                        <td>{{ $ajuste->filial_id ?? 'Todas' }}</td>
                        <td>{{ $ajuste->usuario_id }}</td>
                        <td>{{ is_array($ajuste->itens) ? count($ajuste->itens) : 0 }}</td>
                        <td>{{ $ajuste->observacao ?: '—' }}</td>
                        <td><a class="btn btn-sm btn-light-primary" href="/estoque/ajustes/{{ $ajuste->id }}">Detalhar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">Nenhum ajuste encontrado.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $ajustes->links() }}
    </div>
</div>
@endsection
