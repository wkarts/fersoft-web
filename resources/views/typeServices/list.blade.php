@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/fer-catalog-config.css') }}?v={{ filemtime(public_path('css/fer-catalog-config.css')) }}">
@endpush
@section('content')
<div class="fer-catalog-config fer-catalog-config--list fer-catalog-config--typeServices">
    <div class="card card-custom">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title">Lista de Tipos de Serviço</h4>
            <a href="/tipoServico/new" class="btn btn-success">
                <i class="fa fa-plus" aria-hidden="true"></i>Novo Tipo de Serviço
            </a>
        </div>
        <div class="card-body">
            @if(session()->has('message'))
            <div class="alert {{ session('color') }}" role="status">{{ session()->get('message') }}</div>
            @endif
            <p class="text-muted">Numero de registros: {{count($types)}}</p>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th scope="col">Código</th><th scope="col">Nome</th><th scope="col">Ações</th></tr>
                    </thead>
                    <tbody>
                        @foreach($types as $t)
                        <tr>
                            <td>{{ $t->id }}</td>
                            <td>{{ $t->name }}</td>
                            <td>
                                <a class="btn btn-sm btn-warning" aria-label="Editar tipo de serviço" href="/tipoServico/edit/{{ $t->id }}"><i class="la la-edit" aria-hidden="true"></i></a>
                                <a class="btn btn-sm btn-danger" aria-label="Excluir tipo de serviço" onclick = "if (! confirm('Deseja excluir este registro?')) { return false; }" href="/tipoServico/delete/{{ $t->id }}"><i class="la la-trash" aria-hidden="true"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
