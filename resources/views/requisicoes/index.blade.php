@extends('default.layout')

@section('content')
<div class="container-fluid">

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Histórico de Requisições de Materiais/EPI</h4>
            <a href="{{ route('requisicoes.create') }}" class="btn btn-primary">
                <i class="fa fa-plus"></i> Nova Requisição
            </a>
        </div>
        <div class="card-body">
            
            {{-- FORMULÁRIO DE FILTROS --}}
            <form action="{{ route('requisicoes.index') }}" method="GET" class="mb-4 bg-light p-3 border rounded">
                <div class="row">
                    <div class="col-md-3">
                        <label for="data_inicial">Data Inicial</label>
                        <input type="date" name="data_inicial" id="data_inicial" class="form-control" value="{{ request('data_inicial') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="data_final">Data Final</label>
                        <input type="date" name="data_final" id="data_final" class="form-control" value="{{ request('data_final') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="funcionario_id">Funcionário</label>
                        <select name="funcionario_id" id="funcionario_id" class="form-control">
                            <option value="">-- Todos os Funcionários --</option>
                            @foreach($funcionarios as $func)
                                <option value="{{ $func->id }}" {{ request('funcionario_id') == $func->id ? 'selected' : '' }}>
                                    {{ $func->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-secondary w-100">
                            <i class="fa fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12 text-right">
                        <button type="submit" 
                                formaction="{{ route('requisicoes.imprimirFichaFiltro') }}" 
                                formtarget="_blank" 
                                class="btn btn-danger">
                            <i class="fa fa-file-pdf-o"></i> Imprimir Ficha Consolidada
                        </button>
                    </div>
                </div>
            </form>
            <hr>

            {{-- TABELA DE RESULTADOS --}}
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Funcionário</th>
                            <th>Unidade</th>
                            <th>Responsável (Técnico)</th>
                            <th>Status</th>
                            <th width="220" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requisicoes as $req)
                        <tr>
                            <td>#{{ $req->id }}</td>
                            <td>{{ \Carbon\Carbon::parse($req->data_requisicao)->format('d/m/Y H:i') }}</td>
                            <td>{{ $req->funcionario->nome }}</td>
                            <td>
                                <span class="badge {{ $req->unidade == 'Matriz' ? 'badge-info' : 'badge-warning' }}">
                                    {{ $req->unidade }}
                                </span>
                            </td>
                            <td>{{ $req->responsavel?->nome ?? 'Não informado' }}</td>
                            <td>
                                {{-- Lógica de cores para o Status --}}
                                @if($req->status == 'Finalizado')
                                    <span class="badge badge-success">Finalizado</span>
                                @else
                                    <span class="badge badge-warning">Pendente</span>
                                @endif
                            </td>
                            <td class="text-center">
                                {{-- Botão Imprimir Recibo Individual --}}
                                <a href="{{ route('requisicoes.imprimir', $req->id) }}" target="_blank" class="btn btn-sm btn-info" title="Imprimir Recibo">
                                    <i class="fa fa-print"></i>
                                </a>

                                {{-- Botão Ver Detalhes --}}
                                <a href="{{ route('requisicoes.show', $req->id) }}" class="btn btn-sm btn-dark" title="Ver Itens">
                                    <i class="fa fa-eye"></i>
                                </a>

                                {{-- Ações permitidas apenas se NÃO estiver Finalizado --}}
                                @if($req->status != 'Finalizado')
                                    {{-- Botão Finalizar --}}
                                    <button type="button" class="btn btn-sm btn-success" onclick="finalizarRequisicao({{ $req->id }})" title="Finalizar Requisição">
                                        <i class="fa fa-check"></i>
                                    </button>

                                    {{-- Botão Editar --}}
                                    <a href="{{ route('requisicoes.edit', $req->id) }}" class="btn btn-sm btn-warning" title="Editar">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                @endif

                                {{-- Botão Excluir com Senha --}}
                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmarExclusao({{ $req->id }})" title="Excluir e Estornar">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">Nenhuma requisição encontrada para estes filtros.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-center">
                {{ $requisicoes->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>

{{-- Scripts de Ação --}}
<script>
function finalizarRequisicao(id) {
    if(confirm("Deseja realmente finalizar esta requisição? Após finalizar, a edição será bloqueada.")) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.action = `/requisicoes/finalizar/${id}`; // Rota que vamos criar
        form.innerHTML = `
            @csrf
            @method('PUT')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function confirmarExclusao(id) {
    let senha = prompt("Informe a senha de exclusão para estornar o estoque e remover o registro:");
    
    if (senha) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.action = `/requisicoes/${id}`;
        form.innerHTML = `
            @csrf
            @method('DELETE')
            <input type="hidden" name="senha_exclusao" value="${senha}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection