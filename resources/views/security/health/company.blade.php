@extends('default.layout')

@section('content')
@php
    $empresa = $diagnostic['empresa'];
    $row = $diagnostic['row'];
@endphp

<div class="card card-custom gutter-b">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Diagnóstico de Segurança — {{ $empresa->nome }}</h3>
                <p class="text-muted mb-0">Análise detalhada de parametrização, autorizadores, auditoria e migração legada.</p>
            </div>
            <div>
                <a href="/seguranca/diagnostico" class="btn btn-light">Voltar ao diagnóstico</a>
                <a href="/seguranca/admin/empresas/{{ $empresa->id }}" class="btn btn-primary">Painel da empresa</a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-2"><div class="alert alert-light"><strong>Score</strong><br>{{ $row['score'] }}%</div></div>
            <div class="col-md-2"><div class="alert alert-info"><strong>Segurança</strong><br>{{ $row['tenant_enabled'] ? 'Habilitada' : 'Desabilitada' }}</div></div>
            <div class="col-md-2"><div class="alert alert-success"><strong>Setup</strong><br>{{ $row['setup_completed'] ? 'Concluído' : 'Pendente' }}</div></div>
            <div class="col-md-2"><div class="alert alert-primary"><strong>Enforcement</strong><br>{{ $row['enforcement_enabled'] ? 'Ativo' : 'Inativo' }}</div></div>
            <div class="col-md-2"><div class="alert alert-warning"><strong>Legado</strong><br>{{ $row['legacy_active'] ? 'Ativo' : 'Desativado' }}</div></div>
            <div class="col-md-2"><div class="alert alert-secondary"><strong>Recursos</strong><br>{{ $row['visible_resources'] }}</div></div>
        </div>

        @if(count($diagnostic['recommendations']))
            <div class="alert alert-warning">
                <strong>Recomendações:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($diagnostic['recommendations'] as $recommendation)
                        <li>{{ $recommendation }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border h-100">
                    <div class="card-body">
                        <h5>Permissões e Proteções</h5>
                        <p class="mb-1">Permissões cadastradas: <strong>{{ $row['permissions_count'] }}</strong></p>
                        <p class="mb-1">Regras de proteção: <strong>{{ $row['protection_count'] }}</strong></p>
                        <p class="mb-1">Proteções críticas: <strong>{{ $row['protected_critical_rules'] }}</strong></p>
                        <p class="mb-0">Recursos sem permissão: <strong>{{ $diagnostic['resources']['without_permissions']->count() }}</strong></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border h-100">
                    <div class="card-body">
                        <h5>Autorizadores</h5>
                        <p class="mb-1">Autorizadores ativos: <strong>{{ $row['authorizers_count'] }}</strong></p>
                        <p class="mb-1">Tokens ativos: <strong>{{ $row['active_tokens_count'] }}</strong></p>
                        <p class="mb-0">Autorizadores sem token: <strong>{{ $diagnostic['authorizers']['without_active_token']->count() }}</strong></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border h-100">
                    <div class="card-body">
                        <h5>Auditoria</h5>
                        <p class="mb-1">Políticas: <strong>{{ $diagnostic['audit']['policies_total'] }}</strong></p>
                        <p class="mb-1">Exportação JSON permitida: <strong>{{ $diagnostic['audit']['json_export_enabled_count'] }}</strong></p>
                        <p class="mb-1">Restauração permitida: <strong>{{ $diagnostic['audit']['restore_enabled_count'] }}</strong></p>
                        <p class="mb-0">Recursos sem política: <strong>{{ $diagnostic['audit']['without_policy']->count() }}</strong></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body">
                        <h5>Recursos sem permissão CRUD</h5>
                        @forelse($diagnostic['resources']['without_permissions']->take(20) as $resource)
                            <div>• {{ $resource->plural_display_name ?: $resource->display_name }} <small class="text-muted">{{ $resource->model_class }}</small></div>
                        @empty
                            <p class="text-muted mb-0">Nenhuma pendência encontrada.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body">
                        <h5>Recursos sem proteção operacional</h5>
                        @forelse($diagnostic['resources']['without_protection']->take(20) as $resource)
                            <div>• {{ $resource->plural_display_name ?: $resource->display_name }} <small class="text-muted">{{ $resource->model_class }}</small></div>
                        @empty
                            <p class="text-muted mb-0">Nenhuma pendência encontrada.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body">
                        <h5>Autorizadores sem token ativo</h5>
                        @forelse($diagnostic['authorizers']['without_active_token'] as $authorizer)
                            <div>• {{ optional($authorizer->usuario)->nome ?? 'Usuário #' . $authorizer->usuario_id }}</div>
                        @empty
                            <p class="text-muted mb-0">Nenhuma pendência encontrada.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body">
                        <h5>Recursos sem política de auditoria</h5>
                        @forelse($diagnostic['audit']['without_policy']->take(20) as $resource)
                            <div>• {{ $resource->plural_display_name ?: $resource->display_name }} <small class="text-muted">{{ $resource->model_class }}</small></div>
                        @empty
                            <p class="text-muted mb-0">Nenhuma pendência encontrada.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="card border">
            <div class="card-body">
                <h5>Últimas autorizações operacionais</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ação</th>
                                <th>Registro</th>
                                <th>Expira em</th>
                                <th>Usada em</th>
                                <th>Criada em</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($diagnostic['operations']['last'] as $operation)
                                <tr>
                                    <td>{{ $operation->id }}</td>
                                    <td>{{ $operation->action }}</td>
                                    <td>{{ $operation->record_id }}</td>
                                    <td>{{ optional($operation->expires_at)->format('d/m/Y H:i') }}</td>
                                    <td>{{ optional($operation->used_at)->format('d/m/Y H:i') }}</td>
                                    <td>{{ optional($operation->created_at)->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">Nenhuma autorização registrada.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
