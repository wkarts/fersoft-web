@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
        @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

        <h3>Configurações de Segurança</h3>
        <p class="text-muted">Controle de ativação por empresa. Por padrão, nada bloqueia a aplicação até que a aplicação das regras seja marcada.</p>
        <p><a href="/seguranca/setup" class="btn btn-sm btn-primary">Abrir Assistente de Implantação</a></p>

        <div class="alert alert-info">
            <strong>Escopo da segurança:</strong> estas regras são aplicadas por empresa/tenant.
            Filiais continuam sendo apenas escopo operacional dos dados, evitando duplicar permissões e proteções para cada filial.
        </div>

        <form method="post" action="/seguranca/configuracoes">
            @csrf
            @if($isSuper && !empty($empresaId))<input type="hidden" name="empresa_id" value="{{ $empresaId }}">@endif
            <div class="row">
                <div class="col-md-6">
                    <label><input type="checkbox" name="tenant_enabled" value="1" {{ $setting->tenant_enabled ? 'checked' : '' }}> Habilitar Segurança de Operações</label><br>
                    <label><input type="checkbox" name="setup_completed" value="1" {{ $setting->setup_completed_at ? 'checked' : '' }}> Configuração inicial concluída</label><br>
                    <label><input type="checkbox" name="enforcement_enabled" value="1" {{ $setting->enforcement_enabled ? 'checked' : '' }}> Aplicar regras nos CRUDs</label><br>
                </div>
                <div class="col-md-6">
                    <label><input type="checkbox" name="legacy_password_disabled" value="1" {{ $setting->legacy_password_disabled ? 'checked' : '' }}> Desabilitar senha legada nas telas antigas</label><br>
                    <label><input type="checkbox" name="google_auth_required" value="1" {{ $setting->google_auth_required ? 'checked' : '' }}> Exigir Google Authenticator quando aplicável</label><br>
                    <label><input type="checkbox" name="audit_sensitive_export_enabled" value="1" {{ $setting->audit_sensitive_export_enabled ? 'checked' : '' }}> Permitir exportação JSON sensível</label><br>
                    <label><input type="checkbox" name="restore_from_audit_enabled" value="1" {{ $setting->restore_from_audit_enabled ? 'checked' : '' }}> Permitir restauração por auditoria</label><br>
                </div>
            </div>
            <hr>
            <button class="btn btn-primary">Salvar configurações</button>
        </form>
    </div>
</div>
@endsection
