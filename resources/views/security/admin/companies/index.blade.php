@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Segurança por Empresa</h3>
                <p class="text-muted mb-0">Visão administrativa geral dos tenants e status de implantação da Segurança de Operações.</p>
            </div>
            <a href="/seguranca/admin/painel-global" class="btn btn-light">Painel Global</a>
        </div>

        <form method="get" class="mb-3">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Buscar por empresa, fantasia ou CNPJ" value="{{ request('search') }}">
                <div class="input-group-append"><button class="btn btn-primary">Buscar</button></div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>CNPJ</th>
                        <th>Segurança</th>
                        <th>Setup</th>
                        <th>Aplicação das regras</th>
                        <th>Senha legada</th>
                        <th width="120">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($empresas as $empresa)
                        @php($setting = $settings->get($empresa->id))
                        <tr>
                            <td><strong>{{ $empresa->nome }}</strong><br><small>{{ $empresa->nome_fantasia }}</small></td>
                            <td>{{ $empresa->cnpj }}</td>
                            <td>{{ optional($setting)->tenant_enabled ? 'Habilitada' : 'Desabilitada' }}</td>
                            <td>{{ optional($setting)->setup_completed_at ? 'Concluído' : 'Pendente' }}</td>
                            <td>{{ optional($setting)->enforcement_enabled ? 'Ativa' : 'Inativa' }}</td>
                            <td>{{ optional($setting)->legacy_password_disabled ? 'Desabilitada' : 'Ativa' }}</td>
                            <td><a href="/seguranca/admin/empresas/{{ $empresa->id }}" class="btn btn-sm btn-primary">Detalhes</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">Nenhuma empresa localizada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $empresas->links() }}
    </div>
</div>
@endsection
