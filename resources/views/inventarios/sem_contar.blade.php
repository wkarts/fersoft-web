

@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
	
	<div class="card-body @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">

		<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
			<div class="card card-custom gutter-b example example-compact">
				<div class="card-header">

					<div class="col-xl-12">
						<div class="row">
							<div class="col-xl-12">
								<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
									<br>

									<h4>Itens pendentes: <strong>{{$inventario->referencia}}</strong></h4>

									<table class="datatable-table" style="max-width: 100%; overflow: scroll">
										<thead class="datatable-head">
											<tr class="datatable-row" style="left: 0px;">
												
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Produto</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Categoria</span></th>
												
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Valor de Compra</span></th>
												
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Valor de Venda</span></th>
												
											</tr>
										</thead>
										<tbody class="datatable-body">
											
											@foreach($produtosSemContar as $e)
											<tr class="datatable-row" style="left: 0px;">
												<td class="datatable-cell"><span class="codigo" style="width: 200px;">
													{{$e->nome}} 
													{{$e->grade ? " (" . $e->str_grade . ")" : ""}}
												</span></td>
												<td class="datatable-cell"><span class="codigo" style="width: 80px;">{{$e->categoria->nome}}</span></td>
												
												
												<td class="datatable-cell"><span class="codigo" style="width: 80px;">
													{{ number_format($e->valor_compra, 2, ',', '.') }} {{$e->unidade_compra}}
												</span></td>

												<td class="datatable-cell"><span class="codigo" style="width: 80px;">
													{{ number_format($e->valor_venda, 2, ',', '.') }} {{$e->unidade_venda}}
												</span></td>

												
											</tr>
											@endforeach
										</tbody>
									</table>
								</div>
							</div>
						</div>
						

						<div class="card-body">
							<div class="row">
								<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
									<div class="card card-custom gutter-b example example-compact">
										<div class="card-header">

											<div class="card-body">
												
												<a target="_blank" class="btn btn-info" href="/inventario/imprimirPendentes/{{$inventario->id}}">
													<i class="la la-print"></i>
													Imprimir
												</a>
											</div>

										</div>
									</div>
								</div>
							</div>
						</div>

					</div>
				</div>

			</div>
		</div>
	</div>
</div>

@endsection
