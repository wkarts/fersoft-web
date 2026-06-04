@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
    <div class="card-header bg-white py-3">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <h5 class="mb-0 text-dark font-weight-bold">
                    <i class="fas fa-list text-primary mr-2"></i> {{ $title }}
                </h5>
            </div>
            <div class="col-sm-6 text-right">
                <a href="/tabelas-precos/new" class="btn btn-success font-weight-bold">
                    <i class="fas fa-plus"></i> Nova Tabela
                </a>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-vertical-center">
                <thead class="thead-light">
                    <tr>
                        <th width="100">ID</th>
                        <th>Descrição / Nome da Tabela</th>
                        <th width="200">Data de Criação</th>
                        <th width="150" class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $t)
                        <tr>
                            <td><span class="text-dark-75 font-weight-bolder">#{{ $t->id }}</span></td>
                            <td><span class="text-dark-75 font-weight-bold">{{ $t->descricao }}</span></td>
                            <td>{{ $t->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                <a href="/tabelas-precos/edit/{{ $t->id }}" class="btn btn-sm btn-light-primary btn-icon" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="javascript:void(0)" onclick="if(confirm('Deseja realmente excluir esta tabela?')) window.location.href='/tabelas-precos/delete/{{ $t->id }}'" class="btn btn-sm btn-light-danger btn-icon" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                Nenhuma tabela de preços cadastrada até o momento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection