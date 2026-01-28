@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<input type="hidden" id="pass" value="{{ $config->senha_remover ?? '' }}">
	<div class="card-body @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
		<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
			<div class="card card-custom gutter-b example example-compact">
				<div class="card-body">

					<div class="col-xl-12">
						<div class="row">
							<div class="col-xl-12">
								<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
									<br>
									<form method="get" action="/transferencia/search">
										<div class="row align-items-center">
											<div class="form-group col-lg-4 col-12">
												<div>
													<div class="input-group">
														<label class="col-form-label">Produto</label>
														<div class="input-group">
															<input type="text" name="pesquisa" class="form-control" placeholder="Pesquisa produto" id="kt_datatable_search_query" value="{{{ isset($pesquisa) ? $pesquisa : ''}}}">

														</div>
													</div>
												</div>
											</div>

											<div class="col-lg-2 col-xl-2 mt-3">
												<button class="btn btn-light-primary px-6 font-weight-bold">Filtrar</button>
											</div>
										</div>

									</form>

									<br>
									<h4>{{ $title }}</h4>

									<table class="datatable-table" style="max-width: 100%; overflow: scroll">
										<thead class="datatable-head">
											<tr class="datatable-row" style="left: 0px;">
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Origem</span></th>
												<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Destino</span></th>
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Data</span></th>
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Estado Fiscal</span></th>
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Ações</span></th>
											</tr>
										</thead>
										<tbody class="datatable-body">
											
											@foreach($data as $item)

											<tr class="datatable-row" style="left: 0px;">

												<td class="datatable-cell">
													<span class="codigo" style="width: 100px;">
														{{ $item->filial_saida ? $item->filial_saida->descricao : 'Matriz' }}
													</span>
												</td>

												<td class="datatable-cell">
													<span class="codigo" style="width: 100px;">
														{{ $item->filial_entrada ? $item->filial_entrada->descricao : 'Matriz' }}
													</span>
												</td>

												<td class="datatable-cell">
													<span class="codigo" style="width: 150px;">
														{{ __date($item->created_at) }}
													</span>
												</td>

												<td class="datatable-cell">
													<span class="codigo" style="width: 120px;">
														@if($item->estado == 'novo')
														<span class="label label-xl label-inline label-light-primary">Novo</span>
														@elseif($item->estado == 'aprovado')
														<span class="label label-xl label-inline label-light-success">Aprovado</span>
														@elseif($item->estado == 'cancelado')
														<span class="label label-xl label-inline label-light-danger">Cancelado</span>
														@else
														<span class="label label-xl label-inline label-light-warning">Rejeitado</span>
														@endif
													</span>
												</td>

												<td class="datatable-cell">
													<span class="codigo" style="width: 150px;">
														<a title="Ver transferencia" href="/transferencia/view/{{ $item->id }}" class="btn btn-sm btn-warning">
															<i class="la la-list"></i>
														</a>
														<a target="_blank" href="/transferencia/print/{{ $item->id }}" class="btn btn-sm btn-info">
															<i class="la la-print"></i>
														</a>
													</span>
												</td>
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
								{{$data->links()}}
								@endif
							</div>
						</div>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>

@endsection
