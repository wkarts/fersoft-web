@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	
	<div class="card-body">

		<form method="get" class="row">
			<div class="form-group col-lg-3">
				<input type="text" class="form-control" name="nome" placeholder="Nome" value="{{ $nome }}">
			</div>
			<div class="form-group col-lg-2">
				<input type="text" class="form-control" name="cfop" placeholder="CFOP" value="{{ $cfop }}">
			</div>

			<div class="col-lg-4">
				<button class="btn btn-success">Filtrar</button>
				<a class="btn btn-info" href="/contador/produtos">Limpar</a>
			</div>
		</form>
		<h4>Lista de Produtos</h4>
		
		<div class="table-responsive">
			<table class="table">
				<thead>
					<tr>
						<th>Nome</th>
						<th>Valor de compra</th>
						<th>Valor de venda</th>
						<th>Código de barras</th>
						<th>Unidade de venda</th>
						<th>CFOP Interno</th>
						<th>CFOP Externo</th>
						<th>%ICMS</th>
						<th>%PIS</th>
						<th>%COFINS</th>
						<th>%IPI</th>
						<th>CST/CSOSN</th>
						<th>CST PIS</th>
						<th>CST COFINS</th>
						<th>CST IPI</th>
						<th>NCM</th>
						<th>CEST</th>
					</tr>
				</thead>
				<tbody>
					@foreach($data as $item)
					<tr>
						<td>{{ $item->nome }}</td>
						<td>{{ moeda($item->valor_compra) }}</td>
						<td>{{ moeda($item->valor_venda) }}</td>
						<td>{{ $item->codBarras }}</td>
						<td>{{ $item->unidade_venda }}</td>
						<td>{{ $item->CFOP_saida_estadual }}</td>
						<td>{{ $item->CFOP_saida_inter_estadual }}</td>
						<td>{{ $item->perc_icms }}</td>
						<td>{{ $item->perc_pis }}</td>
						<td>{{ $item->perc_cofins }}</td>
						<td>{{ $item->perc_ipi }}</td>
						<td>{{ $item->CST_CSOSN }}</td>
						<td>{{ $item->CST_PIS }}</td>
						<td>{{ $item->CST_COFINS }}</td>
						<td>{{ $item->CST_IPI }}</td>
						<td>{{ $item->NCM }}</td>
						<td>{{ $item->CEST }}</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>
		{!! $data->appends(request()->all())->links() !!}
	</div>
</div>

@endsection
