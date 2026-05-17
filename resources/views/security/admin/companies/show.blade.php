@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>{{ $empresa->nome }}</h3>
                <p class="text-muted mb-0">Painel administrativo de segurança do tenant.</p>
            </div>
            <a href="/seguranca/diagnostico/empresas/{{ $empresa->id }}" class="btn btn-warning mr-2">Diagnóstico</a>
            <a href="/seguranca/admin/empresas" class="btn btn-light">Voltar</a>
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-6 mb-3"><div class="card bg-light"><div class="card-body"><h5>Recursos</h5><h2>{{ $resourcesCount }}</h2></div></div></div>
            <div class="col-lg-3 col-md-6 mb-3"><div class="card bg-light"><div class="card-body"><h5>Permissões</h5><h2>{{ $permissionsCount }}</h2></div></div></div>
            <div class="col-lg-3 col-md-6 mb-3"><div class="card bg-light"><div class="card-body"><h5>Proteções</h5><h2>{{ $rulesCount }}</h2></div></div></div>
            <div class="col-lg-3 col-md-6 mb-3"><div class="card bg-light"><div class="card-body"><h5>Autorizadores</h5><h2>{{ $authorizersCount }}</h2></div></div></div>
        </div>

        <h5>Status</h5>
        <table class="table table-bordered">
            <tbody>
                <tr><th>Empresa</th><td>{{ $empresa->nome }} - {{ $empresa->cnpj }}</td></tr>
                <tr><th>Segurança habilitada</th><td>{{ $setting->tenant_enabled ? 'Sim' : 'Não' }}</td></tr>
                <tr><th>Aplicação das regras</th><td>{{ $setting->enforcement_enabled ? 'Ativa' : 'Inativa' }}</td></tr>
                <tr><th>Senha legada desabilitada</th><td>{{ $setting->legacy_password_disabled ? 'Sim' : 'Não' }}</td></tr>
                <tr><th>Google Authenticator obrigatório</th><td>{{ $setting->google_auth_required ? 'Sim' : 'Não' }}</td></tr>
                <tr><th>Exportação sensível de auditoria</th><td>{{ $setting->audit_sensitive_export_enabled ? 'Habilitada' : 'Desabilitada' }}</td></tr>
                <tr><th>Restauração por auditoria</th><td>{{ $setting->restore_from_audit_enabled ? 'Habilitada' : 'Desabilitada' }}</td></tr>
            </tbody>
        </table>

        <div class="mt-4">
            <a href="/seguranca/permissoes?empresa_id={{ $empresa->id }}" class="btn btn-primary">Permissões</a>
            <a href="/seguranca/protecoes?empresa_id={{ $empresa->id }}" class="btn btn-primary">Proteções</a>
            <a href="/seguranca/autorizadores?empresa_id={{ $empresa->id }}" class="btn btn-primary">Autorizadores</a>
            <a href="/seguranca/tokens?empresa_id={{ $empresa->id }}" class="btn btn-primary">Tokens</a>
        </div>
    </div>
</div>
@endsection
