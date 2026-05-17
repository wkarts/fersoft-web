@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
        @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Permissões CRUD</h3>
                <p class="text-muted mb-0">Controle fino de visualizar, inserir, editar, excluir, restaurar, exportar e imprimir por recurso.</p>
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
                <h5 class="mb-3">Nova regra de permissão</h5>
                <form method="post" action="/seguranca/permissoes">
                    @csrf
                    @if($isSuper)
                        <input type="hidden" name="empresa_id" value="{{ $empresaId }}">
                    @endif
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
                        <div class="col-md-4 mb-3">
                            <label>Perfil</label>
                            <select name="perfil_acesso_id" class="form-control">
                                <option value="">Sem perfil específico</option>
                                @foreach($perfis as $perfil)
                                    <option value="{{ $perfil->id }}">{{ $perfil->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Usuário</label>
                            <select name="usuario_id" class="form-control">
                                <option value="">Sem usuário específico</option>
                                @foreach($usuarios as $usuario)
                                    <option value="{{ $usuario->id }}">{{ $usuario->nome }} {{ $usuario->login ? '(' . $usuario->login . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        @foreach($actions as $action => $label)
                            <div class="col-lg-2 col-md-3 col-sm-4 mb-2">
                                <label class="checkbox checkbox-outline checkbox-primary">
                                    <input type="checkbox" name="can_{{ $action }}" value="1" checked>
                                    <span></span>&nbsp;{{ $label }}
                                </label>
                            </div>
                        @endforeach
                        <div class="col-lg-2 col-md-3 col-sm-4 mb-2">
                            <label class="checkbox checkbox-outline checkbox-primary">
                                <input type="checkbox" name="enabled" value="1" checked>
                                <span></span>&nbsp;Ativo
                            </label>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-3">
                        A permissão define se o usuário pode executar a ação. A proteção por token/OTP é configurada em outra tela e só será exigida quando o enforcement estiver ativo.
                    </div>

                    <button class="btn btn-primary">Salvar permissão</button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Recurso</th>
                        <th>Perfil/Usuário</th>
                        <th>Ver</th>
                        <th>Inserir</th>
                        <th>Editar</th>
                        <th>Excluir</th>
                        <th>Restaurar</th>
                        <th>Exportar</th>
                        <th>Imprimir</th>
                        <th>Ativo</th>
                        <th width="80">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($permissions as $permission)
                        <tr>
                            <td>
                                <strong>{{ optional($permission->resource)->plural_display_name ?: optional($permission->resource)->display_name }}</strong><br>
                                <small>{{ optional($permission->resource)->module }}</small>
                            </td>
                            <td>
                                @if($permission->usuario)
                                    Usuário: <strong>{{ $permission->usuario->nome }}</strong>
                                @elseif($permission->perfil)
                                    Perfil: <strong>{{ $permission->perfil->nome }}</strong>
                                @else
                                    Regra geral
                                @endif
                            </td>
                            <td>{{ $permission->can_view ? 'Sim' : 'Não' }}</td>
                            <td>{{ $permission->can_create ? 'Sim' : 'Não' }}</td>
                            <td>{{ $permission->can_edit ? 'Sim' : 'Não' }}</td>
                            <td>{{ $permission->can_delete ? 'Sim' : 'Não' }}</td>
                            <td>{{ $permission->can_restore ? 'Sim' : 'Não' }}</td>
                            <td>{{ $permission->can_export ? 'Sim' : 'Não' }}</td>
                            <td>{{ $permission->can_print ? 'Sim' : 'Não' }}</td>
                            <td>{{ $permission->enabled ? 'Sim' : 'Não' }}</td>
                            <td>
                                <form method="post" action="/seguranca/permissoes/{{ $permission->id }}" onsubmit="return confirm('Remover esta permissão?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center">Nenhuma permissão configurada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $permissions->links() }}
    </div>
</div>
@endsection
