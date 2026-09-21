@extends('default.layout')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h3>Unidades / Credenciais MTR</h3><div class="text-muted">SINIR, IEMA e demais órgãos configurados por empresa.</div></div>
        <a href="{{ route('mtr.unidades.create') }}" class="btn btn-primary">+ Nova credencial</a>
    </div>

    @if(session('sucesso'))<div class="alert alert-success">{{ session('sucesso') }}</div>@endif
    @if(session('erro'))<div class="alert alert-danger">{{ session('erro') }}</div>@endif

    <div class="card card-custom"><div class="card-body table-responsive">
        <table class="table table-bordered table-hover">
            <thead><tr><th>#</th><th>Órgão</th><th>Perfil</th><th>CPF/CNPJ</th><th>Unidade</th><th>Ambiente</th><th>Ativo</th><th>Ações</th></tr></thead>
            <tbody>
            @forelse($unidades as $u)
                <tr>
                    <td>{{ $u->id }}</td><td>{{ $u->orgao }}</td><td>{{ $u->perfil }}</td><td>{{ $u->cpf_cnpj }}</td>
                    <td>{{ $u->unidade_id }}</td><td>{{ $u->ambiente }}</td><td>{{ $u->ativo ? 'Sim' : 'Não' }}</td>
                    <td>
                        <a href="{{ route('mtr.unidades.edit',$u->id) }}" class="btn btn-sm btn-warning">Editar</a>
                        <a href="{{ route('mtr.unidades.destroy',$u->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('Remover esta credencial?')">Excluir</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-5">Nenhuma credencial cadastrada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection
