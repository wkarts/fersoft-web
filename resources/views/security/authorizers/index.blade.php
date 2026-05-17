@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
        @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Autorizadores</h3>
                <p class="text-muted mb-0">Usuários com privilégio para liberar operações protegidas.</p>
            </div>
            <a href="/seguranca" class="btn btn-light">Voltar ao painel</a>
        </div>

        @if($isSuper)
            <form method="get" class="mb-4">
                <div class="row">
                    <div class="col-md-5">
                        <select name="empresa_id" class="form-control">
                            <option value="">Autorizadores globais</option>
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
                <h5 class="mb-3">Novo autorizador</h5>
                <form method="post" action="/seguranca/autorizadores">
                    @csrf
                    @if($isSuper)<input type="hidden" name="empresa_id" value="{{ $empresaId }}">@endif
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label>Usuário autorizador</label>
                            <select name="usuario_id" class="form-control" required>
                                <option value="">Selecione</option>
                                @foreach($usuarios as $usuario)
                                    <option value="{{ $usuario->id }}">{{ $usuario->nome }} {{ $usuario->login ? '(' . $usuario->login . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <label class="checkbox checkbox-outline checkbox-primary mb-3">
                                <input type="checkbox" name="can_authorize_all_resources" value="1">
                                <span></span>&nbsp;Todos os recursos
                            </label>
                        </div>
                        <div class="col-md-2 mb-3 d-flex align-items-end">
                            <label class="checkbox checkbox-outline checkbox-primary mb-3">
                                <input type="checkbox" name="enabled" value="1" checked>
                                <span></span>&nbsp;Ativo
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        @foreach($actions as $action => $label)
                            <div class="col-lg-2 col-md-3 col-sm-4 mb-2">
                                <label class="checkbox checkbox-outline checkbox-primary">
                                    <input type="checkbox" name="can_authorize_{{ $action }}" value="1" {{ in_array($action, ['edit', 'delete']) ? 'checked' : '' }}>
                                    <span></span>&nbsp;{{ $label }}
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div class="alert alert-info mt-3 mb-3">
                        O autorizador define quem pode liberar operações. A liberação pode usar token operacional individual ou código de aplicativo autenticador, conforme a proteção configurada.
                    </div>

                    <button class="btn btn-primary">Salvar autorizador</button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Empresa</th>
                        <th>Tudo</th>
                        <th>Ver</th>
                        <th>Inserir</th>
                        <th>Editar</th>
                        <th>Excluir</th>
                        <th>Restaurar</th>
                        <th>Tokens</th>
                        <th>App autenticador</th>
                        <th>Ativo</th>
                        <th width="260">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($authorizers as $authorizer)
                        <tr>
                            <td><strong>{{ optional($authorizer->usuario)->nome }}</strong><br><small>{{ optional($authorizer->usuario)->login }}</small></td>
                            <td>{{ optional($authorizer->empresa)->nome ?: 'Global' }}</td>
                            <td>{{ $authorizer->can_authorize_all_resources ? 'Sim' : 'Não' }}</td>
                            <td>{{ $authorizer->can_authorize_view ? 'Sim' : 'Não' }}</td>
                            <td>{{ $authorizer->can_authorize_create ? 'Sim' : 'Não' }}</td>
                            <td>{{ $authorizer->can_authorize_edit ? 'Sim' : 'Não' }}</td>
                            <td>{{ $authorizer->can_authorize_delete ? 'Sim' : 'Não' }}</td>
                            <td>{{ $authorizer->can_authorize_restore ? 'Sim' : 'Não' }}</td>
                            <td>{{ $authorizer->tokens->count() }}</td>
                            <td>
                                @if($authorizer->hasOperationOtp())
                                    <span class="badge badge-success">Configurado</span><br>
                                    <small>{{ $authorizer->operation_otp_last_used_at ? 'Último uso: ' . $authorizer->operation_otp_last_used_at->format('d/m/Y H:i') : 'Ainda não usado' }}</small>
                                @elseif($authorizer->operation_otp_secret)
                                    <span class="badge badge-warning">Pendente</span>
                                @else
                                    <span class="badge badge-light">Não configurado</span>
                                @endif
                            </td>
                            <td>{{ $authorizer->enabled ? 'Sim' : 'Não' }}</td>
                            <td>
                                <div class="d-flex flex-wrap">
                                    <a href="/seguranca/autorizadores/{{ $authorizer->id }}/otp" class="btn btn-sm btn-info mr-2 mb-2">App Auth</a>
                                    <form method="post" action="/seguranca/autorizadores/{{ $authorizer->id }}" onsubmit="return confirm('Remover este autorizador?')" class="mb-2">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-center">Nenhum autorizador configurado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $authorizers->links() }}
    </div>
</div>
@endsection
