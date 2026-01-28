@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	
	<div class="card-body">

		<form method="get" class="row">
			<div class="form-group col-lg-3">
				<input type="text" class="form-control" name="cliente" placeholder="Cliente" value="{{ $cliente }}">
			</div>

			<div class="form-group col-lg-2">
				<input type="date" class="form-control" name="data_inicio" placeholder="Data início" value="{{ $data_inicio }}">
			</div>
			<div class="form-group col-lg-2">
				<input type="date" class="form-control" name="data_fim" placeholder="Data fim" value="{{ $data_fim }}">
			</div>

			<div class="form-group col-lg-2">
				<select class="custom-select form-control" name="estado">
					<option @if(isset($estado) && $estado == 'TODOS') selected @endif value="TODOS">TODOS</option>
					<option @if(isset($estado) && $estado == 'DISPONIVEL') selected @endif value="DISPONIVEL">DISPONIVEIS</option>
					<option @if(isset($estado) && $estado == 'REJEITADO') selected @endif value="REJEITADO">REJEITADAS</option>
					<option @if(isset($estado) && $estado == 'CANCELADO') selected @endif value="CANCELADO">CANCELADAS</option>
					<option @if(isset($estado) && $estado == 'APROVADO') selected @endif value="APROVADO">APROVADAS</option>
				</select>
			</div>

			<div class="col-lg-3">
				<button class="btn btn-success">Filtrar</button>
				<a class="btn btn-info" href="/contador/pdv">Limpar</a>
			</div>
		</form>
		<h4>Lista de PDV NFCe</h4>

		<div class="table-responsive">
			<table class="table">
				<thead>
					<tr>
						<th>Cliente</th>
						<th>CPF/CPJ</th>
						<th>Total</th>
						<th>Estado</th>
						<th>Chave</th>
						<th>Núm. NFe</th>
						<th>Data emissão</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					@foreach($data as $item)
					<tr>
						<td>{{ $item->cliente ? $item->cliente->razao_social : '--' }}</td>
						<td>{{ $item->cliente ? $item->cliente->cpf_cnpj : '--' }}</td>
						<td>{{ moeda($item->valor_total-$item->desconto+$item->acrescimo) }}</td>
						<td>
							@if($item->estado == 'DISPONIVEL')
							<span class="label label-xl label-inline label-light-primary">Disponível</span>

							@elseif($item->estado == 'APROVADO')
							<span class="label label-xl label-inline label-light-success">Aprovado</span>
							@elseif($item->estado == 'CANCELADO')
							<span class="label label-xl label-inline label-light-danger">Cancelado</span>
							@else
							<span class="label label-xl label-inline label-light-warning">Rejeitado</span>
							@endif
						</td>
						<td>{{ $item->chave }}</td>
						<td>{{ $item->NfNumero }}</td>
						<td>{{ $item->estado == 'APROVADO' ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') : '--' }}</td>
						<td>
							@if($item->estado == 'APROVADO')
							<a title="Download XML" class="btn btn-sm btn-light" href="/contador/pdv-download-xml/{{$item->id}}">
								<i class="la la-download"></i>
							</a>
							@endif
						</td>

					</tr>
					@endforeach
				</tbody>
			</table>
		</div>

		@if($estado == 'APROVADO' || $estado == 'CANCELADO')
		<form method="get" action="/contador/download-xml-nfce">
			<input type="hidden" name="estado" value="{{ $estado }}">
			<input type="hidden" name="cliente" value="{{ $cliente }}">
			<input type="hidden" name="data_inicio" value="{{ $data_inicio }}">
			<input type="hidden" name="data_fim" value="{{ $data_fim }}">
			<button class="btn btn-success">
				<i class="la la-download"></i>
				Download XML
			</button>
		</form>
		@endif
		{!! $data->appends(request()->all())->links() !!}
	</div>
</div>

@endsection
