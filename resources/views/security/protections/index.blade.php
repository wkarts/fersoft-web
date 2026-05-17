@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
        @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Proteções de Operação</h3>
                <p class="text-muted mb-0">Define quando uma ação permitida exige token, OTP, autorizador ou bloqueio.</p>
            </div>
            <a href="/seguranca" class="btn btn-light">Voltar ao painel</a>
        </div>

        @if($isSuper)
            <form method="get" class="mb-4">
                <div class="row">
                    <div class="col-md-5">
                        <select name="empresa_id" class="form-control">
                            <option value="">Regra global da plataforma</option>
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
                <h5 class="mb-3">Nova regra de proteção</h5>
                <form method="post" action="/seguranca/protecoes">
                    @csrf
                    @if($isSuper)<input type="hidden" name="empresa_id" value="{{ $empresaId }}">@endif
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Recurso</label>
                            <select name="security_crud_resource_id" class="form-control" required>
                                <option value="">Selecione</option>
                                @foreach($resources->groupBy('module') as $module => $items)
                                    <optgroup label="{{ $module }}">
                                        @foreach($items as $resource)
                                            <option value="{{ $resource->id }}">{{ $resource->plural_display_name ?: $resource->display_name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>Ação</label>
                            <select name="action" class="form-control" required>
                                @foreach($actions as $action => $label)
                                    <option value="{{ $action }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Tipo de proteção</label>
                            <select name="protection_type" class="form-control" required>
                                @foreach($protectionTypes as $type => $label)
                                    <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Mensagem personalizada</label>
                            <input type="text" name="message" class="form-control" placeholder="Mensagem opcional">
                        </div>
                    </div>

                    <div class="row">
                        @foreach([
                            'requires_authorizer' => 'Exige autorizador',
                            'allow_self_authorization' => 'Permite autoautorização',
                            'bypass_super_admin' => 'SuperAdmin pode passar direto',
                            'bypass_company_admin' => 'Admin da empresa pode passar direto',
                            'enabled' => 'Ativo'
                        ] as $field => $label)
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                <label class="checkbox checkbox-outline checkbox-primary">
                                    <input type="checkbox" name="{{ $field }}" value="1" {{ in_array($field, ['bypass_super_admin', 'enabled']) ? 'checked' : '' }}>
                                    <span></span>&nbsp;{{ $label }}
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="alert alert-warning mt-3 mb-3">
                        Esta regra só será aplicada quando a empresa estiver com Segurança habilitada e aplicação das regras ativa. Até lá, não bloqueia nenhum CRUD existente.
                    </div>

                    <button class="btn btn-primary">Salvar proteção</button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Recurso</th>
                        <th>Ação</th>
                        <th>Proteção</th>
                        <th>Autorizador</th>
                        <th>Bypass</th>
                        <th>Mensagem</th>
                        <th>Ativo</th>
                        <th width="80">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                        <tr>
                            <td><strong>{{ optional($rule->resource)->plural_display_name ?: optional($rule->resource)->display_name }}</strong><br><small>{{ optional($rule->resource)->module }}</small></td>
                            <td>{{ $actions[$rule->action] ?? $rule->action }}</td>
                            <td>{{ $protectionTypes[$rule->protection_type] ?? $rule->protection_type }}</td>
                            <td>{{ $rule->requires_authorizer ? 'Sim' : 'Não' }}</td>
                            <td>
                                Super: {{ $rule->bypass_super_admin ? 'Sim' : 'Não' }}<br>
                                Admin empresa: {{ $rule->bypass_company_admin ? 'Sim' : 'Não' }}
                            </td>
                            <td>{{ $rule->message }}</td>
                            <td>{{ $rule->enabled ? 'Sim' : 'Não' }}</td>
                            <td>
                                <form method="post" action="/seguranca/protecoes/{{ $rule->id }}" onsubmit="return confirm('Remover esta regra?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">Nenhuma proteção configurada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $rules->links() }}
    </div>
</div>
@endsection
