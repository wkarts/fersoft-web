

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
									<form method="get" action="/inventario/pesquisaItem">
										<div class="row align-items-center">
											<div class="col-lg-5 col-xl-5">
												<div class="row align-items-center">
													<div class="col-md-12 my-2 my-md-0">
														<div class="input-group">
															<input type="text" name="pesquisa" class="form-control" placeholder="Pesquisa produto" id="kt_datatable_search_query" value="{{{ isset($pesquisa) ? $pesquisa : ''}}}">
															
														</div>
													</div>
												</div>
											</div>
											<input type="hidden" name="inventario_id" value="{{$inventario->id}}">
											<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
												<button class="btn btn-light-primary px-6 font-weight-bold">Pesquisa</button>
											</div>
										</div>

									</form>

									<br>
									<h4>Itens do inventário: <strong>{{$inventario->referencia}}</strong></h4>
									
									<table class="datatable-table" style="max-width: 100%; overflow: scroll">
										<thead class="datatable-head">
											<tr class="datatable-row" style="left: 0px;">
												
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Produto</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Categoria</span></th>
												<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Quanitdade</span></th>
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Valor de Compra</span></th>
												
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Valor de Venda</span></th>

												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Subtotal Compra</span></th>
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Subtotal Venda</span></th>
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Observação</span></th>
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Ações</span></th>
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
													@if($e->produto->unidade_venda == 'UN' || $e->produto->unidade_venda == 'UNID')
													{{number_format($e->quantidade, 0, '.', '')}}
													@else
													{{number_format($e->quantidade, 3, '.', ',')}}
													@endif
												</span></td>
												
												<td class="datatable-cell"><span class="codigo" style="width: 80px;">
													{{ number_format($e->produto->valor_compra, 2, ',', '.') }} {{$e->produto->unidade_compra}}
												</span></td>

												<td class="datatable-cell"><span class="codigo" style="width: 80px;">
													{{ number_format($e->produto->valor_venda, 2, ',', '.') }} {{$e->produto->unidade_venda}}
												</span></td>

												<td class="datatable-cell"><span class="codigo" style="width: 120px;">
													{{ number_format($e->produto->valor_compra * $e->quantidade, 2, ',', '.') }}
												</span></td>
												<td class="datatable-cell"><span class="codigo" style="width: 120px;">
													{{ number_format($e->produto->valor_venda * $e->quantidade, 2, ',', '.') }}
												</span></td>
												<td class="datatable-cell"><span class="codigo" style="width: 150px;">
													{{ $e->observacao }}
												</span></td>
												<td class="datatable-cell"><span class="codigo" style="width: 120px;">
													<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/inventario/itensDelete/{{$e->id}}" }else{return false} })' href="#!" class="btn btn-danger btn-sm">
														<i class="la la-trash"></i>
													</a>
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
												<h3 class="card-title">Total em estoque compra: R$ <strong style="margin-left: 3px;" class="text-danger">{{number_format($totaliza['compra'], 2, ',', '.')}}</strong></h3>

												<h3 class="card-title">Total em estoque venda: R$ <strong style="margin-left: 3px;" class="text-success">{{number_format($totaliza['venda'], 2, ',', '.')}}</strong></h3>

												@isset($pesquisa)
												<form method="get" action="/inventario/imprimirFiltro" target="_blank">
													<input type="hidden" name="pesquisa" value="{{$pesquisa}}">
													<input type="hidden" name="inventario_id" value="{{$inventario->id}}">
													<button class="btn btn-info" type="submit">
														<i class="la la-print"></i>
														Imprimir
													</button>
												</form>
												@else
												<a target="_blank" class="btn btn-info" href="/inventario/imprimir/{{$inventario->id}}">
													<i class="la la-print"></i>
													Imprimir
												</a>
												<a class="btn btn-danger" href="/inventario/comparaEstoque/{{$inventario->id}}">
													<i class="la la-stream"></i>
													Comparação com estoque
												</a>
												@endif

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
