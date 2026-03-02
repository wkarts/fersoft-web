@extends('default.layout')
@section('content')
<div class="row">
<div class="col-md-6">
<div class="card card-custom mb-4"><div class="card-header"><h3 class="card-title">Fechar Competência</h3></div><div class="card-body">
<form method="post" action="/ponto/fechamentos/fechar">@csrf
<label>Competência (YYYY-MM)</label><input class="form-control" name="competencia" required>
<label class="mt-2">Observações</label><textarea class="form-control" name="observacoes"></textarea>
<button class="btn btn-primary mt-2">Fechar</button>
</form>
</div></div>
</div>
<div class="col-md-6">
<div class="card card-custom mb-4"><div class="card-header"><h3 class="card-title">Reabrir Competência</h3></div><div class="card-body">
<form method="post" action="/ponto/fechamentos/reabrir">@csrf
<label>Competência (YYYY-MM)</label><input class="form-control" name="competencia" required>
<button class="btn btn-warning mt-2">Reabrir</button>
</form>
</div></div>
</div>
</div>

<div class="card card-custom"><div class="card-header"><h3 class="card-title">Histórico</h3></div><div class="card-body"><table class="table table-bordered"><thead><tr><th>Competência</th><th>Status</th><th>Versão</th><th>Fechado em</th><th>Reaberto em</th></tr></thead><tbody>@foreach($fechamentos as $f)<tr><td>{{ $f->competencia }}</td><td>{{ $f->status }}</td><td>{{ $f->versao }}</td><td>{{ $f->fechado_em }}</td><td>{{ $f->reaberto_em }}</td></tr>@endforeach</tbody></table>{{ $fechamentos->links() }}</div></div>
@endsection
