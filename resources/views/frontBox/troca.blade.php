@extends('default.layout')
@section('content')


<div class="card card-custom gutter-b">


	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			@if(isset($config))
			<input type="hidden" id="pass" value="{{ ($config->senha_remover)? 'true': ''; }}">
			@endif

			<div class="row">
				<div class="form-group col-lg-3 col-md-4 col-sm-4">
					<form method="get">

						<div class="input-group">
							{{-- <input type="text" placeholder="Data" id="numeros" name="data" class="form-control date-input" value="{{$data}}"> --}}
							<input type="text" name="data" placeholder="Data da Troca" class="form-control" value="{{$data}}" id="kt_datepicker_3">
							<div class="input-group-append">
								<button class="btn btn-light-primary" type="submit">Buscar</button>
							</div>
						</div>

					</form>
				</div>
			</div>



			<br>
			<h4>Troca de PDV</h4>
			<div class="row">
				<div class="col-lg-2 col-xl-2">
					<a style="width: 100%" class="btn btn-light-success" data-toggle="modal" data-target="#modal3">
						<i class="la la-sync"></i>
						Nova Troca
					</a>
				</div>
			</div>

			<div class="row">
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
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 70px;">#</span></th>
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Venda Original</span></th>
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Venda Original N.º</span></th>
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Venda Gerada</span></th>
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Venda Gerada N.º</span></th>
																<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Prod. Removidos</span></th>
																<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Prod. Adicionados</span></th>
																<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Data</span></th>
															</tr>
														</thead>

														<tbody class="datatable-body">
															<?php 
															$total = 0;
															?>
															@foreach($trocas as $v)

															<tr class="datatable-row" style="left: 0px;">
																<td class="datatable-cell"><span class="codigo" style="width: 70px;">{{$v->id}}</span>
																</td>
																<td class="datatable-cell">
																	<span class="codigo" style="width: 150px;">
																		<a href="{{ url('/frenteCaixa/filtro?id='.$v->antiga_venda_caixas_id)}}" target="_blank">Acessar &raquo;</a>
																	</span>
																</td>
																<td class="datatable-cell">
																	<span class="codigo" style="width: 100px;">
																		{{ $v->antiga_venda_caixas_id}}
																	</span>
																</td>
																<td class="datatable-cell">
																	<span class="codigo" style="width: 150px;">
																	@if($v->nova_venda_caixas_id !== $v->antiga_venda_caixas_id)
																		<a href="{{ url('/frenteCaixa/filtro?id='.$v->nova_venda_caixas_id)}}" target="_blank">Acessar &raquo;</a>
																	@else
																		--
																	@endif
																	</span>
																</td>
																<td class="datatable-cell">
																	<span class="codigo" style="width: 100px;">
																	@if($v->nova_venda_caixas_id !== $v->antiga_venda_caixas_id)
																		{{ $v->nova_venda_caixas_id }}
																	@else
																		--
																	@endif
																	</span>
																</td>
																<td class="datatable-cell"><span class="codigo" style="width: 200px;">{{ $v->prod_removidos }}</span>
																<td class="datatable-cell"><span class="codigo" style="width: 200px;">{{ $v->prod_adicionados }}</span>
																</td>
																<td class="datatable-cell"><span class="codigo" style="width: 150px;">{{ \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i:s')}}</span>
																</td>
																
															</tr>

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
								<!--end: Wizard Step 2-->



							</form>

						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">CANCELAMENTO DE NFCe</h5>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<input type="hidden" id="venda_id" name="">

			<div class="modal-body">
				<div class="row">
					<div class="form-group validated col-sm-12 col-lg-12 col-12">
						<label class="col-form-label" id="">Justificativa</label>
						<input type="text" placeholder="Justificativa" id="justificativa" name="justificativa" class="form-control" value="">
					</div>
				</div>
			</div>
			<div class="modal-footer">

				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="button" id="btn_cancelar_nfce" onclick="cancelar()" class="btn btn-light-info font-weight-bold spinner-white spinner-right">Cancelar</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal3" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">REALIZAR NOVA TROCA</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="form-group validated col-sm-12 mb-2">
						<h4>Forma de Identificação da Venda:</h4>
					</div>
					<div class="form-group validated col-sm-12 col-Numero NF Inicial-6">
						<label class="col-form-label" id="">Número de NFCe</label>
						<div class="">
							<input type="text" id="nfceIdentificador" placeholder="" class="form-control" value="">
						</div>
					</div>
					<div class="form-group validated col-sm-12 mb-1">
						<h4>OU</h4>
					</div>
					<div class="form-group validated col-sm-12 col-lg-12">
						<label class="col-form-label" id="">Código da Venda</label>
						<div class="">
							<input type="text" id="idIdentificador" placeholder="" class="form-control" value="">
						</div>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="button" id="novaTroca" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Abrir</button>
			</div>
		</div>
	</div>
</div>

@endsection	