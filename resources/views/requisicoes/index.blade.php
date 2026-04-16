@extends('default.layout')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Histórico de Requisições de Materiais/EPI</h4>
            <a href="{{ route('requisicoes.create') }}" class="btn btn-primary">Nova Requisição</a>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Funcionário</th>
                        <th>Unidade</th>
                        <th>Responsável (Entrega)</th>
                        <th>Status</th>
                        <th width="100">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requisicoes as $req)
                    <tr>
                        <td>#{{ $req->id }}</td>
                        <td>{{ \Carbon\Carbon::parse($req->data_requisicao)->format('d/m/Y H:i') }}</td>
                        <td>{{ $req->funcionario->nome }}</td>
                        <td>
                            <span class="badge {{ $req->unidade == 'Matriz' ? 'badge-info' : 'badge-warning' }}">
                                {{ $req->unidade }}
                            </span>
                        </td>
                        <td>{{ $req->responsavel->name }}</td>
                        <td>
                            <span class="badge badge-success">{{ $req->status }}</span>
                        </td>
                        <td>
                            {{-- Botão para ver detalhes ou imprimir Recibo --}}
                            <a href="{{ route('requisicoes.show', $req->id) }}" class="btn btn-sm btn-dark" title="Ver Itens">
                                <i class="fa fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Paginação --}}
            <div class="mt-3">
                {{ $requisicoes->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>
@endsection