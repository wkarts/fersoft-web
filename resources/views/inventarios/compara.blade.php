

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

									<h4>Itens do inventário: <strong>{{$inventario->referencia}}</strong></h4>

									<table class="datatable-table" style="max-width: 100%; overflow: scroll">
										<thead class="datatable-head">
											<tr class="datatable-row" style="left: 0px;">
												
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Produto</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Categoria</span></th>
												<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Quantidade</span></th>
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Valor de Compra</span></th>
												
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Valor de Venda</span></th>
												
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Observação</span></th>
												
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Total</span></th>
											</tr>
										</thead>
										<tbody class="datatable-body">
											<?php 
											$subtotalCompra = 0;
											$subtotalVenda = 0;
											?>
											@foreach($itens as $e)
											<tr class="datatable-row" style="left: 0px;">
												<td class="datatable-cell"><span class="codigo" style="width: 200px;">
													{{$e->produto->nome}} 
													{{$e->produto->grade ? " (" . $e->produto->str_grade . ")" : ""}}
												</span></td>
												<td class="datatable-cell"><span class="codigo" style="width: 80px;">{{$e->produto->categoria->nome}}</span></td>
												<td class="datatable-cell"><span class="codigo" style="width: 100px;">
													{{$e->quantidade - $e->produto->estoqueAtual()}}
												</span></td>
												
												<td class="datatable-cell"><span class="codigo" style="width: 80px;">
													{{ number_format($e->produto->valor_compra, 2, ',', '.') }} {{$e->produto->unidade_compra}}
												</span></td>

												<td class="datatable-cell"><span class="codigo" style="width: 80px;">
													{{ number_format($e->produto->valor_venda, 2, ',', '.') }} {{$e->produto->unidade_venda}}
												</span></td>

												
												<td class="datatable-cell"><span class="codigo" style="width: 150px;">
													{{ $e->observacao }}
												</span></td>
												<td class="datatable-cell"><span class="codigo" style="width: 120px;">
													{{ number_format($e->produto->valor_venda * ($e->quantidade - $e->produto->estoqueAtual()), 2, ',', '.') }}
												</span></td>
												
												
											</tr>
											@endforeach
										</tbody>
									</table>
								</div>
							</div>
						</div>
						<div class="d-flex justify-content-between align-items-center flex-wrap">
							<div class="d-flex flex-wrap py-2 mr-3">
								@if(isset($links))
								{{$itens->links()}}
								@endif
							</div>
						</div>

						<div class="card-body">
							<div class="row">
								<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
									<div class="card card-custom gutter-b example example-compact">
										<div class="card-header">

											<div class="card-body">
												
												<a target="_blank" class="btn btn-info" href="/inventario/imprimirCompara/{{$inventario->id}}">
													<i class="la la-print"></i>
													Imprimir
												</a>
												<a class="btn btn-danger" href="/inventario/pendentes/{{$inventario->id}}">
													<i class="la la-box"></i>
													Produtos pendentes: {{sizeof($produtosSemContar)}}
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
