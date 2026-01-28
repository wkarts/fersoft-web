@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			@if(isset($config))
			<input type="hidden" id="pass" value="{{ $config->senha_remover }}">
			@endif
			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			<form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="/trocas/filtro">
				<div class="row align-items-center">

					<div class="form-group col-lg-4 col-md-4 col-sm-6">
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

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Status do crédito</label>
						<div class="">
							<div class="input-group date">
								<select class="custom-select form-control" id="status" name="status">
									<option value="">TODOS</option>
									<option @if(isset($status) && $status == -1) selected @endif value="-1">PENDENTE</option>
									<option @if(isset($status) && $status == 1) selected @endif value="1">FINALIZADO</option>
									
								</select>
							</div>
						</div>
					</div>

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Pesquisa</button>
					</div>
				</div>
			</form>
			<br>
			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Lista de Trocas/Devoluções</h4>

			<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Registros: <strong class="text-success">{{sizeof($trocas)}}</strong></label>
			<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
				<div class="form-group col-lg-3 col-md-4 col-sm-6">
					<a href="/trocas/nova" class="btn btn-success">
						<i class="la la-plus"></i>
						Nova Troca
					</a>
				</div>
			</div>


		</div>

		<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
			<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

				<div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
					<!--begin: Wizard Nav-->

					<div class="wizard-nav">

						<div class="wizard-steps px-8 py-8 px-lg-15 py-lg-3">
							<!--begin::Wizard Step 1 Nav-->
							<div class="wizard-step" data-wizard-type="step" data-wizard-state="done">
								<div class="wizard-label">
									<h3 class="wizard-title">
										<span>
											<i style="font-size: 40px" class="la la-table"></i>
											Tabela
										</span>
									</h3>
									<div class="wizard-bar"></div>
								</div>
							</div>
							<!--end::Wizard Step 1 Nav-->
							<!--begin::Wizard Step 2 Nav-->
							<div class="wizard-step" data-wizard-type="step" data-wizard-state="current">
								<div class="wizard-label" id="grade">
									<h3 class="wizard-title">
										<span>
											<i style="font-size: 40px" class="la la-tablet"></i>
											Grade
										</span>
									</h3>
									<div class="wizard-bar"></div>
								</div>
							</div>

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
															
															<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 70px;">ID</span></th>
															<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Cliente</span></th>
															<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Data de registro</span></th>
															<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Tipo de pagamento</span></th>

															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Status</span></th>

															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Usuário</span></th>
															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor da Venda</span></th>

															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor da Troca</span></th>

															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Ações</span></th>
														</tr>
													</thead>

													<tbody id="body" class="datatable-body">
														@php 
														$somaTotal = 0;
														$somaCredito = 0;
														@endphp
														@foreach($trocas as $t)

														<tr class="datatable-row">
															
															<td class="datatable-cell"><span class="codigo" style="width: 70px;" id="id">{{$t->id}}</span>
															</td>
															<td class="datatable-cell"><span class="codigo" style="width: 200px;">{{ $t->cliente ? $t->cliente->razao_social : 'Consumidor final' }}</span>
															</td>
															<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ \Carbon\Carbon::parse($t->created_at)->format('d/m/Y H:i:s')}}</span>
															</td>

															<td class="datatable-cell">
																<span class="codigo" style="width: 100px;">
																	@if($t->venda())
																	@if($t->venda()->tipo_pagamento == '99')

																	@else
																	{{$t->venda()->getTipoPagamento($t->venda()->tipo_pagamento)}}
																	@endif
																	@endif

																</span>
															</td>
															<td class="datatable-cell"><span class="codigo" style="width: 100px;" id="estado_{{$t->id}}">{{ $t->status ? 'Finalizado' : 'Pendente' }}</span>
															</td>
															
															<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ $t->usuario->nome }}</span>
															</td>
															<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($t->valor_total, $casasDecimais, ',', '.') }}</span>
															</td>

															<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($t->valor_credito, 2, ',', '.') }}</span>
															</td>

															<td>
																<div class="row">
																	<span style="width: 80px;">

																		@if(!$t->status)
																		<a class="btn btn-danger" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ removerTroca("{{$t->id}}") }else{return false} })' href="#!">
																			<i class="la la-trash"></i>
																		</a>

																		@endif
																		
																		
																	</span>
																</div>
															</td>

														</tr>
														<?php 
														$somaTotal += $t->valor_total;
														if(!$t->status)
															$somaCredito += $t->valor_credito;
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

							<!--end: Wizard Step 1-->
							<!--begin: Wizard Step 2-->
							<div class="pb-5" data-wizard-type="step-content">

								<!-- Inicio do card -->

								<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
									<div class="row">

										@foreach($trocas as $t)
										<div class="col-sm-6 col-lg-6 col-md-6 col-xl-6">

											<div class="card card-custom gutter-b example example-compact">
												<div class="card-header">
													<div class="card-title">
														<h3 style="width: 230px; font-size: 15px; height: 10px;" class="card-title">
															<strong class="text-success"> </strong>

															{{$t->cliente ? $t->cliente->razao_social : 'Consumidor final'}}

														</h3>

													</div>
													<div class="card-toolbar">
														<div class="dropdown dropdown-inline" data-toggle="tooltip" title="" data-placement="left" data-original-title="Ações">
															<a href="#" class="btn btn-hover-light-primary btn-sm btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
																<i class="fa fa-ellipsis-h"></i>
															</a>
															<div class="dropdown-menu p-0 m-0 dropdown-menu-md dropdown-menu-right">
																<!--begin::Navigation-->
																<ul class="navi navi-hover">
																	<li class="navi-header font-weight-bold py-4">
																		<span class="font-size-lg">Ações:</span>
																	</li>
																	<li class="navi-separator mb-3 opacity-70"></li>

																	@if(!$t->status)
																	<li class="navi-item">
																		<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ removerVenda("{{$t->id}}") }else{return false} })' href="#!" class="navi-link">
																			<span class="navi-text">
																				<span class="label label-xl label-inline label-light-danger">Remover</span>
																			</span>
																		</a>
																	</li>
																	@endif



																</ul>
																<!--end::Navigation-->
															</div>
														</div>

													</div>
												</div>

												<div class="card-body">

													<div class="kt-widget__info">
														<span class="kt-widget__label">Nome fantasia:</span>
														<a target="_blank" class="kt-widget__data text-success">
															{{$t->cliente ? $t->cliente->nome_fantasia : ''}}
														</a>
													</div>

													<div class="kt-widget__info">
														<span class="kt-widget__label">Valor Integral:</span>
														<a target="_blank" class="kt-widget__data text-success">
															R$ {{ number_format($t->valor_total, 2, ',', '.') }}
														</a>
													</div>

													<div class="kt-widget__info">
														<span class="kt-widget__label">Valor crédito:</span>
														<a target="_blank" class="kt-widget__data text-success">
															R$ {{ number_format($t->valor_credito, 2, ',', '.') }}
														</a>
													</div>


													<div class="kt-widget__info">
														<span class="kt-widget__label">Data:</span>
														<a target="_blank" class="kt-widget__data text-success">
															{{ \Carbon\Carbon::parse($t->created_at)->format('d/m/Y H:i:s')}}
														</a>
													</div>


													<div class="kt-widget__info">
														<span class="kt-widget__label">Status:</span>
														<a target="_blank" class="kt-widget__data text-success">

															@if($t->status)
															<span class="label label-xl label-inline label-light-success">Finalizado</span>
															@else
															<span class="label label-xl label-inline label-light-warning">Pendente</span>
															@endif
														</a>
													</div>

													<div class="kt-widget__info">
														<span class="kt-widget__label">Usuário:</span>
														<a target="_blank" class="kt-widget__data text-success">
															{{ $t->usuario->nome }}
														</a>
													</div>

												</div>
											</div>

										</div>
										@endforeach

									</div>
								</div>
							</div>
							<!--end: Wizard Step 2-->
							<div class="d-flex justify-content-between align-items-center flex-wrap">
								<div class="d-flex flex-wrap py-2 mr-3">
									@if(isset($links))
									{{$trocas->links()}}
									@endif
								</div>
							</div>

							<h4>Soma valor de troca: <strong class="text-info">R$ {{number_format($somaCredito, 2, ',', '.')}}</strong></h4>
						</form>

					</div>
				</div>
			</div>
		</div>
	</div>

</div>

@endsection

@section('javascript')
<script type="text/javascript">
	function removerTroca(id){

		let senha = $('#pass').val()
		if(senha != ""){

			swal({
				title: 'Remover troca',
				text: 'Informe a senha!',
				content: {
					element: "input",
					attributes: {
						placeholder: "Digite a senha",
						type: "password",
					},
				},
				button: {
					text: "Cancelar!",
					closeModal: false,
					type: 'error'
				},
				confirmButtonColor: "#DD6B55",
			}).then(v => {
				if(v.length > 0){
					$.get(path+'configNF/verificaSenha', {senha: v})
					.then(
						res => {
							location.href="/trocas/delete/"+id;
						},
						err => {
							swal("Erro", "Senha incorreta", "error")
							.then(() => {
								location.reload()
							});
						}
						)
				}else{
					location.reload()
				}
			})
		}else{
			location.href="/trocas/delete/"+id;
		}
	}


</script>
@endsection