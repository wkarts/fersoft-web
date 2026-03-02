@extends('default.layout')
@section('content')
<div class="card card-custom">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">{{ $title }}</h3>
        <a href="/ponto/relogios/new" class="btn btn-primary btn-sm">Novo Relógio</a>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Fabricante/Modelo</th>
                    <th>Série</th>
                    <th>Local</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            @forelse($itens as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->nome }}</td>
                    <td>{{ $item->fabricante }} / {{ $item->modelo }}</td>
                    <td>{{ $item->numero_serie }}</td>
                    <td>{{ $item->local }}</td>
                    <td>{{ $item->ativo ? 'Ativo' : 'Inativo' }}</td>
                    <td>
                        <a href="/ponto/relogios/edit/{{ $item->id }}" class="btn btn-sm btn-warning">Editar</a>
                        <a href="/ponto/relogios/delete/{{ $item->id }}" class="btn btn-sm btn-danger">Excluir</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Nenhum relógio cadastrado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
