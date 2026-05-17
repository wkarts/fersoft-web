@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header">
        <div class="card-title">
            <h3 class="card-label">Relatórios da Segurança de Operações</h3>
        </div>
        <div class="card-toolbar">
            <a href="/seguranca/diagnostico" class="btn btn-light-primary btn-sm">Diagnóstico</a>
        </div>
    </div>

    <div class="card-body">
        <div class="alert alert-custom alert-light-info fade show mb-8" role="alert">
            <div class="alert-icon"><i class="la la-info-circle"></i></div>
            <div class="alert-text">
                Estes relatórios são administrativos e não alteram permissões, proteções, tokens ou enforcement.
                Tokens são exportados somente como status; o hash/secret nunca é incluído no arquivo.
            </div>
        </div>

        <form method="GET" action="{{ route('security.reports.download') }}" class="row">
            <div class="form-group col-md-4">
                <label>Relatório</label>
                <select name="report" class="form-control">
                    @foreach($reports as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if($isSuperAdmin)
                <div class="form-group col-md-4">
                    <label>Empresa</label>
                    <select name="empresa_id" class="form-control">
                        <option value="">Todas / Global</option>
                        @foreach($empresas as $empresa)
                            <option value="{{ $empresa->id }}">{{ $empresa->nome }} @if(!empty($empresa->cnpj)) - {{ $empresa->cnpj }} @endif</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="form-group col-md-2">
                <label>Formato</label>
                <select name="format" class="form-control">
                    <option value="csv">CSV</option>
                    <option value="json">JSON</option>
                </select>
            </div>

            <div class="form-group col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-block">Baixar</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom gutter-b">
    <div class="card-header">
        <div class="card-title">
            <h3 class="card-label">Relatórios disponíveis</h3>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Relatório</th>
                        <th>Finalidade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reports as $key => $label)
                        <tr>
                            <td><strong>{{ $label }}</strong><br><small class="text-muted">{{ $key }}</small></td>
                            <td>
                                @if($key === 'health')
                                    Conferência do estado geral da implantação e pendências.
                                @elseif($key === 'resources')
                                    Lista técnica e amigável das Models/recursos detectados.
                                @elseif($key === 'permissions')
                                    Matriz de permissões CRUD por empresa, perfil e usuário.
                                @elseif($key === 'protections')
                                    Regras de proteção por ação: token, OTP, bloqueio ou legado.
                                @elseif($key === 'authorizers')
                                    Usuários autorizadores e ações que podem liberar.
                                @elseif($key === 'tokens')
                                    Status dos tokens de liberação sem exportar segredo/hash.
                                @elseif($key === 'audit-policies')
                                    Políticas de visualização, JSON e restauração por auditoria.
                                @elseif($key === 'operations')
                                    Histórico recente de autorizações operacionais.
                                @elseif($key === 'companies')
                                    Status de implantação por empresa/tenant.
                                @else
                                    Relatório administrativo da Segurança de Operações.
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
