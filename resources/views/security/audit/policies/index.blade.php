@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
        @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Políticas de Auditoria</h3>
                <p class="text-muted mb-0">Controle quais models podem aparecer para tenants, exibir JSON, exportar JSON e permitir restauração.</p>
            </div>
            <a href="/seguranca/auditoria" class="btn btn-light">Voltar</a>
        </div>

        @if($isSuper)
            <form method="get" class="mb-4">
                <div class="row">
                    <div class="col-md-5">
                        <select name="empresa_id" class="form-control">
                            <option value="">Política global da plataforma</option>
                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}" {{ (string)$empresaId === (string)$empresa->id ? 'selected' : '' }}>{{ $empresa->nome }} - {{ $empresa->cnpj }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><button class="btn btn-primary">Filtrar empresa</button></div>
                </div>
            </form>
        @endif

        <div class="card bg-light mb-4">
            <div class="card-body">
                <h5>Nova política</h5>
                <form method="post" action="/seguranca/auditoria/politicas">
                    @csrf
                    @if($isSuper)<input type="hidden" name="empresa_id" value="{{ $empresaId }}">@endif
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Recurso</label>
                            <select name="security_crud_resource_id" class="form-control">
                                <option value="">Informar model manualmente</option>
                                @foreach($resources as $resource)
                                    <option value="{{ $resource->id }}">{{ $resource->module }} / {{ $resource->display_name }} — {{ $resource->model_class }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Model manual</label>
                            <input type="text" name="model_class" class="form-control" placeholder="App\Models\Empresa">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Campos sensíveis adicionais</label>
                            <input type="text" name="sanitize_fields" class="form-control" placeholder="campo1,campo2,campo3">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2"><label><input type="checkbox" name="tenant_can_view" value="1" checked> Tenant vê</label></div>
                        <div class="col-md-2"><label><input type="checkbox" name="tenant_can_view_json" value="1"> Tenant vê JSON</label></div>
                        <div class="col-md-2"><label><input type="checkbox" name="tenant_can_export_json" value="1"> Tenant exporta</label></div>
                        <div class="col-md-2"><label><input type="checkbox" name="tenant_can_restore" value="1"> Tenant restaura</label></div>
                        <div class="col-md-2"><label><input type="checkbox" name="super_admin_only" value="1"> Só SuperAdmin</label></div>
                        <div class="col-md-2"><label><input type="checkbox" name="enabled" value="1" checked> Ativa</label></div>
                    </div>
                    <button class="btn btn-primary mt-3">Salvar política</button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Model</th>
                        <th>Tenant vê</th>
                        <th>JSON</th>
                        <th>Exporta</th>
                        <th>Restaura</th>
                        <th>Só SuperAdmin</th>
                        <th>Campos</th>
                        <th width="90">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($policies as $policy)
                        <tr>
                            <td>{{ optional($policy->empresa)->nome ?? 'Global' }}</td>
                            <td><small>{{ $policy->model_class }}</small></td>
                            <td>{{ $policy->tenant_can_view ? 'Sim' : 'Não' }}</td>
                            <td>{{ $policy->tenant_can_view_json ? 'Sim' : 'Não' }}</td>
                            <td>{{ $policy->tenant_can_export_json ? 'Sim' : 'Não' }}</td>
                            <td>{{ $policy->tenant_can_restore ? 'Sim' : 'Não' }}</td>
                            <td>{{ $policy->super_admin_only ? 'Sim' : 'Não' }}</td>
                            <td><small>{{ implode(', ', $policy->sanitize_fields ?: []) }}</small></td>
                            <td>
                                <form method="post" action="/seguranca/auditoria/politicas/{{ $policy->id }}" onsubmit="return confirm('Remover política?')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-sm btn-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">Nenhuma política cadastrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
