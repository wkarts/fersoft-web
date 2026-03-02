@extends('default.layout')
@section('content')
<div class="card card-custom mb-4">
  <div class="card-header"><h3 class="card-title">{{ $title }}</h3></div>
  <div class="card-body">
    <form method="post" action="/ponto/marcacoes/tratar">@csrf
      <div class="row">
        <div class="col-md-4"><label>Funcionário</label><select class="form-control" name="funcionario_id" required>@foreach($funcionarios as $f)<option value="{{ $f->id }}">{{ $f->nome }}</option>@endforeach</select></div>
        <div class="col-md-3"><label>Data início</label><input class="form-control" type="date" name="data_inicio" required></div>
        <div class="col-md-3"><label>Data fim</label><input class="form-control" type="date" name="data_fim" required></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary btn-block">Tratar período</button></div>
      </div>
    </form>
  </div>
</div>

<div class="card card-custom mb-4"><div class="card-header"><h3 class="card-title">Marcações</h3></div><div class="card-body"><table class="table table-bordered"><thead><tr><th>Funcionário</th><th>Data/Hora</th><th>Origem</th><th>Status</th></tr></thead><tbody>@foreach($marcacoes as $m)<tr><td>{{ $m->funcionario_id }}</td><td>{{ $m->data_hora_marcacao }}</td><td>{{ $m->origem }}</td><td>{{ $m->status }}</td></tr>@endforeach</tbody></table>{{ $marcacoes->links() }}</div></div>

<div class="card card-custom"><div class="card-header"><h3 class="card-title">Ocorrências de Tratamento</h3></div><div class="card-body"><table class="table table-bordered"><thead><tr><th>Funcionário</th><th>Data</th><th>Tipo</th><th>Minutos</th><th>Descrição</th></tr></thead><tbody>@foreach($ocorrencias as $o)<tr><td>{{ $o->funcionario_id }}</td><td>{{ $o->data_referencia }}</td><td>{{ $o->tipo }}</td><td>{{ $o->minutos }}</td><td>{{ $o->descricao }}</td></tr>@endforeach</tbody></table>{{ $ocorrencias->links() }}</div></div>
@endsection
