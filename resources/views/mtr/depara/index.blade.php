@extends('default.layout')
@section('content')
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h3>De-Para de Resíduos</h3><div class="text-muted">Mapeamento interno para códigos IBAMA/SINIR/IEMA.</div></div>
    <a href="{{ route('mtr.depara.create') }}" class="btn btn-primary">+ Novo vínculo</a>
</div>
@if(session('sucesso'))<div class="alert alert-success">{{ session('sucesso') }}</div>@endif
@if(session('erro'))<div class="alert alert-danger">{{ session('erro') }}</div>@endif
<div class="card card-custom"><div class="card-body table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>#</th><th>Órgão</th><th>Produto</th><th>Cód. IBAMA</th><th>Resíduo</th><th>Unidade</th><th>Ações</th></tr></thead>
<tbody>
@forelse($deparas as $d)
<tr>
    <td>{{ $d->id }}</td><td>{{ $d->orgao }}</td><td>{{ $produtosMapa[$d->produto_id] ?? '—' }}</td>
    <td>{{ $d->cod_ibama }}</td><td>{{ $d->descricao_residuo }}</td><td>{{ $d->unidade_medida }}</td>
    <td><a href="{{ route('mtr.depara.edit',$d->id) }}" class="btn btn-sm btn-warning">Editar</a>
    <a href="{{ route('mtr.depara.destroy',$d->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('Excluir este vínculo?')">Excluir</a></td>
</tr>
@empty<tr><td colspan="7" class="text-center text-muted py-5">Nenhum vínculo cadastrado.</td></tr>@endforelse
</tbody>
</table>
</div></div>
</div>
@endsection
