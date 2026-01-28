@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="" id="kt_user_profile_aside">

			<div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">
				<a href="/cuponsEcommerce/create" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Novo Cupom
				</a>
			</div>
			<br>
			<h4 class="ml-3">Cupons de Desconto</h4>

			<label class="ml-3">Registros: <strong class="text-success">{{sizeof($data)}}</strong></label>
			
			<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

				<div class="pb-5" data-wizard-type="step-content">

					<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
						<div class="row">
							<div class="col-xl-12">

								<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

									<table class="datatable-table" style="max-width: 100%; overflow: scroll">
										<thead class="datatable-head">
											<tr class="datatable-row" style="left: 0px;">
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Descrição</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Código</span></th>
												<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Status</span></th>
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Tipo</span></th>
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor desconto</span></th>
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Valor minímo pedido</span></th>
												<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">Ações</span></th>
											</tr>
										</thead>
										<tbody id="body" class="datatable-body">
											@foreach($data as $c)
											<tr class="datatable-row">
												<td class="datatable-cell">
													<span class="codigo" style="width: 200px;">
														{{ $c->descricao }}
													</span>
												</td>
												
												<td class="datatable-cell">
													<span class="codigo" style="width: 100px;">
														{{ $c->codigo }}
													</span>
												</td>

												<td class="datatable-cell">
													<span class="codigo" style="width: 100px;">
														@if($c->status)
														<span class="label label-xl label-inline label-light-success">ATIVO</span>
														@else
														<span class="label label-xl label-inline label-light-danger">DESATIVADO</span>
														@endif
													</span>
												</td>
												
												<td class="datatable-cell">
													<span class="codigo" style="width: 100px;">
														{{ strtoupper($c->tipo) }}
													</span>
												</td>
												<td class="datatable-cell">
													<span class="codigo" style="width: 100px;">
														{{ number_format($c->valor, 2, ',', '.') }}
													</span>
												</td>
												<td class="datatable-cell">
													<span class="codigo" style="width: 150px;">
														{{ number_format($c->valor_minimo_pedido, 2, ',', '.') }}
													</span>
												</td>

												<td class="datatable-cell">
													<span class="codigo" style="width: 250px;">

														<!-- aqui -->

														<a class="btn btn-sm btn-warning" href="/cuponsEcommerce/edit/{{ $c->id }}">
															<i class="la la-edit"></i>				
														</a>

														<a class="btn btn-sm btn-danger" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/cuponsEcommerce/delete/{{ $c->id }}" }else{return false} })' href="#!">
															<i class="la la-trash"></i>				
														</a>
														

														<!-- adasd -->
													</span>
												</td>
											</tr>
											@endforeach
										</tbody>
									</table>
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