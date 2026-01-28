@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			<form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="/nuvemshop/pedidos">
				<div class="row align-items-center">

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Cliente</label>
						<div class="">
							<div class="input-group">
								<input type="text" name="cliente" class="form-control" value="{{{isset($cliente) ? $cliente : ''}}}" />
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data Inicial</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_inicial" class="form-control date-out" readonly value="{{{isset($data_inicial) ? $data_inicial : ''}}}" id="kt_datepicker_3" />
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-calendar"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data Final</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_final" class="form-control" readonly value="{{{isset($data_final) ? $data_final : ''}}}" id="kt_datepicker_3" />
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-calendar"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Pesquisa</button>
					</div>
				</div>
			</form>
			<br>
			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Lista de Pedidos Nuvem Shop</h4>

			<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Registros: <strong class="text-success">{{sizeof($pedidos)}}</strong></label>

		</div>

		<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
			<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

				<div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
					<!--begin: Wizard Nav-->

					<div class="wizard-nav">

						<div class="wizard-steps px-8 py-8 px-lg-15 py-lg-3">
							<!--begin::Wizard Step 1 Nav-->
							
							<!--end::Wizard Step 1 Nav-->

						</div>
					</div>


					<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

						<!--begin: Wizard Form-->
						<form class="form fv-plugins-bootstrap fv-plugins-framework" id="kt_form">
							<!--begin: Wizard Step 1-->
							<div class="pb-5" data-wizard-type="step-content">

								<!-- Inicio da tabela -->

								<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
									<div class="row">
										<div class="col-xl-12">

											<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

												<table class="datatable-table" style="max-width: 100%; overflow: scroll">
													<thead class="datatable-head">
														<tr class="datatable-row" style="left: 0px;">
															
															<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">ID</span></th>
															<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Cliente</span></th>
															<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Data</span></th>
															

															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">NFe</span></th>
															
															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor Total</span></th>
															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor Frete</span></th>
															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Desconto</span></th>
															
															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Ações</span></th>
														</tr>
													</thead>

													<tbody id="body" class="datatable-body">
														<?php $total = 0; ?>
														@foreach($pedidos as $p)

														<tr class="datatable-row">
															
															<td class="datatable-cell"><span class="codigo" style="width: 120px;" id="id">{{$p->id}}</span>
															</td>

															<td class="datatable-cell"><span class="codigo" style="width: 200px;" id="id">{{ $p->customer->name }}</span>
															</td>

															<td class="datatable-cell">
																<span class="codigo" style="width: 120px;" id="id">
																	{{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y H:i') }}
																</span>
															</td>
															
															<td class="datatable-cell"><span class="codigo" style="width: 100px;" id="id">{{$p->numero_nfe > 0 ? $p->numero_nfe : '--'}}</span>
															</td>
															
															<td class="datatable-cell">
																<span class="codigo" style="width: 100px;" id="id">
																	{{number_format($p->total, 2, ',', '.')}}
																</span>
															</td>

															<td class="datatable-cell">
																<span class="codigo" style="width: 100px;" id="id">
																	{{number_format($p->shipping_cost_customer, 2, ',', '.')}}
																</span>
															</td>

															<td class="datatable-cell">
																<span class="codigo" style="width: 100px;" id="id">
																	{{number_format($p->discount, 2, ',', '.')}}
																</span>
															</td>

															<td>
																<div class="row">
																	<span style="width: 200px;">
																		<a class="btn btn-sm btn-info" href="/nuvemshop/detalhar/{{ $p->id }}">
																			<i class="la la-file"></i>
																		</a>

																		<a class="btn btn-sm btn-danger" onclick='swal("Atenção!", "Deseja remover este pedido?", "warning").then((sim) => {if(sim){ location.href="/nuvemshop/delete/{{ $p->id }}" }else{return false} })' href="#!" class="btn btn-danger">
																			<i class="la la-trash"></i>
																		</a>
																	</span>
																</div>
															</td>
														</tr>
														<?php 
														$total += $p->total;
														?>
														@endforeach

													</tbody>
												</table>
											</div>
										</div>

									</div>
								</div>
								<!-- Fim da tabela -->
							</div>
						</form>
					</div>
				</div>
				
			</div>
		</div>
		@if(!isset($cliente))
		<div class="row">
			<div class="col-sm-1">
				@if($page > 1)
				<a class="btn btn-light-primary" href="/nuvemshop/pedidos?page={{$page-1}}" class="float-left">
					<i class="la la-angle-double-left"></i>
				</a>
				@endif
			</div>
			<div class="col-sm-10"></div>
			<div class="col-sm-1">
				<a class="btn btn-light-primary" href="/nuvemshop/pedidos?page={{$page+1}}" class="float-right">
					<i class="la la-angle-double-right"></i>
				</a>
			</div>
		</div>
		@endif
	</div>
</div>

@endsection	