@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        @if(session('mensagem_sucesso'))
            <div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>
        @endif
        @if(session('mensagem_erro'))
            <div class="alert alert-danger">{{ session('mensagem_erro') }}</div>
        @endif


        @if(isset($migrationsReady) && !$migrationsReady)
            <div class="alert alert-danger">
                <strong>Migrations da Segurança de Operações pendentes.</strong><br>
                A estrutura foi carregada em modo seguro e nenhuma validação será aplicada até executar <code>php artisan migrate</code>.
                @if(!empty($missingTables))
                    <div class="mt-2"><strong>Tabelas ausentes:</strong> {{ implode(', ', $missingTables) }}</div>
                @endif
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="mb-1">Segurança de Operações</h3>
                <p class="text-muted mb-0">Controle centralizado de permissões CRUD, proteção por autorizadores, auditoria e restauração.</p>
            </div>
            @if(!$setting->tenant_enabled)
                <a href="/seguranca/setup" class="btn btn-primary">Abrir assistente</a>
            @endif
        </div>

        @if(!$setting->tenant_enabled)
            <div class="alert alert-warning">
                <strong>Recurso desabilitado para esta empresa.</strong><br>
                Nada foi alterado no funcionamento atual da aplicação. Todos os CRUDs continuam usando as regras antigas até que a Segurança de Operações seja habilitada e aplicada.
            </div>
        @elseif(!$setting->enforcement_enabled)
            <div class="alert alert-info">
                <strong>Modo de configuração ativo.</strong><br>
                A Segurança de Operações está habilitada, mas ainda não bloqueia operações. Configure recursos, autorizadores, tokens e proteções antes de ativar a aplicação das regras.
            </div>
        @else
            <div class="alert alert-success">
                <strong>Aplicação das regras ativa.</strong><br>
                As validações de segurança poderão ser aplicadas conforme as regras configuradas.
            </div>
        @endif

        <div class="row">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5>Recursos</h5>
                        <h2>{{ $resourcesCount }}</h2>
                        <a href="/seguranca/setup">Assistente</a> | <a href="/seguranca/recursos">Ver recursos</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5>Autorizadores</h5>
                        <h2>{{ $authorizersCount }}</h2>
                        <a href="/seguranca/autorizadores">Configurar</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5>Regras</h5>
                        <h2>{{ $rulesCount }}</h2>
                        <a href="/seguranca/protecoes">Ver proteções</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h5>Logs</h5>
                        <h2>{{ $logsCount }}</h2>
                        <a href="/seguranca/auditoria">Auditoria</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <h5>Status da empresa</h5>
            <table class="table table-bordered">
                <tbody>
                    <tr><th>Plataforma liberou o recurso</th><td>{{ $setting->platform_enabled ? 'Sim' : 'Não' }}</td></tr>
                    <tr><th>Tenant habilitou o recurso</th><td>{{ $setting->tenant_enabled ? 'Sim' : 'Não' }}</td></tr>
                    <tr><th>Aplicação das regras</th><td>{{ $setting->enforcement_enabled ? 'Ativa' : 'Inativa' }}</td></tr>
                    <tr><th>Senha legada desabilitada</th><td>{{ $setting->legacy_password_disabled ? 'Sim' : 'Não' }}</td></tr>
                    <tr><th>Google Authenticator obrigatório</th><td>{{ $setting->google_auth_required ? 'Sim' : 'Não' }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEnableSecurity" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="post" action="/seguranca/setup/habilitar">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Habilitar Segurança de Operações?</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Este recurso substitui gradualmente as antigas senhas fixas de liberação por um controle mais seguro baseado em autorizadores, tokens individuais e Google Authenticator.</p>
                    <p>Após habilitar, será necessário configurar recursos protegidos, permissões CRUD, usuários autorizadores, tokens e regras de auditoria.</p>
                    <div class="alert alert-info mb-0">
                        Por segurança, nenhuma operação será bloqueada automaticamente até que a configuração seja finalizada e a aplicação das regras seja ativada.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Agora não</button>
                    <button type="submit" class="btn btn-primary">Habilitar e configurar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
