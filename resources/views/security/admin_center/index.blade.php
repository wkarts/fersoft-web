@extends('default.layout')

@section('content')
<link rel="stylesheet" href="/css/security-admin.css">

@php
    $statusBadge = [
        'success' => 'sa-badge-success',
        'warning' => 'sa-badge-warning',
        'danger' => 'sa-badge-danger',
        'secondary' => 'sa-badge-secondary',
        'info' => 'sa-badge-info',
    ];
@endphp

<div class="security-admin-page">
    @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
    @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

    <div class="sa-hero mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="mb-2">
                    <span class="sa-chip"><i class="la la-shield"></i> Administração unificada</span>
                    <span class="sa-chip"><i class="la la-building"></i> {{ $isSuper && $empresaId ? 'Empresa filtrada #' . $empresaId : ($isSuper ? 'Visão SuperAdmin' : 'Tenant atual') }}</span>
                    <span class="sa-badge {{ $statusBadge[$summary['status_class']] ?? 'sa-badge-secondary' }}">{{ $summary['status_label'] }}</span>
                </div>
                <h2 class="sa-title">Segurança, autenticação, CRUD protegido e auditoria</h2>
                <p class="sa-subtitle">
                    Configure autenticação, permissões, proteções de edição/exclusão, autorizadores, tokens e auditoria em uma única área administrativa. As telas antigas continuam disponíveis, mas esta visão resume o impacto de cada configuração.
                </p>
            </div>
            <div class="col-lg-4 mt-3 mt-lg-0">
                @if($isSuper)
                    <form method="get" action="/seguranca/administracao" class="sa-filter">
                        <label class="font-weight-bold">Visualizar empresa</label>
                        <select name="empresa_id" class="form-control mb-2">
                            <option value="">Visão geral / plataforma</option>
                            @foreach($empresas as $empresa)
                                <option value="{{ $empresa->id }}" {{ (string)$empresaId === (string)$empresa->id ? 'selected' : '' }}>{{ $empresa->nome }} - {{ $empresa->cnpj }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary btn-block">Aplicar visão</button>
                    </form>
                @else
                    <div class="sa-help-box">
                        <strong>Como usar</strong><br>
                        Ative o recurso no assistente, cadastre autorizadores, configure métodos de autenticação e só depois ative a aplicação das regras.
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(!$migrationsReady)
        <div class="alert alert-danger">
            <strong>Migrations pendentes.</strong> A área administrativa foi carregada em modo seguro. Tabelas ausentes: {{ implode(', ', $missingTables) }}.
        </div>
    @endif

    <div class="row mb-4">
        @foreach([
            ['label' => 'Recursos', 'value' => $summary['resources_total'], 'icon' => 'la-cubes', 'help' => 'Models/módulos detectados'],
            ['label' => 'Regras', 'value' => $summary['rules_total'], 'icon' => 'la-lock', 'help' => 'Proteções cadastradas'],
            ['label' => 'Permissões', 'value' => $summary['permissions_total'], 'icon' => 'la-user-check', 'help' => 'CRUD por usuário/perfil'],
            ['label' => 'Autorizadores', 'value' => $summary['authorizers_total'], 'icon' => 'la-user-shield', 'help' => 'Usuários que liberam ações'],
            ['label' => 'Tokens ativos', 'value' => $summary['tokens_total'], 'icon' => 'la-key', 'help' => 'Tokens operacionais'],
            ['label' => 'Logs recentes', 'value' => $summary['logs_total'], 'icon' => 'la-history', 'help' => 'Auditoria filtrada'],
        ] as $item)
            <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                <div class="sa-card">
                    <div class="sa-card-body sa-kpi">
                        <div>
                            <span>{{ $item['label'] }}</span>
                            <h4>{{ $item['value'] }}</h4>
                            <small class="text-muted">{{ $item['help'] }}</small>
                        </div>
                        <div class="sa-icon"><i class="la {{ $item['icon'] }}"></i></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="sa-tabs mb-4" role="tablist">
        <button class="sa-tab active" data-sa-tab="visao">Visão Geral</button>
        <button class="sa-tab" data-sa-tab="autenticacao">Autenticação</button>
        <button class="sa-tab" data-sa-tab="crud">Módulos</button>
        <button class="sa-tab" data-sa-tab="regras">Regras Unificadas</button>
        <button class="sa-tab" data-sa-tab="autorizadores">Autorizadores</button>
        <button class="sa-tab" data-sa-tab="auditoria">Auditoria</button>
        <button class="sa-tab" data-sa-tab="guias">Guias</button>
    </div>

    <section class="sa-section active" id="sa-section-visao">
        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="sa-card"><div class="sa-card-body">
                    <h4 class="sa-section-title">Status da configuração</h4>
                    <p class="sa-section-help">Resumo do que está ativo e qual impacto isso causa no sistema.</p>
                    <div class="sa-mini-grid">
                        <div class="sa-mini-item"><strong>Segurança habilitada</strong>{{ $setting->tenant_enabled ? 'Sim' : 'Não' }}</div>
                        <div class="sa-mini-item"><strong>Aplicação das regras</strong>{{ $setting->enforcement_enabled ? 'Ativa' : 'Inativa' }}</div>
                        <div class="sa-mini-item"><strong>Google Authenticator</strong>{{ $setting->google_auth_required ? 'Recomendado/ativo no setup' : 'Opcional' }}</div>
                        <div class="sa-mini-item"><strong>Senha legada</strong>{{ $setting->legacy_password_disabled ? 'Desabilitada' : 'Ainda disponível' }}</div>
                        <div class="sa-mini-item"><strong>Exportação JSON</strong>{{ $setting->audit_sensitive_export_enabled ? 'Permitida conforme política' : 'Bloqueada para tenant' }}</div>
                        <div class="sa-mini-item"><strong>Restauração por auditoria</strong>{{ $setting->restore_from_audit_enabled ? 'Permitida conforme política' : 'Desabilitada' }}</div>
                    </div>
                    <div class="sa-actions mt-4">
                        <a href="/seguranca/setup" class="btn btn-primary"><i class="la la-magic"></i> Abrir assistente</a>
                        <a href="/seguranca/configuracoes" class="btn btn-light-primary"><i class="la la-cog"></i> Configurações avançadas</a>
                        <a href="/seguranca/diagnostico" class="btn btn-light"><i class="la la-stethoscope"></i> Diagnóstico</a>
                    </div>
                </div></div>
            </div>
            <div class="col-lg-5 mb-4">
                <div class="sa-card"><div class="sa-card-body">
                    <h4 class="sa-section-title">Orientações importantes</h4>
                    <p class="sa-section-help">Pontos que merecem atenção antes de ativar o enforcement.</p>
                    @foreach($tips as $tip)
                        <div class="alert alert-light border mb-2"><i class="la la-info-circle text-primary"></i> {{ $tip }}</div>
                    @endforeach
                </div></div>
            </div>
        </div>
    </section>

    <section class="sa-section" id="sa-section-autenticacao">
        <div class="row">
            <div class="col-lg-5 mb-4">
                <div class="sa-card"><div class="sa-card-body">
                    <h4 class="sa-section-title">Métodos de autenticação</h4>
                    <p class="sa-section-help">Defina quais métodos podem ser usados para aprovar ações protegidas.</p>
                    <form method="post" action="/seguranca/configuracoes">
                        @csrf
                        @if($isSuper && $empresaId)<input type="hidden" name="empresa_id" value="{{ $empresaId }}">@endif
                        @foreach([
                            'tenant_enabled' => 'Segurança de Operações habilitada',
                            'enforcement_enabled' => 'Aplicar regras em produção',
                            'google_auth_required' => 'Recomendar Google Authenticator no setup',
                            'legacy_password_disabled' => 'Desabilitar senha legada',
                            'audit_sensitive_export_enabled' => 'Permitir exportação JSON pela auditoria',
                            'restore_from_audit_enabled' => 'Permitir restauração por auditoria',
                        ] as $field => $label)
                            <label class="checkbox checkbox-outline checkbox-primary d-block mb-3">
                                <input type="checkbox" name="{{ $field }}" value="1" {{ $setting->{$field} ? 'checked' : '' }}>
                                <span></span>&nbsp;{{ $label }}
                            </label>
                        @endforeach
                        <label class="checkbox checkbox-outline checkbox-primary d-block mb-3">
                            <input type="checkbox" name="setup_completed" value="1" {{ $setting->setup_completed_at ? 'checked' : '' }}>
                            <span></span>&nbsp;Configuração inicial concluída
                        </label>
                        <button class="btn btn-primary">Salvar configuração geral</button>
                    </form>
                </div></div>
            </div>
            <div class="col-lg-7 mb-4">
                <div class="sa-card"><div class="sa-card-body">
                    <h4 class="sa-section-title">Impacto das opções</h4>
                    <div class="table-responsive sa-table-wrap">
                        <table class="table sa-table table-bordered">
                            <thead><tr><th>Opção</th><th>Impacto</th><th>Recomendação</th></tr></thead>
                            <tbody>
                                <tr><td>Google Authenticator</td><td>Código gerado no dispositivo do autorizador.</td><td><span class="sa-badge sa-badge-success">Recomendado</span></td></tr>
                                <tr><td>Token operacional</td><td>Token separado do login, útil como método complementar.</td><td><span class="sa-badge sa-badge-info">Complementar</span></td></tr>
                                <tr><td>Token temporário</td><td>Gerado após aprovação e usado uma única vez na ação final.</td><td><span class="sa-badge sa-badge-success">Mantido</span></td></tr>
                                <tr><td>Senha legada</td><td>Compatibilidade com fluxos antigos de liberação.</td><td><span class="sa-badge sa-badge-warning">Migrar aos poucos</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="sa-help-box mt-3">O administrador pode manter token operacional, token temporário, App Auth e demais métodos. O App Auth é o padrão recomendado, mas não limita o sistema a um único método.</div>
                </div></div>
            </div>
        </div>
    </section>

    <section class="sa-section" id="sa-section-crud">
        <div class="sa-card"><div class="sa-card-body">
            <h4 class="sa-section-title">Controle de CRUD por módulo</h4>
            <p class="sa-section-help">Veja rapidamente quais módulos possuem recursos, permissões e ações protegidas.</p>
            <div class="row">
                @forelse($resourcesByModule as $module)
                    <div class="col-xl-4 col-lg-6 mb-3">
                        <div class="sa-card"><div class="sa-card-body">
                            <h5 class="font-weight-bold mb-2">{{ $module['module'] }}</h5>
                            <div class="sa-mini-grid">
                                <div class="sa-mini-item"><strong>Recursos</strong>{{ $module['resources_count'] }}</div>
                                <div class="sa-mini-item"><strong>Permissões</strong>{{ $module['permissions_count'] }}</div>
                                <div class="sa-mini-item"><strong>Regras</strong>{{ $module['rules_count'] }}</div>
                                <div class="sa-mini-item"><strong>Ações protegidas</strong>{{ $module['protected_actions'] }}</div>
                            </div>
                            <div class="mt-3">
                                @foreach($module['resources']->take(5) as $resource)
                                    <span class="sa-badge {{ $resource->sensitive ? 'sa-badge-warning' : 'sa-badge-info' }} mb-1">{{ $resource->plural_display_name ?: $resource->display_name }}</span>
                                @endforeach
                                @if($module['resources']->count() > 5)<span class="text-muted">+{{ $module['resources']->count() - 5 }}</span>@endif
                            </div>
                        </div></div>
                    </div>
                @empty
                    <div class="col-12"><div class="sa-empty">Nenhum recurso sincronizado. Use o assistente ou a tela de recursos.</div></div>
                @endforelse
            </div>
            <div class="sa-actions mt-3">
                <a href="/seguranca/recursos" class="btn btn-light-primary">Gerenciar recursos</a>
                <a href="/seguranca/regras" class="btn btn-light">Regras unificadas</a>
                <a href="/seguranca/regras" class="btn btn-light">Editar permissões/proteções</a>
            </div>
        </div></div>
    </section>

    <section class="sa-section" id="sa-section-regras">
        <div class="sa-card"><div class="sa-card-body">
            <h4 class="sa-section-title">Permissões e regras de segurança</h4>
            <p class="sa-section-help">A administração principal agora é feita em uma matriz única por módulo, juntando permissões CRUD e métodos de autenticação no mesmo lugar.</p>
            <div class="table-responsive sa-table-wrap">
                <table class="table table-bordered table-hover sa-table">
                    <thead><tr><th>Recurso</th><th>Ação</th><th>Método</th><th>Escopo</th><th>Status</th><th>Ações</th></tr></thead>
                    <tbody>
                        @forelse($rules as $rule)
                            <tr>
                                <td><strong>{{ optional($rule->resource)->plural_display_name ?: optional($rule->resource)->display_name }}</strong><br><small class="text-muted">{{ optional($rule->resource)->module }}</small></td>
                                <td>{{ $actions[$rule->action] ?? $rule->action }}</td>
                                <td><span class="sa-badge sa-badge-info">{{ $protectionTypes[$rule->protection_type] ?? $rule->protection_type }}</span></td>
                                <td>{{ $rule->empresa_id ? 'Empresa/Tenant' : 'Global' }}</td>
                                <td><span class="sa-badge {{ $rule->enabled ? 'sa-badge-success' : 'sa-badge-secondary' }}">{{ $rule->enabled ? 'Ativa' : 'Inativa' }}</span></td>
                                <td><button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalRule{{ $rule->id }}">Editar</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">Nenhuma regra configurada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @foreach($rules as $rule)
                <div class="sa-mobile-card">
                    <strong>{{ optional($rule->resource)->plural_display_name ?: optional($rule->resource)->display_name }}</strong><br>
                    {{ $actions[$rule->action] ?? $rule->action }} · {{ $protectionTypes[$rule->protection_type] ?? $rule->protection_type }}<br>
                    <button type="button" class="btn btn-sm btn-primary mt-2" data-toggle="modal" data-target="#modalRule{{ $rule->id }}">Editar regra</button>
                </div>
            @endforeach
            <div class="sa-actions mt-3"><a href="/seguranca/regras" class="btn btn-light-primary">Abrir regras unificadas</a></div>
        </div></div>
    </section>

    <section class="sa-section" id="sa-section-autorizadores">
        <div class="row">
            <div class="col-lg-7 mb-4"><div class="sa-card"><div class="sa-card-body">
                <h4 class="sa-section-title">Autorizadores</h4>
                <p class="sa-section-help">Usuários que podem aprovar ações protegidas com App Auth ou token operacional.</p>
                <div class="table-responsive sa-table-wrap">
                    <table class="table table-bordered sa-table">
                        <thead><tr><th>Usuário</th><th>Permissões</th><th>App Auth</th><th>Status</th><th>Ações</th></tr></thead>
                        <tbody>
                        @forelse($authorizers as $authorizer)
                            <tr>
                                <td><strong>{{ optional($authorizer->usuario)->nome ?? 'Usuário #' . $authorizer->usuario_id }}</strong><br><small>{{ optional($authorizer->usuario)->login }}</small></td>
                                <td>
                                    @foreach(['edit'=>'Editar','delete'=>'Excluir','restore'=>'Restaurar','export'=>'Exportar'] as $key => $label)
                                        @if($authorizer->{'can_authorize_'.$key})<span class="sa-badge sa-badge-info mb-1">{{ $label }}</span>@endif
                                    @endforeach
                                </td>
                                <td><span class="sa-badge {{ $authorizer->hasOperationOtp() ? 'sa-badge-success' : 'sa-badge-warning' }}">{{ $authorizer->hasOperationOtp() ? 'Configurado' : 'Pendente' }}</span></td>
                                <td><span class="sa-badge {{ $authorizer->enabled ? 'sa-badge-success' : 'sa-badge-secondary' }}">{{ $authorizer->enabled ? 'Ativo' : 'Inativo' }}</span></td>
                                <td><a class="btn btn-sm btn-light-primary" href="/seguranca/autorizadores/{{ $authorizer->id }}/otp">App Auth</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">Nenhum autorizador cadastrado.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <a href="/seguranca/autorizadores" class="btn btn-light-primary">Gerenciar autorizadores</a>
            </div></div></div>
            <div class="col-lg-5 mb-4"><div class="sa-card"><div class="sa-card-body">
                <h4 class="sa-section-title">Tokens operacionais</h4>
                <p class="sa-section-help">Tokens separados do login, úteis como recurso complementar.</p>
                @forelse($tokens->take(8) as $token)
                    <div class="sa-mini-item mb-2">
                        <strong>{{ $token->name }}</strong>
                        {{ optional(optional($token->authorizer)->usuario)->nome ?? 'Autorizador #' . $token->security_authorizer_id }}<br>
                        <span class="sa-badge {{ $token->enabled ? 'sa-badge-success' : 'sa-badge-secondary' }}">{{ $token->enabled ? 'Ativo' : 'Inativo' }}</span>
                        @if($token->expires_at)<small class="text-muted"> expira em {{ $token->expires_at->format('d/m/Y H:i') }}</small>@endif
                    </div>
                @empty
                    <div class="sa-empty">Nenhum token operacional cadastrado.</div>
                @endforelse
                <a href="/seguranca/tokens" class="btn btn-light-primary mt-3">Gerenciar tokens</a>
            </div></div></div>
        </div>
    </section>

    <section class="sa-section" id="sa-section-auditoria">
        <div class="sa-card"><div class="sa-card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                <div>
                    <h4 class="sa-section-title">Auditoria e histórico</h4>
                    <p class="sa-section-help">Consulte logs, filtre registros, veja detalhes e exporte JSON internamente com os filtros aplicados.</p>
                </div>
                <div class="sa-actions">
                    <a href="/seguranca/auditoria" class="btn btn-light-primary">Abrir auditoria completa</a>
                    <a href="/seguranca/auditoria/exportar-json?{{ http_build_query(array_filter(['empresa_id' => request('empresa_id'), 'data_inicial' => request('audit_data_inicial'), 'data_final' => request('audit_data_final'), 'modelo' => request('audit_modelo')])) }}" class="btn btn-outline-primary">Exportar JSON filtrado</a>
                </div>
            </div>
            <form method="get" action="/seguranca/administracao" class="sa-filter mb-3">
                @if($isSuper && $empresaId)<input type="hidden" name="empresa_id" value="{{ $empresaId }}">@endif
                <div class="row">
                    <div class="col-md-3 mb-2"><label>Data inicial</label><input type="date" name="audit_data_inicial" class="form-control" value="{{ request('audit_data_inicial') }}"></div>
                    <div class="col-md-3 mb-2"><label>Data final</label><input type="date" name="audit_data_final" class="form-control" value="{{ request('audit_data_final') }}"></div>
                    <div class="col-md-3 mb-2"><label>Ação</label><input type="text" name="audit_acao" class="form-control" value="{{ request('audit_acao') }}" placeholder="update, delete..."></div>
                    <div class="col-md-3 mb-2"><label>Recurso/Modelo</label><input type="text" name="audit_modelo" class="form-control" value="{{ request('audit_modelo') }}" placeholder="Cliente, Produto..."></div>
                    <div class="col-12"><button class="btn btn-primary">Filtrar auditoria</button> <a href="/seguranca/administracao{{ $isSuper && $empresaId ? '?empresa_id='.$empresaId : '' }}" class="btn btn-light">Limpar</a></div>
                </div>
            </form>
            <div class="table-responsive sa-table-wrap">
                <table class="table table-bordered table-hover sa-table">
                    <thead><tr><th>Data</th><th>Ação</th><th>Recurso</th><th>Registro</th><th>Usuário</th><th>Origem</th><th>Ações</th></tr></thead>
                    <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</td>
                            <td><span class="sa-badge sa-badge-info">{{ $log->acao }}</span></td>
                            <td><small>{{ class_basename($log->modelo) }}</small></td>
                            <td>{{ $log->registro_id ?? '-' }}</td>
                            <td>{{ optional($log->usuario)->nome ?? $log->usuario_id ?? '-' }}</td>
                            <td>{{ $log->ip_address }}</td>
                            <td><a class="btn btn-sm btn-primary" href="/seguranca/auditoria/{{ $log->id }}">Detalhes</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">Nenhum log encontrado.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @foreach($logs as $log)
                <div class="sa-mobile-card"><strong>{{ $log->acao }}</strong> · {{ optional($log->created_at)->format('d/m/Y H:i') }}<br>{{ class_basename($log->modelo) }} #{{ $log->registro_id ?? '-' }}<br><a class="btn btn-sm btn-primary mt-2" href="/seguranca/auditoria/{{ $log->id }}">Detalhes</a></div>
            @endforeach
        </div></div>
    </section>

    <section class="sa-section" id="sa-section-guias">
        <div class="row">
            @foreach([
                ['t' => 'Ative por etapas', 'd' => 'Use o assistente, configure autorizadores e proteções, teste com enforcement desligado e só depois aplique as regras.'],
                ['t' => 'Edição e exclusão', 'd' => 'Para ações críticas, prefira App Auth do autorizador. Token operacional continua como complemento.'],
                ['t' => 'Auditoria', 'd' => 'O menu aponta para Auditoria. A exportação JSON fica dentro da tela e respeita filtros e políticas.'],
                ['t' => 'SuperAdmin x tenant', 'd' => 'SuperAdmin tem visão global. Tenant administra apenas a própria empresa e suas permissões.'],
            ] as $guide)
                <div class="col-lg-6 mb-3"><div class="sa-card"><div class="sa-card-body"><h5 class="font-weight-bold">{{ $guide['t'] }}</h5><p class="mb-0 text-muted">{{ $guide['d'] }}</p></div></div></div>
            @endforeach
        </div>
        <div class="sa-actions">
            <a href="/docs/seguranca-operacoes/index.html" target="_blank" class="btn btn-primary">Abrir documentação HTML</a>
            <a href="/seguranca/relatorios" class="btn btn-light">Relatórios administrativos</a>
        </div>
    </section>

    @foreach($rules as $rule)
        <div class="modal fade" id="modalRule{{ $rule->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
                <form method="post" action="/seguranca/protecoes">
                    @csrf
                    <input type="hidden" name="security_crud_resource_id" value="{{ $rule->security_crud_resource_id }}">
                    <input type="hidden" name="action" value="{{ $rule->action }}">
                    <input type="hidden" name="empresa_id" value="{{ $rule->empresa_id }}">
                    <div class="modal-header"><h5 class="modal-title">Editar proteção</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                    <div class="modal-body">
                        <div class="alert alert-info">Alterar esta regra muda o método solicitado para {{ $actions[$rule->action] ?? $rule->action }} em {{ optional($rule->resource)->plural_display_name ?: optional($rule->resource)->display_name }}.</div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label>Tipo de proteção</label><select name="protection_type" class="form-control">@foreach($protectionTypes as $type => $label)<option value="{{ $type }}" {{ $rule->protection_type === $type ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                            <div class="col-md-6 mb-3"><label>Mensagem</label><input type="text" name="message" class="form-control" value="{{ $rule->message }}"></div>
                        </div>
                        <div class="row">
                            @foreach(['requires_authorizer'=>'Exige autorizador','allow_self_authorization'=>'Permite autoautorização','bypass_super_admin'=>'SuperAdmin passa direto','bypass_company_admin'=>'Admin da empresa passa direto','enabled'=>'Ativo'] as $field => $label)
                                <div class="col-md-6 mb-2"><label class="checkbox checkbox-outline checkbox-primary"><input type="checkbox" name="{{ $field }}" value="1" {{ $rule->{$field} ? 'checked' : '' }}><span></span>&nbsp;{{ $label }}</label></div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button><button class="btn btn-primary">Salvar alteração</button></div>
                </form>
            </div></div>
        </div>
    @endforeach
</div>

<script>
(function () {
    var tabs = document.querySelectorAll('.security-admin-page .sa-tab');
    var sections = document.querySelectorAll('.security-admin-page .sa-section');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = tab.getAttribute('data-sa-tab');
            tabs.forEach(function (item) { item.classList.remove('active'); });
            sections.forEach(function (section) { section.classList.remove('active'); });
            tab.classList.add('active');
            var section = document.getElementById('sa-section-' + target);
            if (section) section.classList.add('active');
        });
    });
})();
</script>
@endsection
