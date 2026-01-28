@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	
	<div class="card-body">

		<form method="get" class="row">
			<div class="form-group col-lg-3">
				<input type="text" class="form-control" name="razao_social" placeholder="Razão social" value="{{ $razao_social }}">
			</div>

			<div class="col-lg-4">
				<button class="btn btn-success">Filtrar</button>
				<a class="btn btn-info" href="/contador/fornecedores">Limpar</a>
			</div>
		</form>
		<h4>Lista de Fornecedores</h4>
		
		<div class="table-responsive">
			<table class="table">
				<thead>
					<tr>
						<th>Razão social</th>
						<th>CPF/CNPJ</th>
						<th>IE</th>
						<th>Rua</th>
						<th>Número</th>
						<th>Bairro</th>
						<th>Cidade</th>
					</tr>
				</thead>
				<tbody>
					@foreach($data as $item)
					<tr>
						<td>{{ $item->razao_social }}</td>
						<td>{{ $item->cpf_cnpj }}</td>
						<td>{{ $item->ie_rg }}</td>
						<td>{{ $item->rua }}</td>
						<td>{{ $item->numero }}</td>
						<td>{{ $item->bairro }}</td>
						<td>{{ $item->cidade->nome }} ({{ $item->cidade->uf }})</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>
		{!! $data->appends(request()->all())->links() !!}
	</div>
</div>

@endsection
