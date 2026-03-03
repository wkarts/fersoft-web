@extends('default.layout')
@section('content')
<div class="card card-custom mb-4"><div class="card-header"><h3 class="card-title">{{ $title }}</h3></div><div class="card-body">
<form method="post" action="/ponto/ajustes">@csrf
<div class="row">
<div class="col-md-3"><label>Funcionário</label><select class="form-control" name="funcionario_id" required>@foreach($funcionarios as $f)<option value="{{ $f->id }}">{{ $f->nome }}</option>@endforeach</select></div>
<div class="col-md-2"><label>Tipo</label><select class="form-control" name="tipo"><option value="inclusao">Inclusão</option><option value="edicao">Edição</option><option value="desconsideracao">Desconsideração</option></select></div>
<div class="col-md-3"><label>Data/Hora original</label><input type="datetime-local" name="data_hora_original" class="form-control"></div>
<div class="col-md-3"><label>Data/Hora nova</label><input type="datetime-local" name="data_hora_nova" class="form-control"></div>
<div class="col-md-12 mt-2"><label>Justificativa</label><textarea name="justificativa" class="form-control" required></textarea></div>
<div class="col-md-2 mt-2"><button class="btn btn-primary">Solicitar ajuste</button></div>
</div></form></div></div>

<div class="card card-custom"><div class="card-header"><h3 class="card-title">Histórico de Ajustes</h3></div><div class="card-body"><table class="table table-bordered"><thead><tr><th>ID</th><th>Funcionário</th><th>Tipo</th><th>Status</th><th>Justificativa</th><th>Ações</th></tr></thead><tbody>@foreach($ajustes as $a)<tr><td>{{ $a->id }}</td><td>{{ $a->funcionario_id }}</td><td>{{ $a->tipo }}</td><td>{{ $a->status }}</td><td>{{ $a->justificativa }}</td><td><form method="post" action="/ponto/ajustes/aprovar" class="d-inline">@csrf<input type="hidden" name="ponto_ajuste_id" value="{{ $a->id }}"><input type="hidden" name="status" value="aprovado"><button class="btn btn-sm btn-success">Aprovar</button></form> <form method="post" action="/ponto/ajustes/aprovar" class="d-inline">@csrf<input type="hidden" name="ponto_ajuste_id" value="{{ $a->id }}"><input type="hidden" name="status" value="reprovado"><button class="btn btn-sm btn-danger">Reprovar</button></form></td></tr>@endforeach</tbody></table>{{ $ajustes->links() }}</div></div>
@endsection
