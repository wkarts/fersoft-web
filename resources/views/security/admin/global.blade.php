@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3>Painel Global de Segurança</h3>
                <p class="text-muted mb-0">Visão administrativa da parametrização por empresa.</p>
            </div>
            <a href="/seguranca/diagnostico" class="btn btn-warning mr-2">Diagnóstico Global</a>
            <a href="/seguranca/admin/empresas" class="btn btn-primary">Ver empresas</a>
        </div>

        <div class="row mb-4">
            <div class="col-md-2"><div class="alert alert-light"><strong>Total</strong><br>{{ $summary['total'] }}</div></div>
            <div class="col-md-2"><div class="alert alert-info"><strong>Habilitadas</strong><br>{{ $summary['tenant_enabled'] }}</div></div>
            <div class="col-md-2"><div class="alert alert-success"><strong>Setup concluído</strong><br>{{ $summary['setup_completed'] }}</div></div>
            <div class="col-md-2"><div class="alert alert-primary"><strong>Aplicando regras</strong><br>{{ $summary['enforcement_enabled'] }}</div></div>
            <div class="col-md-2"><div class="alert alert-warning"><strong>Legado ativo</strong><br>{{ $summary['legacy_enabled'] }}</div></div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>CNPJ</th>
                        <th>Segurança</th>
                        <th>Setup</th>
                        <th>Aplicação</th>
                        <th>Legado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary['rows'] as $row)
                        <tr>
                            <td><a href="/seguranca/admin/empresas/{{ $row['empresa']->id }}">{{ $row['empresa']->nome }}</a></td>
                            <td>{{ $row['empresa']->cnpj }}</td>
                            <td>{{ $row['tenant_enabled'] ? 'Habilitada' : 'Desabilitada' }}</td>
                            <td>{{ $row['setup_completed'] ? 'Concluído' : 'Pendente' }}</td>
                            <td>{{ $row['enforcement_enabled'] ? 'Ativa' : 'Inativa' }}</td>
                            <td>{{ $row['legacy_password_disabled'] ? 'Desabilitado' : 'Ativo' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
