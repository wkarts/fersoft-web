@extends('default.layout')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Importar Plano de Contas Contábil</h3>
        <a href="{{ route('contabilidade.configuracoes.index') }}" class="btn btn-warning">
            <i class="fa fa-cogs"></i> Configurar Mapeamento
        </a>
    </div>

    @if(session('sucesso'))
        <div class="alert alert-success">{{ session('sucesso') }}</div>
    @endif

    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <form action="{{ route('contabilidade.plano.importar') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Selecione o arquivo TXT (.txt)</label>
                        <input class="form-control" type="file" name="arquivo_plano" accept=".txt" required>
                    </div>
                    <div class="col-md-4 mt-2">
                        <button type="submit" class="btn btn-primary w-100">Processar Importação</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">Contas Cadastradas</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Código</th>
                        <th>Classificador</th>
                        <th>Nome</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contas as $conta)
                        <tr>
                            <td class="ps-4">{{ $conta->codigo_acesso }}</td>
                            <td>{{ $conta->classificador }}</td>
                            <td>{{ $conta->nome }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">Nenhuma conta encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection