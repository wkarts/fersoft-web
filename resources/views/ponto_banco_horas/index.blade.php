@extends('default.layout')
@section('content')
<div class="card card-custom mb-4"><div class="card-header"><h3 class="card-title">{{ $title }}</h3></div><div class="card-body">
<form method="post" action="/ponto/banco-horas/recalcular">@csrf
<div class="row">
<div class="col-md-4"><label>Funcionário</label><select class="form-control" name="funcionario_id" required>@foreach($funcionarios as $f)<option value="{{ $f->id }}">{{ $f->nome }}</option>@endforeach</select></div>
<div class="col-md-3"><label>Data início</label><input class="form-control" type="date" name="data_inicio" required></div>
<div class="col-md-3"><label>Data fim</label><input class="form-control" type="date" name="data_fim" required></div>
<div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary btn-block">Recalcular</button></div>
</div>
</form>
</div></div>

<div class="card card-custom"><div class="card-header"><h3 class="card-title">Lançamentos</h3></div><div class="card-body"><table class="table table-bordered"><thead><tr><th>Funcionário</th><th>Data</th><th>Crédito</th><th>Débito</th><th>Saldo</th></tr></thead><tbody>@foreach($lancamentos as $l)<tr><td>{{ $l->funcionario_id }}</td><td>{{ $l->data_referencia }}</td><td>{{ $l->minutos_credito }}</td><td>{{ $l->minutos_debito }}</td><td>{{ $l->saldo_minutos }}</td></tr>@endforeach</tbody></table>{{ $lancamentos->links() }}</div></div>
@endsection
