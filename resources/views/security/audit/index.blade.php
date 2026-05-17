@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
        @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Auditoria</h3>
                <p class="text-muted mb-0">Logs de alterações e atividades. SuperAdmin possui visão completa; tenants respeitam políticas de auditoria.</p>
            </div>
            <div>
                <a href="/seguranca/auditoria/politicas" class="btn btn-light">Políticas</a>
                <a href="/seguranca/auditoria/restaurar" class="btn btn-warning">Restauração</a>
            </div>
        </div>

        <form method="get" action="/seguranca/auditoria" class="mb-4">
            <div class="row">
                <div class="form-group col-lg-2 col-md-4">
                    <label>Data Inicial</label>
                    <input type="date" name="data_inicial" class="form-control" value="{{ request('data_inicial') }}">
                </div>
                <div class="form-group col-lg-2 col-md-4">
                    <label>Data Final</label>
                    <input type="date" name="data_final" class="form-control" value="{{ request('data_final') }}">
                </div>
                <div class="form-group col-lg-2 col-md-4">
                    <label>Filial</label>
                    <select name="filial_id" class="form-control">
                        <option value="">Todas</option>
                        <option value="null" {{ request('filial_id') === 'null' ? 'selected' : '' }}>Matriz</option>
                        @foreach($filiais as $filial)
                            <option value="{{ $filial->id }}" {{ request('filial_id') == $filial->id ? 'selected' : '' }}>{{ $filial->nome_fantasia }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-2 col-md-4">
                    <label>Usuário</label>
                    <select name="usuario_id" class="form-control">
                        <option value="">Todos</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" {{ request('usuario_id') == $usuario->id ? 'selected' : '' }}>{{ $usuario->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-2 col-md-4">
                    <label>Ação</label>
                    <select name="acao" class="form-control">
                        <option value="">Todas</option>
                        @foreach($acoes as $acao)
                            <option value="{{ $acao }}" {{ request('acao') == $acao ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $acao)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-lg-2 col-md-4">
                    <label>Modelo</label>
                    <input type="text" name="modelo" class="form-control" value="{{ request('modelo') }}" placeholder="Model">
                </div>
            </div>
            <button class="btn btn-primary">Filtrar</button>
            <a href="/seguranca/auditoria" class="btn btn-secondary">Limpar</a>
        </form>

        <form method="get" action="/seguranca/auditoria/exportar-json" class="alert alert-light border mb-4">
            <strong>Exportação JSON protegida</strong>
            <div class="row mt-3">
                <div class="col-md-2"><input type="text" name="log_id" class="form-control" placeholder="ID do log"></div>
                <div class="col-md-2"><input type="date" name="data_inicial" class="form-control" value="{{ request('data_inicial') }}"></div>
                <div class="col-md-2"><input type="date" name="data_final" class="form-control" value="{{ request('data_final') }}"></div>
                <div class="col-md-3"><input type="text" name="modelo" class="form-control" placeholder="Model"></div>
                <div class="col-md-3"><button class="btn btn-outline-primary">Exportar até 500 logs</button></div>
            </div>
            <small class="text-muted">Para tenant, a exportação depende da configuração da empresa e da política da model. SuperAdmin exporta completo.</small>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Ação</th>
                        <th>Modelo</th>
                        <th>Registro</th>
                        <th>Usuário</th>
                        <th>Filial</th>
                        <th>IP</th>
                        <th width="220">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td><span class="badge badge-info">{{ $log->acao }}</span></td>
                            <td><small>{{ $log->modelo }}</small></td>
                            <td>{{ $log->registro_id ?? '-' }}</td>
                            <td>{{ optional($log->usuario)->nome ?? $log->usuario_id ?? '-' }}</td>
                            <td>{{ optional($log->filial)->nome_fantasia ?? 'Matriz' }}</td>
                            <td>{{ $log->ip_address }}</td>
                            <td>
                                <a class="btn btn-sm btn-primary" href="/seguranca/auditoria/{{ $log->id }}">Detalhes</a>
                                <a class="btn btn-sm btn-light" href="/seguranca/auditoria/{{ $log->id }}/json" target="_blank">JSON</a>
                                @if(in_array($log->acao, ['update', 'delete', 'create']))
                                    <a class="btn btn-sm btn-warning" href="/seguranca/auditoria/{{ $log->id }}/restaurar/preview">Restaurar</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">Nenhum log encontrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    </div>
</div>
@endsection
