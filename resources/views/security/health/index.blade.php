@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Diagnóstico da Segurança de Operações</h3>
                <p class="text-muted mb-0">Painel técnico para acompanhar parametrização, migração legada e pendências por empresa.</p>
            </div>
            <div>
                <a href="/seguranca/admin/painel-global" class="btn btn-light">Painel Global</a>
                <a href="/seguranca/admin/empresas" class="btn btn-primary">Empresas</a>
            </div>
        </div>

        @php($summary = $diagnostic['summary'])
        <div class="row mb-4">
            <div class="col-md-2"><div class="alert alert-light"><strong>Empresas</strong><br>{{ $summary['companies_total'] }}</div></div>
            <div class="col-md-2"><div class="alert alert-info"><strong>Habilitadas</strong><br>{{ $summary['tenant_enabled'] }}</div></div>
            <div class="col-md-2"><div class="alert alert-success"><strong>Setup OK</strong><br>{{ $summary['setup_completed'] }}</div></div>
            <div class="col-md-2"><div class="alert alert-primary"><strong>Enforcement</strong><br>{{ $summary['enforcement_enabled'] }}</div></div>
            <div class="col-md-2"><div class="alert alert-warning"><strong>Legado ativo</strong><br>{{ $summary['legacy_active'] }}</div></div>
            <div class="col-md-2"><div class="alert alert-danger"><strong>Críticas</strong><br>{{ $summary['with_critical_issues'] }}</div></div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3"><div class="card border"><div class="card-body"><strong>Recursos detectados</strong><br>{{ $summary['resources_total'] }}</div></div></div>
            <div class="col-md-3"><div class="card border"><div class="card-body"><strong>Recursos sensíveis</strong><br>{{ $summary['resources_sensitive'] }}</div></div></div>
            <div class="col-md-3"><div class="card border"><div class="card-body"><strong>Somente SuperAdmin</strong><br>{{ $summary['resources_super_admin_only'] }}</div></div></div>
            <div class="col-md-3"><div class="card border"><div class="card-body"><strong>Políticas globais de auditoria</strong><br>{{ $summary['global_audit_policies'] }}</div></div></div>
        </div>

        @if($diagnostic['resources']['without_global_audit_policy']->count() > 0)
            <div class="alert alert-warning">
                <strong>Atenção:</strong> existem {{ $diagnostic['resources']['without_global_audit_policy']->count() }} recursos sem política global de auditoria.
                <a href="/seguranca/auditoria/politicas" class="alert-link">Configurar políticas</a>.
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>CNPJ</th>
                        <th>Score</th>
                        <th>Status</th>
                        <th>Segurança</th>
                        <th>Enforcement</th>
                        <th>Legado</th>
                        <th>Permissões</th>
                        <th>Proteções críticas</th>
                        <th>Autorizadores</th>
                        <th>Tokens</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($diagnostic['rows'] as $row)
                        @php($empresa = $row['empresa'])
                        <tr>
                            <td>{{ $empresa->nome }}</td>
                            <td>{{ $empresa->cnpj }}</td>
                            <td><strong>{{ $row['score'] }}%</strong></td>
                            <td>
                                @if($row['health_level'] === 'danger')
                                    <span class="badge badge-danger">Crítico</span>
                                @elseif($row['health_level'] === 'warning')
                                    <span class="badge badge-warning">Atenção</span>
                                @else
                                    <span class="badge badge-success">OK</span>
                                @endif
                            </td>
                            <td>{{ $row['tenant_enabled'] ? 'Habilitada' : 'Desabilitada' }}</td>
                            <td>{{ $row['enforcement_enabled'] ? 'Ativo' : 'Inativo' }}</td>
                            <td>{{ $row['legacy_active'] ? 'Ativo' : 'Desativado' }}</td>
                            <td>{{ $row['permissions_count'] }}</td>
                            <td>{{ $row['protected_critical_rules'] }}</td>
                            <td>{{ $row['authorizers_count'] }}</td>
                            <td>{{ $row['active_tokens_count'] }}</td>
                            <td>
                                <a class="btn btn-sm btn-light" href="/seguranca/diagnostico/empresas/{{ $empresa->id }}">Diagnóstico</a>
                                <a class="btn btn-sm btn-light-primary" href="/seguranca/admin/empresas/{{ $empresa->id }}">Painel</a>
                            </td>
                        </tr>
                        @if(count($row['issues']) || count($row['warnings']))
                            <tr>
                                <td colspan="12">
                                    @foreach($row['issues'] as $issue)
                                        <div class="text-danger">• {{ $issue }}</div>
                                    @endforeach
                                    @foreach($row['warnings'] as $warning)
                                        <div class="text-warning">• {{ $warning }}</div>
                                    @endforeach
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
