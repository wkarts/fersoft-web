@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
        @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Restauração por Auditoria</h3>
                <p class="text-muted mb-0">Permite pré-visualizar e restaurar/reverter registros a partir dos logs, respeitando políticas de auditoria.</p>
            </div>
            <a href="/seguranca/auditoria" class="btn btn-light">Voltar</a>
        </div>

        <form method="get" class="mb-4">
            <div class="input-group">
                <input type="text" name="log_id" class="form-control" placeholder="Informe o ID do log" value="{{ request('log_id') }}">
                <div class="input-group-append"><button class="btn btn-primary">Buscar</button></div>
            </div>
        </form>

        <div class="alert alert-warning">
            A restauração automática só é executada após pré-visualização. Models fiscais ou críticas devem ser liberadas por política específica.
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Ação</th>
                        <th>Modelo</th>
                        <th>Registro</th>
                        <th width="140">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->acao }}</td>
                            <td><small>{{ $log->modelo }}</small></td>
                            <td>{{ $log->registro_id }}</td>
                            <td><a href="/seguranca/auditoria/{{ $log->id }}/restaurar/preview" class="btn btn-sm btn-warning">Pré-visualizar</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">Nenhum log restaurável encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
