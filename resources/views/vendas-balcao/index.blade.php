@extends('default.layout')
@section('css')
<style type="text/css">
	.row-table:hover{
		cursor: pointer;
	}

	body.loading .modal-loading {
		display: block;
	}

	.modal-loading {
		display: none;
		position: fixed;
		z-index: 10000;
		top: 0;
		left: 0;
		height: 100%;
		width: 100%;
		background: rgba(255, 255, 255, 0.8)
		url("/loading.gif") 50% 50% no-repeat;
	}
</style>
@endsection
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			@if(isset($config))
			<input type="hidden" id="pass" value="{{ $config->senha_remover }}">
			@endif
			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			<form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="">
				<div class="row align-items-center">

					<!-- <div class="form-group col-lg-3 col-md-4 col-sm-6">
						<label class="col-form-label">Cliente</label>
						<div class="">
							<div class="input-group">
								<input type="text" name="cliente" class="form-control" value="{{{isset($cliente) ? $cliente : ''}}}" />
							</div>
						</div>
					</div> -->

					<div class="form-group validated col-lg-3 col-12">
						<label class="col-form-label" id="">Cliente</label>
						<div class="input-group">

							<select class="form-control select2" id="kt_select2_3" name="cliente">
								<option value="">Selecione o cliente</option>
								@foreach($clientes as $c)
								<option value="{{$c->id}}" @isset($cliente) @if($cliente == $c->id) selected @endif @endif>{{$c->id}} - {{$c->razao_social}}/{{$c->nome_fantasia}} ({{$c->cpf_cnpj}})</option>
								@endforeach
							</select>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data Inicial</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_inicial" class="form-control date-out date-input" value="{{{isset($dataInicial) ? $dataInicial : ''}}}" id="kt_datepicker_3" />
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
								<input type="text" name="data_final" class="form-control date-input" value="{{{isset($dataFinal) ? $dataFinal : ''}}}" id="kt_datepicker_3" />
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-calendar"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Estado</label>
						<div class="">
							<div class="input-group date">
								<select class="custom-select form-control" id="estado" name="estado">
									<option @isset($estado) @if($estado == '0') selected @endif @endif value="0">Pendentes</option>
									<option @isset($estado) @if($estado == '1') selected @endif @endif value="1">Finalizados</option>
									<option @isset($estado) @if($estado == '-1') selected @endif @endif value="-1">Todos</option>

								</select>
							</div>
						</div>
					</div>

					<div class="form-group col-lg-1 col-md-2 col-sm-3">
						<label class="col-form-label">Código</label>
						<div class="">
							<div class="input-group">
								<input type="text" name="codigo_venda" class="form-control" value="{{{isset($codigo_venda) ? $codigo_venda : ''}}}" />
							</div>
						</div>
					</div>

					@if(empresaComFilial())
					{!! __view_locais_select_filtro("Local", isset($filial_id) ? $filial_id : '') !!}
					@endif

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Filtrar</button>
					</div>
				</div>
			</form>
			<br>

			

			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Lista de Vendas Balcão</h4>

			<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Registros: <strong class="text-success">{{sizeof($vendas)}}</strong></label>

		</div>

		<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
			<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

				<div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
					<!--begin: Wizard Nav-->


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
															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 160px;">Ações</span></th>
															<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">#</span></th>
															<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 70px;">Número</span></th>
															<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Cliente</span></th>
															<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Data</span></th>

															@if(empresaComFilial())
															<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Local</span></th>
															@endif
															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Forma Pag.</span></th>
															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Estado</span></th>

															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Usuário</span></th>
															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor Integral</span></th>

															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Desconto</span></th>
															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Acréscimo</span></th>
															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor Total</span></th>
															
														</tr>
													</thead>

													<tbody id="body" class="datatable-body">
														<?php $total = 0; ?>
														@foreach($vendas as $v)

														<tr class="datatable-row row-table" @if(!$v->estado) ondblclick="modalFinalizar('{{$v->id}}')" @endif>
															<td class="datatable-cell">
																<span style="width: 160px;">
																	@if(!$v->estado)
																	<a class="btn btn-warning btn-sm" href="{{ route('vendas-balcao.edit', [$v->id]) }}">
																		<i class="la la-edit"></i>				
																	</a>

																	<a class="btn btn-danger btn-sm" onclick='swal("Atenção!", "Deseja remover esta venda?", "warning").then((sim) => {if(sim){ location.href="vendas-balcao/destroy/{{ $v->id }}" }else{return false} })' href="#!">
																		<i class="la la-trash"></i>
																	</a>

																	<button type="button" onclick="modalFinalizar('{{$v->id}}')" class="btn btn-dark btn-sm">
																		<i class="la la-money-check"></i>				
																	</button>
																	@endif
																</span>
															</td>
															<td class="datatable-cell"><span class="codigo" style="width: 100px;" id="id">#{{$v->codigo_venda}}</span>
															</td>
															<td class="datatable-cell"><span class="codigo" style="width: 70px;" id="id">{{$v->numero_sequencial}}</span>
															</td>
															<td class="datatable-cell">
																<span class="codigo" style="width: 200px;">
																	@if($v->cliente)
																	{{ $v->cliente->razao_social }}
																	@elseif($v->cliente_nome != null)
																	{{ $v->cliente_nome }}
																	@else
																	NAO IDENTIFCADO
																	@endif
																</span>
															</td>
															<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i:s')}}</span>
															</td>
															<td class="datatable-cell">
																<span class="codigo" style="width: 100px;">

																	@if($v->tipo_pagamento == '99')

																	
																	@else
																	{{$v->getTipoPagamento($v->tipo_pagamento)}}
																	@endif

																</span>
															</td>
															@if(empresaComFilial())
															<td class="datatable-cell">
																<span class="codigo" style="width: 150px;">
																	{{ $v->filial_id ? $v->filial->descricao : 'Matriz' }}
																</span>
															</td>
															@endif
															<td class="datatable-cell">
																<span class="codigo" style="width: 100px;" id="">

																	@if($v->estado)
																	<span class="label label-xl label-inline label-light-success">Finalizado</span>
																	@else
																	<span class="label label-xl label-inline label-light-warning">Pendente</span>
																	@endif
																</span>
															</td>

															<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ $v->usuario->nome }}</span>
															</td>
															<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($v->valor_total, $casasDecimais, ',', '.') }}</span>
															</td>

															<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($v->desconto, 2, ',', '.') }}</span>
															</td>
															<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($v->acrescimo, 2, ',', '.') }}</span>
															</td>

															<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($v->valor_total-$v->desconto+$v->acrescimo, $casasDecimais, ',', '.') }}</span>
															</td>

														</tr>
														<?php 
														$total += $v->valor_total;
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

							<!--end: Wizard Step 2-->
							<div class="d-flex justify-content-between align-items-center flex-wrap">
								<div class="d-flex flex-wrap py-2 mr-3">
									@if(isset($links))
									{{$vendas->links()}}
									@endif
								</div>
							</div>
						</form>

					</div>
				</div>
			</div>
		</div>

	</div>

	<div class="modal-loading loading-class"></div>
	
	<div class="modal fade" id="modal-finalizar" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
		<div class="modal-dialog modal-xl" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Finalizando venda balcão</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						x
					</button>
				</div>
				<div class="modal-body">


				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				</div>
			</div>
		</div>
	</div>

	@endsection
	@section('javascript')
	<script type="text/javascript" src="/js/vendas_balcao_finalizar.js"></script>
	@endsection