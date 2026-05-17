@extends('default.layout')

@section('content')
<div class="card card-custom gutter-b">
    <div class="card-body">
        @if(session('mensagem_sucesso'))
            <div class="alert alert-success">{{ session('mensagem_sucesso') }}</div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Exemplo: Controller sem BaseController</h3>
                <p class="text-muted mb-0">Esta view é apenas modelo para controllers legados/personalizados.</p>
            </div>
            <a href="/exemplo-seguranca-produtos/create" class="btn btn-primary">Novo</a>
        </div>

        <div class="alert alert-info">
            O modal de autorização é global. Esta view não inclui nenhum HTML de modal manualmente.
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Valor</th>
                        <th width="180">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($produtos as $produto)
                        <tr>
                            <td>{{ $produto->id }}</td>
                            <td>{{ $produto->nome }}</td>
                            <td>{{ $produto->valor_venda }}</td>
                            <td>
                                <a href="/exemplo-seguranca-produtos/edit/{{ $produto->id }}" class="btn btn-sm btn-warning">Editar</a>
                                <a href="/exemplo-seguranca-produtos/delete/{{ $produto->id }}" class="btn btn-sm btn-danger" onclick="return confirm('Deseja excluir este registro?')">Excluir</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">Nenhum registro encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $produtos->links() }}
    </div>
</div>
@endsection
