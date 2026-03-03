@extends('default.layout')
@section('content')
<div class="card card-custom mb-4">
  <div class="card-header"><h3 class="card-title">{{ $title }}</h3></div>
  <div class="card-body">
    <form method="get" action="/ponto/relatorios">
      <div class="row">
        <div class="col-md-4"><label>Funcionário</label><select class="form-control" name="funcionario_id"><option value="">Todos</option>@foreach($funcionarios as $f)<option value="{{ $f->id }}" @selected(request('funcionario_id')==$f->id)>{{ $f->nome }}</option>@endforeach</select></div>
        <div class="col-md-3"><label>Data início</label><input class="form-control" type="date" name="data_inicio" value="{{ request('data_inicio') }}"></div>
        <div class="col-md-3"><label>Data fim</label><input class="form-control" type="date" name="data_fim" value="{{ request('data_fim') }}"></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary btn-block">Filtrar</button></div>
      </div>
    </form>
    <a class="btn btn-success btn-sm mt-3" href="/ponto/relatorios/csv?funcionario_id={{ request('funcionario_id') }}&data_inicio={{ request('data_inicio') }}&data_fim={{ request('data_fim') }}">Exportar CSV</a>
  </div>
</div>

<div class="card card-custom mb-4"><div class="card-header"><h3 class="card-title">Espelho (Marcações)</h3></div><div class="card-body"><table class="table table-bordered"><thead><tr><th>Funcionário</th><th>Data/Hora</th><th>Origem</th><th>Status</th></tr></thead><tbody>@foreach($marcacoes as $m)<tr><td>{{ $m->funcionario_id }}</td><td>{{ $m->data_hora_marcacao }}</td><td>{{ $m->origem }}</td><td>{{ $m->status }}</td></tr>@endforeach</tbody></table>{{ $marcacoes->links() }}</div></div>

<div class="card card-custom"><div class="card-header"><h3 class="card-title">Ocorrências</h3></div><div class="card-body"><table class="table table-bordered"><thead><tr><th>Funcionário</th><th>Data</th><th>Tipo</th><th>Minutos</th><th>Descrição</th></tr></thead><tbody>@foreach($ocorrencias as $o)<tr><td>{{ $o->funcionario_id }}</td><td>{{ $o->data_referencia }}</td><td>{{ $o->tipo }}</td><td>{{ $o->minutos }}</td><td>{{ $o->descricao }}</td></tr>@endforeach</tbody></table>{{ $ocorrencias->links() }}</div></div>
@endsection
