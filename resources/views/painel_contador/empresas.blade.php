@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	
	<div class="card-body">

		
		<h4>Lista de Empresas</h4>

		<div class="table-responsive">
			<table class="table">
				<thead>
					<tr>
						<th>Nome</th>
						<th>CPF/CNPJ</th>
						<th>Rua</th>
						<th>Número</th>
						<th>Bairro</th>
						<th>Cidade</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					@foreach($data as $item)
					<tr>
						<td>{{ $item->nome }}</td>
						<td>{{ $item->cnpj }}</td>
						<td>{{ $item->rua }}</td>
						<td>{{ $item->numero }}</td>
						<td>{{ $item->bairro }}</td>
						<td>{{ $item->cidade }} ({{ $item->uf }})</td>
						<td>

							<form method="post" action="/contador/set-empresa">
								@csrf
								<a title="Detalhes da Empresa" class="btn btn-info btn-sm" href="/contador/empresa-detalhe/{{ $item->id }}">
									<i class="la la-file"></i>
								</a>
								@if($empresaSelecionada != $item->id)
								<input type="hidden" name="empresa" value="{{$item->id}}">
								<button title="Selecionar Empresa" class="btn btn-success btn-sm" type="submit">
									<i class="la la-check"></i>
								</button>
								@endif

							</form>
						</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>

	</div>
</div>

@endsection
