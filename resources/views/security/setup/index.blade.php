@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        @if(session('mensagem_sucesso'))<div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>@endif
        @if(session('mensagem_erro'))<div class="alert alert-danger">{{ session('mensagem_erro') }}</div>@endif

        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <h3 class="mb-1">Assistente de Implantação da Segurança</h3>
                <p class="text-muted mb-0">Ative, parametrize, revise e só depois aplique as regras nos CRUDs. Esta tela não separa regras por filial; o escopo é empresa/tenant.</p>
            </div>
            <div class="text-right">
                <span class="badge badge-primary p-2">{{ $checklist['completion_percent'] }}% concluído</span>
            </div>
        </div>

        @if(!empty($checklist['warnings']))
            <div class="alert alert-warning">
                <strong>Atenção:</strong>
                <ul class="mb-0">
                    @foreach($checklist['warnings'] as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="alert alert-info">
            <strong>Regra de produção:</strong> habilitar a Segurança de Operações não bloqueia nada automaticamente.
            O bloqueio só ocorre quando <strong>Aplicação das regras</strong> estiver ativa e houver permissões/proteções aplicáveis.
        </div>

        <div class="row mb-4">
            <div class="col-md-3"><div class="alert alert-light border"><strong>Recursos</strong><br>{{ $checklist['resources_count'] }}</div></div>
            <div class="col-md-3"><div class="alert alert-light border"><strong>Permissões</strong><br>{{ $checklist['permissions_count'] }}</div></div>
            <div class="col-md-3"><div class="alert alert-light border"><strong>Proteções</strong><br>{{ $checklist['protections_count'] }}</div></div>
            <div class="col-md-3"><div class="alert alert-light border"><strong>Autorizadores</strong><br>{{ $checklist['authorizers_count'] }}</div></div>
        </div>

        <div class="alert alert-primary">
            <strong>Google Authenticator como padrão recomendado:</strong> no setup inicial, configure ao menos um autorizador com app autenticador.
            Tokens operacionais e tokens temporários continuam disponíveis como mecanismos complementares e compatíveis com o fluxo existente.
        </div>

        <div class="row mb-4">
            <div class="col-md-4"><div class="alert alert-light border"><strong>Apps autenticadores confirmados</strong><br>{{ $checklist['authorizers_with_otp_count'] ?? 0 }}</div></div>
            <div class="col-md-4"><div class="alert alert-light border"><strong>Tokens operacionais ativos</strong><br>{{ $checklist['active_tokens_count'] }}</div></div>
            <div class="col-md-4"><div class="alert alert-light border"><strong>Autorizadores sem credencial</strong><br>{{ $checklist['authorizers_without_credential'] ?? 0 }}</div></div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th style="width: 90px;">Status</th>
                        <th>Etapa</th>
                        <th>Descrição</th>
                        <th style="width: 260px;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($checklist['steps'] as $key => $step)
                        <tr>
                            <td>{!! $step['done'] ? '<span class="badge badge-success">OK</span>' : '<span class="badge badge-warning">Pendente</span>' !!}</td>
                            <td><strong>{{ $step['label'] }}</strong></td>
                            <td>{{ $step['description'] }}</td>
                            <td>
                                @if($key === 'enabled' && !$step['done'])
                                    <form method="post" action="/seguranca/setup/habilitar">@csrf<button class="btn btn-sm btn-primary">Habilitar</button></form>
                                @elseif($key === 'resources')
                                    <form method="post" action="/seguranca/setup/sincronizar-recursos">@csrf<button class="btn btn-sm btn-primary">Sincronizar recursos</button></form>
                                @elseif($key === 'permissions')
                                    <form method="post" action="/seguranca/setup/gerar-permissoes">@csrf<button class="btn btn-sm btn-primary">Gerar permissões padrão</button></form>
                                @elseif($key === 'audit_policies')
                                    <form method="post" action="/seguranca/setup/gerar-politicas-auditoria">@csrf<button class="btn btn-sm btn-primary">Gerar políticas</button></form>
                                @elseif($key === 'protections_review')
                                    <div class="d-flex flex-column">
                                        <form method="post" action="/seguranca/setup/gerar-protecoes-seguras" class="mb-1">@csrf<button class="btn btn-sm btn-warning">Gerar proteções críticas</button></form>
                                        <a class="btn btn-sm btn-light" href="/seguranca/protecoes">Revisar proteções</a>
                                    </div>
                                @elseif($key === 'authorizers')
                                    <a class="btn btn-sm btn-primary" href="/seguranca/autorizadores">Configurar autorizadores</a>
                                @elseif($key === 'google_auth')
                                    <a class="btn btn-sm btn-primary" href="/seguranca/autorizadores">Configurar App Auth</a>
                                @elseif($key === 'tokens')
                                    <div class="d-flex flex-column">
                                        <a class="btn btn-sm btn-primary mb-1" href="/seguranca/autorizadores">Configurar App Auth</a>
                                        <a class="btn btn-sm btn-light" href="/seguranca/tokens">Configurar tokens</a>
                                    </div>
                                @elseif($key === 'setup_completed')
                                    <form method="post" action="/seguranca/setup/concluir">@csrf<button class="btn btn-sm btn-success" {{ $checklist['can_complete_setup'] ? '' : 'disabled' }}>Concluir setup</button></form>
                                @elseif($key === 'enforcement')
                                    <form method="post" action="/seguranca/setup/ativar-enforcement">@csrf<button class="btn btn-sm btn-danger" {{ $checklist['can_enable_enforcement'] ? '' : 'disabled' }} onclick="return confirm('Confirma ativar a aplicação das regras nos CRUDs?')">Ativar regras</button></form>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-6">
                <h5>Transição da senha legada</h5>
                <p class="text-muted">A senha antiga só deve ser ocultada/desabilitada nas telas legadas depois que a aplicação das regras estiver ativa.</p>
                <form method="post" action="/seguranca/setup/desabilitar-senha-legada">
                    @csrf
                    <button class="btn btn-outline-danger" {{ $checklist['can_disable_legacy'] ? '' : 'disabled' }} onclick="return confirm('Confirma desabilitar a senha legada nas telas antigas?')">
                        Desabilitar senha legada
                    </button>
                </form>
            </div>
            <div class="col-md-6">
                <h5>Atalhos</h5>
                <a class="btn btn-light btn-sm mb-1" href="/seguranca/recursos">Recursos</a>
                <a class="btn btn-light btn-sm mb-1" href="/seguranca/permissoes">Permissões CRUD</a>
                <a class="btn btn-light btn-sm mb-1" href="/seguranca/protecoes">Proteções</a>
                <a class="btn btn-light btn-sm mb-1" href="/seguranca/auditoria/politicas">Políticas de Auditoria</a>
                <a class="btn btn-light btn-sm mb-1" href="/seguranca/diagnostico">Diagnóstico</a>
            </div>
        </div>
    </div>
</div>
@endsection
