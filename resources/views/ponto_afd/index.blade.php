@extends('default.layout')
@section('content')
<div class="card card-custom mb-4">
    <div class="card-header"><h3 class="card-title">{{ $title }}</h3></div>
    <div class="card-body">
        <form method="post" action="/ponto/importacao-afd" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>Relógio (opcional)</label>
                    <select class="form-control" name="ponto_relogio_id">
                        <option value="">Selecione</option>
                        @foreach($relogios as $r)
                            <option value="{{ $r->id }}">{{ $r->nome }} - {{ $r->numero_serie }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label>Arquivo AFD</label>
                    <input type="file" class="form-control" name="arquivo_afd" required>
                </div>
                <div class="col-md-2 form-group d-flex align-items-end">
                    <button class="btn btn-primary btn-block">Importar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header"><h3 class="card-title">Arquivos Importados</h3></div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th><th>Nome</th><th>Hash</th><th>Status</th><th>Linhas</th><th>Processado em</th><th>Ações</th>
                </tr>
            </thead>
            <tbody>
            @forelse($arquivos as $a)
                <tr>
                    <td>{{ $a->id }}</td>
                    <td>{{ $a->nome_original }}</td>
                    <td><small>{{ $a->hash_arquivo }}</small></td>
                    <td>{{ $a->status }}</td>
                    <td>{{ $a->linhas_validas }}/{{ $a->total_linhas }} <small class="text-danger">(inv: {{ $a->linhas_invalidas }})</small></td>
                    <td>{{ $a->processado_em }}</td>
                    <td><a class="btn btn-sm btn-info" href="/ponto/importacao-afd/{{ $a->id }}">Visualizar</a></td>
                </tr>
            @empty
                <tr><td colspan="7">Nenhum arquivo importado.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $arquivos->links() }}
    </div>
</div>
@endsection
