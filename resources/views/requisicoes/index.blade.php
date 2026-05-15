@extends('default.layout')

@section('content')
<div class="container-fluid">

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

   	 <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Histórico de Requisições de Materiais/EPI</h4>
            <div>
                <button type="button" class="btn btn-info" data-toggle="modal" data-target="#modalImportar">
                    <i class="fa fa-upload"></i> Importar Histórico
                </button>
                <a href="{{ route('requisicoes.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Nova Requisição
                </a>
            </div>
        </div>
        
        <div class="card-body">
            {{-- FORMULÁRIO DE FILTROS --}}
            <form action="{{ route('requisicoes.index') }}" method="GET" class="mb-4 bg-light p-3 border rounded" id="form-filtro">
                <div class="row">
                    <div class="col-md-2">
                        <label>Data Inicial</label>
                        <input type="date" name="data_inicial" class="form-control" value="{{ request('data_inicial') }}">
                    </div>
                    <div class="col-md-2">
                        <label>Data Final</label>
                        <input type="date" name="data_final" class="form-control" value="{{ request('data_final') }}">
                    </div>
                    <div class="col-md-3">
                        <label>Funcionário</label>
                        <select name="funcionario_id" class="form-control">
                            <option value="">-- Todos --</option>
                            @foreach($funcionarios as $func)
                                <option value="{{ $func->id }}" {{ request('funcionario_id') == $func->id ? 'selected' : '' }}>
                                    {{ $func->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5 d-flex align-items-end">
                        <button type="submit" class="btn btn-secondary mr-2">
                            <i class="fa fa-search"></i> Filtrar
                        </button>
                        
                        {{-- BOTÃO CORRIGIDO PARA USAR A ROTA DE FILTRO DE IMPRESSÃO --}}
                        <button type="submit" 
                                formaction="{{ route('requisicoes.imprimirFichaFiltro') }}" 
                                formtarget="_blank" 
                                class="btn btn-danger">
                            <i class="fa fa-file-pdf-o"></i> Imprimir Ficha Consolidada
                        </button>
                    </div>
                </div>
            </form>

            {{-- TABELA --}}
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Funcionário</th>
                            <th>Status</th>
                            <th width="200" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requisicoes as $req)
                        <tr>
                            <td>#{{ $req->id }}</td>
                            <td>{{ \Carbon\Carbon::parse($req->data_requisicao)->format('d/m/Y H:i') }}</td>
                            <td>{{ $req->funcionario->nome }}</td>
                            <td>
                                <span class="badge {{ $req->status == 'Finalizado' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $req->status }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('requisicoes.imprimir', $req->id) }}" target="_blank" class="btn btn-sm btn-info" title="Imprimir"><i class="fa fa-print"></i></a>
                                <a href="{{ route('requisicoes.show', $req->id) }}" class="btn btn-sm btn-dark" title="Ver"><i class="fa fa-eye"></i></a>
                                
                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmarExclusao({{ $req->id }})" title="Excluir">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">Nenhuma requisição encontrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-2">
                    {{ $requisicoes->appends(request()->all())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DE SENHA --}}
<div class="modal fade" id="modalSenhaExclusao" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <form id="formExcluirRequisicao" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                </div>
                <div class="modal-body">
                    <label>Senha de Exclusão:</label>
                    <input type="password" name="senha_exclusao" id="input_senha_exclusao" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Sair</button>
                    <button type="submit" class="btn btn-danger">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id) {
    $('#formExcluirRequisicao').attr('action', '/requisicoes/' + id);
    $('#input_senha_exclusao').val('');
    $('#modalSenhaExclusao').modal('show');
}
</script>
@endsection