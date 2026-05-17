@extends('default.layout')

@section('content')
<link rel="stylesheet" href="/css/security-admin.css">

@php
    $activeQuery = request()->query();
    $scopeLabel = 'Regra geral da empresa';
    if(($scope['scope_type'] ?? 'geral') === 'perfil') $scopeLabel = 'Perfil específico';
    if(($scope['scope_type'] ?? 'geral') === 'usuario') $scopeLabel = 'Usuário específico';
@endphp

<div class="security-admin-page security-rules-page">
    @if(session('mensagem_sucesso'))<div class="alert alert-success py-2">{{ session('mensagem_sucesso') }}</div>@endif
    @if(session('mensagem_erro'))<div class="alert alert-danger py-2">{{ session('mensagem_erro') }}</div>@endif

    <div class="sa-hero sa-hero-compact mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start">
            <div class="mb-2">
                <span class="sa-chip"><i class="la la-shield-alt"></i> Regras unificadas</span>
                <span class="sa-chip"><i class="la la-user-lock"></i> {{ $scopeLabel }}</span>
                @if($empresaId)<span class="sa-chip"><i class="la la-building"></i> Empresa #{{ $empresaId }}</span>@endif
                <h2 class="sa-title mt-2">Permissões CRUD + Proteções por autenticação</h2>
                <p class="sa-subtitle mb-0">Configure em um único lugar o que cada módulo pode fazer e qual método será exigido para visualizar, criar, editar, excluir, restaurar, exportar ou imprimir.</p>
            </div>
            <div class="sa-actions mt-2">
                <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalCleanupRules"><i class="la la-broom"></i> Limpar regras</button>
                <form method="post" action="/seguranca/regras/recriar-padrao" onsubmit="return confirm('Recriar regras padrão para o escopo atual? As regras automáticas atuais podem ser substituídas.')">
                    @csrf
                    @foreach($activeQuery as $key => $value)
                        @if(!is_array($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <input type="hidden" name="clear_auto_before" value="1">
                    <button class="btn btn-sm btn-light-primary"><i class="la la-sync"></i> Recriar padrão</button>
                </form>
                <a href="/seguranca/administracao{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="btn btn-sm btn-light"><i class="la la-arrow-left"></i> Administração</a>
            </div>
        </div>
    </div>

    <div class="sa-card mb-3"><div class="sa-card-body sa-card-body-compact">
        <form method="get" action="/seguranca/regras" class="sa-compact-filter">
            <div class="row align-items-end">
                @if($isSuper)
                    <div class="col-lg-3 col-md-6 mb-2">
                        <label>Empresa</label>
                        <select name="empresa_id" class="form-control form-control-sm">
                            <option value="">Regra global/plataforma</option>
                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}" {{ (string)$empresaId === (string)$empresa->id ? 'selected' : '' }}>{{ $empresa->nome }} - {{ $empresa->cnpj }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-lg-2 col-md-6 mb-2">
                    <label>Módulo</label>
                    <select name="module" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach($modules as $module)
                            <option value="{{ $module }}" {{ request('module') === $module ? 'selected' : '' }}>{{ $module }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6 mb-2">
                    <label>Escopo</label>
                    <select name="scope_type" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="geral" {{ ($scope['scope_type'] ?? 'geral') === 'geral' ? 'selected' : '' }}>Geral da empresa</option>
                        <option value="perfil" {{ ($scope['scope_type'] ?? '') === 'perfil' ? 'selected' : '' }}>Perfil</option>
                        <option value="usuario" {{ ($scope['scope_type'] ?? '') === 'usuario' ? 'selected' : '' }}>Usuário</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6 mb-2" style="display: {{ ($scope['scope_type'] ?? '') === 'perfil' ? 'block' : 'none' }}">
                    <label>Perfil</label>
                    <select name="perfil_acesso_id" class="form-control form-control-sm">
                        <option value="">Selecione</option>
                        @foreach($perfis as $perfil)
                            <option value="{{ $perfil->id }}" {{ (string)($scope['perfil_acesso_id'] ?? '') === (string)$perfil->id ? 'selected' : '' }}>{{ $perfil->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6 mb-2" style="display: {{ ($scope['scope_type'] ?? '') === 'usuario' ? 'block' : 'none' }}">
                    <label>Usuário</label>
                    <select name="usuario_id" class="form-control form-control-sm">
                        <option value="">Selecione</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" {{ (string)($scope['usuario_id'] ?? '') === (string)$usuario->id ? 'selected' : '' }}>{{ $usuario->nome }} {{ $usuario->login ? '(' . $usuario->login . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6 mb-2">
                    <label>Busca</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Módulo/recurso/model">
                </div>
                <div class="col-lg-1 col-md-6 mb-2">
                    <button class="btn btn-sm btn-primary btn-block">Filtrar</button>
                </div>
            </div>
        </form>
    </div></div>

    <div class="sa-card"><div class="sa-card-body sa-card-body-compact">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
            <div>
                <h4 class="sa-section-title mb-0">Matriz de regras por módulo</h4>
                <small class="text-muted">Cada linha une permissões CRUD e proteção por método de autenticação.</small>
            </div>
            <div class="text-muted small">{{ $matrix->count() }} recurso(s) carregado(s)</div>
        </div>

        <div class="table-responsive sa-table-wrap">
            <table class="table table-sm table-bordered table-hover sa-table sa-table-compact">
                <thead>
                    <tr>
                        <th style="min-width:220px">Módulo/Recurso</th>
                        @foreach($actions as $action => $label)
                            <th class="text-center">{{ $label }}</th>
                        @endforeach
                        <th class="text-center">Status</th>
                        <th style="width:170px">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($matrix as $row)
                        @php($resource = $row['resource'])
                        @php($permission = $row['permission'])
                        <tr>
                            <td>
                                <strong>{{ $resource->plural_display_name ?: $resource->display_name }}</strong>
                                <div class="text-muted small">{{ $resource->module ?: 'Geral' }}</div>
                                @if($resource->sensitive)<span class="sa-badge sa-badge-warning">Sensível</span>@endif
                                @if($permission)<span class="sa-badge sa-badge-info">{{ $permission->source ?: 'manual' }}</span>@else<span class="sa-badge sa-badge-secondary">padrão</span>@endif
                            </td>
                            @foreach($actions as $action => $label)
                                @php($item = $row['actions'][$action])
                                <td class="text-center sa-rule-cell">
                                    <span class="sa-dot {{ $item['can'] ? 'sa-dot-ok' : 'sa-dot-off' }}" title="{{ $item['can'] ? 'Permitido' : 'Bloqueado' }}"></span>
                                    @if($item['protection_enabled'] && $item['protection_type'] !== 'none')
                                        <span class="sa-badge sa-badge-info d-block mt-1">{{ $protectionTypes[$item['protection_type']] ?? $item['protection_type'] }}</span>
                                    @else
                                        <span class="text-muted small d-block mt-1">livre</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-center">
                                @if($permission && !$permission->enabled)
                                    <span class="sa-badge sa-badge-secondary">Permissão inativa</span>
                                @elseif($row['protected_count'] > 0)
                                    <span class="sa-badge sa-badge-success">{{ $row['protected_count'] }} protegida(s)</span>
                                @else
                                    <span class="sa-badge sa-badge-warning">Sem proteção</span>
                                @endif
                                @if($row['denied_count'] > 0)<span class="sa-badge sa-badge-danger mt-1">{{ $row['denied_count'] }} bloqueada(s)</span>@endif
                            </td>
                            <td>
                                <button class="btn btn-xs btn-primary mb-1" data-toggle="modal" data-target="#modalRuleResource{{ $resource->id }}">Editar regra</button>
                                <div class="btn-group btn-group-sm">
                                    @if($permission)
                                        <form method="post" action="/seguranca/regras/permissao/{{ $permission->id }}" onsubmit="return confirm('Excluir a permissão CRUD deste recurso/escopo?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-xs btn-outline-danger">Excluir permissão</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($actions) + 3 }}" class="text-center text-muted">Nenhum recurso encontrado para os filtros atuais.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="sa-mobile-rules">
            @foreach($matrix as $row)
                @php($resource = $row['resource'])
                <div class="sa-mobile-card">
                    <div class="d-flex justify-content-between"><strong>{{ $resource->plural_display_name ?: $resource->display_name }}</strong><button class="btn btn-xs btn-primary" data-toggle="modal" data-target="#modalRuleResource{{ $resource->id }}">Editar</button></div>
                    <small class="text-muted">{{ $resource->module ?: 'Geral' }}</small>
                    <div class="mt-2">
                        @foreach($actions as $action => $label)
                            @php($item = $row['actions'][$action])
                            <span class="sa-badge {{ $item['can'] ? 'sa-badge-info' : 'sa-badge-secondary' }} mb-1">{{ $label }}: {{ $item['can'] ? 'Sim' : 'Não' }}</span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div></div>

    @foreach($matrix as $row)
        @php($resource = $row['resource'])
        @php($permission = $row['permission'])
        <div class="modal fade" id="modalRuleResource{{ $resource->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <form method="post" action="/seguranca/regras">
                        @csrf
                        <input type="hidden" name="security_crud_resource_id" value="{{ $resource->id }}">
                        <input type="hidden" name="empresa_id" value="{{ $empresaId }}">
                        <input type="hidden" name="scope_type" value="{{ $scope['scope_type'] }}">
                        <input type="hidden" name="perfil_acesso_id" value="{{ $scope['perfil_acesso_id'] }}">
                        <input type="hidden" name="usuario_id" value="{{ $scope['usuario_id'] }}">
                        <input type="hidden" name="source" value="manual">
                        <div class="modal-header py-3">
                            <div>
                                <h5 class="modal-title mb-0">Editar regra: {{ $resource->plural_display_name ?: $resource->display_name }}</h5>
                                <small class="text-muted">Permissões e autenticação lado a lado para o escopo atual.</small>
                            </div>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <label class="checkbox checkbox-outline checkbox-primary mb-3">
                                <input type="checkbox" name="permission_enabled" value="1" {{ !$permission || $permission->enabled ? 'checked' : '' }}>
                                <span></span>&nbsp;Regra de permissão ativa
                            </label>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered sa-table sa-table-compact">
                                    <thead>
                                        <tr>
                                            <th>Ação</th>
                                            <th class="text-center">Permitir CRUD</th>
                                            <th>Método de autenticação/proteção</th>
                                            <th class="text-center">Proteção ativa</th>
                                            <th>Mensagem para o usuário</th>
                                            <th class="text-center">Bypass</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($actions as $action => $label)
                                            @php($item = $row['actions'][$action])
                                            @php($rule = $item['protection'])
                                            <tr>
                                                <td><strong>{{ $label }}</strong><br><small class="text-muted">{{ $action }}</small></td>
                                                <td class="text-center">
                                                    <label class="checkbox checkbox-outline checkbox-primary justify-content-center">
                                                        <input type="checkbox" name="permissions[{{ $action }}]" value="1" {{ $item['can'] ? 'checked' : '' }}>
                                                        <span></span>
                                                    </label>
                                                </td>
                                                <td>
                                                    <select name="protections[{{ $action }}][protection_type]" class="form-control form-control-sm">
                                                        @foreach($protectionTypes as $type => $typeLabel)
                                                            <option value="{{ $type }}" {{ $item['protection_type'] === $type ? 'selected' : '' }}>{{ $typeLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="text-center">
                                                    <label class="checkbox checkbox-outline checkbox-primary justify-content-center">
                                                        <input type="checkbox" name="protections[{{ $action }}][enabled]" value="1" {{ $item['protection_enabled'] ? 'checked' : '' }}>
                                                        <span></span>
                                                    </label>
                                                </td>
                                                <td><input type="text" name="protections[{{ $action }}][message]" value="{{ optional($rule)->message }}" class="form-control form-control-sm" placeholder="Mensagem opcional"></td>
                                                <td>
                                                    <label class="checkbox checkbox-outline checkbox-primary d-block mb-1 small"><input type="checkbox" name="protections[{{ $action }}][bypass_super_admin]" value="1" {{ !$rule || $rule->bypass_super_admin ? 'checked' : '' }}><span></span> Super</label>
                                                    <label class="checkbox checkbox-outline checkbox-primary d-block mb-1 small"><input type="checkbox" name="protections[{{ $action }}][bypass_company_admin]" value="1" {{ $rule && $rule->bypass_company_admin ? 'checked' : '' }}><span></span> Admin empresa</label>
                                                    <label class="checkbox checkbox-outline checkbox-primary d-block small"><input type="checkbox" name="protections[{{ $action }}][allow_self_authorization]" value="1" {{ $rule && $rule->allow_self_authorization ? 'checked' : '' }}><span></span> Autoautorizar</label>
                                                    @if($rule)
                                                        <button type="submit" form="deleteProtection{{ $rule->id }}" class="btn btn-xs btn-outline-danger mt-2" onclick="return confirm('Excluir apenas a proteção desta ação?')">Excluir proteção</button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="alert alert-light border py-2 mt-2 mb-0">
                                <strong>Impacto:</strong> se a permissão estiver desmarcada, a ação é bloqueada. Se a permissão estiver marcada e a proteção ativa, o sistema solicitará o método configurado quando o enforcement estiver ativo.
                            </div>
                        </div>
                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">Cancelar</button>
                            <button class="btn btn-sm btn-primary">Salvar regra unificada</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    @foreach($matrix as $row)
        @foreach($row['actions'] as $action => $item)
            @if($item['protection'])
                <form id="deleteProtection{{ $item['protection']->id }}" method="post" action="/seguranca/regras/protecao/{{ $item['protection']->id }}" style="display:none">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        @endforeach
    @endforeach

    <div class="modal fade" id="modalCleanupRules" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document"><div class="modal-content">
            <form method="post" action="/seguranca/regras/limpar" onsubmit="return confirm('Confirma limpar as regras selecionadas? Esta ação não remove recursos, usuários nem logs.')">
                @csrf
                <div class="modal-header py-3"><h5 class="modal-title">Limpar regras</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                <div class="modal-body">
                    @foreach($activeQuery as $key => $value)
                        @if(!is_array($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                    @endforeach
                    <div class="alert alert-warning py-2">A limpeza respeita empresa, recurso e escopo filtrados. Use “Todas” apenas quando quiser remover manual e automático.</div>
                    <label>Origem</label>
                    <select name="source" class="form-control form-control-sm mb-3">
                        <option value="auto">Somente regras automáticas</option>
                        <option value="manual">Somente regras manuais</option>
                        <option value="all">Todas as regras do escopo</option>
                    </select>
                    <label class="checkbox checkbox-outline checkbox-primary d-block mb-2"><input type="checkbox" name="clean_permissions" value="1" checked><span></span> Limpar permissões CRUD</label>
                    <label class="checkbox checkbox-outline checkbox-primary d-block"><input type="checkbox" name="clean_protections" value="1" checked><span></span> Limpar proteções por autenticação</label>
                </div>
                <div class="modal-footer py-2"><button type="button" class="btn btn-sm btn-light" data-dismiss="modal">Cancelar</button><button class="btn btn-sm btn-danger">Limpar regras</button></div>
            </form>
        </div></div>
    </div>
</div>
@endsection
