@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
        @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Recursos do Sistema</h3>
                <p class="text-muted mb-0">Models detectadas a partir do BaseModel e identidade de segurança.</p>
            </div>
            @if($isSuper)
                <form method="post" action="/seguranca/recursos/sincronizar">
                    @csrf
                    <button class="btn btn-primary">Sincronizar recursos</button>
                </form>
            @endif
        </div>

        <form method="get" class="mb-3">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Buscar por módulo, recurso, model ou rota" value="{{ request('search') }}">
                <div class="input-group-append">
                    <button class="btn btn-secondary">Buscar</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Módulo</th>
                        <th>Nome</th>
                        <th>Model</th>
                        <th>Rota</th>
                        <th>Tabela</th>
                        <th>Sensível</th>
                        <th>Tenant</th>
                        <th>Ativo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resources as $resource)
                        <tr>
                            <td>{{ $resource->module }}</td>
                            <td><strong>{{ $resource->plural_display_name ?: $resource->display_name }}</strong><br><small>{{ $resource->description }}</small></td>
                            <td><small>{{ $resource->model_class }}</small></td>
                            <td>{{ $resource->route_prefix }}</td>
                            <td>{{ $resource->table_name }}</td>
                            <td>{{ $resource->sensitive ? 'Sim' : 'Não' }}</td>
                            <td>{{ $resource->tenant_visible ? 'Visível' : 'Oculto' }}</td>
                            <td>{{ $resource->enabled ? 'Sim' : 'Não' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">Nenhum recurso sincronizado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $resources->links() }}
    </div>
</div>
@endsection
