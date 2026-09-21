@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-header flex-wrap border-0 pt-6 pb-0">
        <div class="card-title">
            <h3 class="card-label">
                <i class="fa fa-key text-primary"></i> Unidades e Credenciais MTR (SINIR / IEMA)
            </h3>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('mtr.unidades.create') }}" class="btn btn-primary font-weight-bolder">
                <i class="fa fa-plus"></i> Nova Credencial
            </a>
        </div>
    </div>

    <div class="card-body">
        @if(session('sucesso'))
            <div class="alert alert-custom alert-light-success fade show mb-5" role="alert">
                <div class="alert-icon"><i class="flaticon-outline-check font-size-h2"></i></div>
                <div class="alert-text">{{ session('sucesso') }}</div>
            </div>
        @endif
        @if(session('erro'))
            <div class="alert alert-custom alert-light-danger fade show mb-5" role="alert">
                <div class="alert-icon"><i class="flaticon-warning font-size-h2"></i></div>
                <div class="alert-text">{{ session('erro') }}</div>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-head-custom table-vertical-center" id="kt_advance_table_widget_1">
                <thead>
                    <tr class="text-left">
                        <th>Órgão</th>
                        <th>Filial</th>
                        <th>CPF/CNPJ</th>
                        <th>Cód. Unidade</th>
                        <th>Perfil</th>
                        <th>Ambiente</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($unidades as $u)
                        <tr>
                            <td><span class="label label-inline label-light-primary font-weight-bold">{{ $u->orgao }}</span></td>
                            <td>{{ $u->filial_nome ?? 'Matriz / Todas' }}</td>
                            <td><code>{{ $u->cpf_cnpj }}</code></td>
                            <td>{{ $u->unidade_id }}</td>
                            <td>{{ $u->perfil }}</td>
                            <td>
                                @if($u->ambiente == 'producao')
                                    <span class="label label-inline label-light-success font-weight-bold">Produção</span>
                                @else
                                    <span class="label label-inline label-light-warning font-weight-bold">Homologação</span>
                                @endif
                            </td>
                            <td>
                                @if($u->ativo)
                                    <span class="label label-inline label-light-success font-weight-bold">Ativo</span>
                                @else
                                    <span class="label label-inline label-light-danger font-weight-bold">Inativo</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('mtr.unidades.edit', $u->id) }}" class="btn btn-icon btn-light btn-hover-primary btn-sm mx-1">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <a href="{{ route('mtr.unidades.destroy', $u->id) }}" class="btn btn-icon btn-light btn-hover-danger btn-sm mx-1" onclick="return confirm('Deseja remover estas credenciais?')">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                Nenhuma credencial configurada até o momento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection