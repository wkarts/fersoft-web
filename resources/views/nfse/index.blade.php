@extends('default.layout')
@section('css')
<style type="text/css">
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

			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			<form class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft" method="get" action="/nfse/filtro">
				<div class="row align-items-center">

					<div class="form-group col-md-4 col-12">
						<label class="col-form-label">Cliente</label>
						<select class="form-control select2" id="kt_select2_3" name="cliente_id">
							<option value="null">Selecione o cliente</option>
							@foreach($clientes as $c)
							<option @isset($cliente_id) @if($cliente_id == $c->id) selected @endif @endif value="{{$c->id}}">{{$c->id}} - {{$c->razao_social}}/{{$c->nome_fantasia}} ({{$c->cpf_cnpj}}) | {{$c->telefone}}</option>
							@endforeach
						</select>
					</div>
					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data Inicial</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_inicial" class="form-control" readonly value="{{{isset($dataInicial) ? $dataInicial : ''}}}" id="kt_datepicker_3" />
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
								<input type="text" name="data_final" class="form-control" readonly value="{{{isset($dataFinal) ? $dataFinal : ''}}}" id="kt_datepicker_3" />
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
									<option @if(isset($estado) && $estado == 'novo') selected @endif value="novo">NOVO</option>
									<option @if(isset($estado) && $estado == 'rejeitado') selected @endif value="rejeitado">REJEITADAS</option>
									<option @if(isset($estado) && $estado == 'cancelado') selected @endif value="cancelado">CANCELADAS</option>
									<option @if(isset($estado) && $estado == 'aprovado') selected @endif value="aprovado">APROVADAS</option>
									<option @if(isset($estado) && $estado == 'processando') selected @endif value="processando">PROCESSANDO</option>
									<option @if(isset($estado) && $estado == 'TODOS') selected @endif value="">TODOS</option>
								</select>
							</div>
						</div>
					</div>

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Filtrar</button>
					</div>
				</div>
			</form>
			<br>
			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">Lista de NFSe</h4>

			@if(isset($links))
			<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">Registros: <strong class="text-success">{{ sizeof($data) }} de {{ $data->total() }}</strong></label>
			@else
			<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">Registros: <strong class="text-success">{{ sizeof($data) }}</strong></label>
			@endif

			<h5>Soma dos serviços <strong>R$ {{ moeda($total) }}</strong></h5>

			<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
				<div class="form-group col-lg-3 col-md-4 col-sm-6">
					<a href="/nfse/create" class="btn btn-success">
						<i class="la la-plus"></i>
						Nova NFSe
					</a>
				</div>
			</div>
		</div>

		<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
			<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

				<div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
					<!--begin: Wizard Nav-->

					<div class="wizard-nav">

						<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

							<!--begin: Wizard Form-->
							<form class="form fv-plugins-bootstrap fv-plugins-framework" id="kt_form">
								<!--begin: Wizard Step 1-->
								<!-- Inicio da tabela -->

								<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
									<div class="row">
										<div class="col-xl-12">

											<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

												<table class="datatable-table" style="max-width: 100%; overflow: scroll">
													<thead class="datatable-head">
														<tr class="datatable-row" style="left: 0px;">
															<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 70px;">#</span></th>

															<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Tomador</span></th>
															<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor total de serviço</span></th>

															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Estado</span></th>

															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Data de cadastro</span></th>

															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Número</span></th>
															<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 240px;">Ações</span></th>
														</tr>
													</thead>

													<tbody id="body" class="datatable-body">

														@foreach($data as $item)

														<tr class="datatable-row">
															<td id="checkbox">

																<p style="width: 80px;">
																	<input type="checkbox" class="check" id="test_{{$item->id}}" />
																	<label for="test_{{$item->id}}"></label>
																</p>

															</td>
															<td style="display: none" id="id">{{$item->id}}</td>
															<td style="display: none" id="numero_nfse">{{$item->numero_nfse}}</td>

															<td style="display: none" class="estado_{{$item->id}}">{{$item->estado}}</td>
															
															<td class="datatable-cell">
																<span class="codigo" style="width: 150px;">
																	{{$item->razao_social}}
																</span>
															</td>
															
															<td class="datatable-cell">
																<span class="codigo" style="width: 100px;">
																	{{ moeda($item->valor_total) }}
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
																	@elseif($item->estado == 'processando')
																	<span class="label label-xl label-inline label-light-dark">Processando</span>
																	@elseif($item->estado == 'rejeitado')
																	<span class="label label-xl label-inline label-light-warning">Rejeitado</span>
																	@endif
																</span>
															</td>
															<td class="datatable-cell">
																<span class="codigo" style="width: 150px;">
																	{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i:s')}}
																</span>
															</td>
															<td class="datatable-cell">
																<span class="codigo" style="width: 100px;">
																	{{$item->numero_nfse > 0 ? $item->numero_nfse : '-' }}
																</span>
															</td>
															

															<td>
																<div class="row">
																	<span style="width: 240px;">

																		@if($item->estado == 'novo' || $item->estado == 'rejeitado')

																		<a class="btn btn-danger btn-sm" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/nfse/delete/{{ $item->id }}" }else{return false} })' href="#!">
																			<i class="la la-trash"></i>				
																		</a>

																		<a class="btn btn-warning btn-sm" onclick='swal("Atenção!", "Deseja editar este registro?", "warning").then((sim) => {if(sim){ location.href="/nfse/edit/{{ $item->id }}" }else{return false} })' href="#!">
																			<i class="la la-edit"></i>	
																		</a>

																		<button type="button" onclick="transmitir('{{ $item->id }}')" title="Transmitir NFSe" class="btn btn-success btn-sm" >
																			<i class="la la-send"></i>
																		</button>
																		@endif

																		@if($item->estado == 'aprovado')

																		<a target="_blank" href="/nfse/baixarXml/{{$item->id}}" class="btn btn-light btn-sm">
																			<i class="la la-download"></i>
																		</a>
																		@else

																		<a title="Visualizar temporário" class="btn btn-dark btn-sm" href="/nfse/preview-xml/{{$item->id}}">
																			<i class="las la-file-excel"></i>
																		</a>

																		@endif

																		<a title="Clonar" class="btn btn-primary btn-sm" href="/nfse/clone/{{$item->id}}">
																			<i class="la la-copy"></i>
																		</a>

																	</span>
																</div>
															</td>

														</tr>
														
														@endforeach

													</tbody>
												</table>
											</div>
										</div>
										@if($certificado != null)
										<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12 mt-2">
											<div class="row">

												<div class="col-sm-4 col-lg-4 col-md-4 col-xl-2 col-6">
													<button type="button"  id="btn-enviar" onclick="enviar()" style="width: 100%" class="btn btn-success spinner-white spinner-right" href="#!">Enviar</button>
												</div>

													<!-- <div class="col-sm-4 col-lg-4 col-md-4 col-xl-2 col-6">
														<a id="btn-imprimir-cancelar" onclick="consultarEmissao()" style="width: 100%" class="btn btn-light-danger" href="#!">Consultar Emissão</a>
													</div> -->

													<div class="col-sm-4 col-lg-4 col-md-4 col-xl-2 col-6">
														<button type="button" id="btn-imprimir" onclick="imprimir()" style="width: 100%" class="btn btn-secondary" href="#!">Imprimir</button>
													</div>

													<div class="col-sm-4 col-lg-4 col-md-4 col-xl-2 col-6">
														<button type="button" id="btn-consultar" onclick="consultar()" style="width: 100%" class="btn btn-info spinner-white spinner-right" href="#!">Consultar</button>
													</div>

													<div class="col-sm-4 col-lg-4 col-md-4 col-xl-2 col-6">
														<button type="button" id="btn-cancelar" data-toggle="modal" data-target="#modal1" onclick="setarNumero()" style="width: 100%" class="btn btn-danger" href="#modal1">Cancelar</button>
													</div>

													<div class="col-sm-4 col-lg-4 col-md-4 col-xl-2 col-6">
														<button type="button" id="btn-xml" onclick="setarNumero(true)" style="width: 100%" class="btn btn-warning" data-toggle="modal" data-target="#modal5">Enviar XML</button>
													</div>

													<!-- <div class="col-sm-4 col-lg-4 col-md-4 col-xl-2 col-6">
														<a id="btn-imprimir-cancelar" onclick="imprimirCancela()" style="width: 100%" class="btn btn-light-danger" href="#!">Imprimir Cancela</a>
													</div> -->

												</div>

											</div>
											@else
											<input type="hidden" id="semCertificado" value="true" name="">
											@endif
										</div>
									</div>
									<!-- Fim da tabela -->

									<!--end: Wizard Step 2-->
									<div class="d-flex justify-content-between align-items-center flex-wrap">
										<div class="d-flex flex-wrap py-2 mr-3">
											@if(isset($links))
											{{$data->links()}}
											@endif
										</div>
									</div>
									<h5 class="col-12 mt-3">Soma dos serviços <strong>R$ {{ moeda($total) }}</strong></h5>

								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>


	<div class="modal fade" id="modal1" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">CANCELAR NFSe <strong class="text-danger" id="numero_cancelamento"></strong></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						x
					</button>
				</div>
				<div class="modal-body">
					<div class="row">

						<div class="form-group validated col-sm-12 col-lg-12">
							<label class="col-form-label" id="">Motivo</label>
							<select class="form-control custom-select" id="motivo">
								<option value="1">Erro na emissão</option>
								<option value="2">Serviço não prestado</option>
								<option value="4">Duplicidade de nota</option>
							</select>
						</div>
					</div>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="button" id="btn-cancelar-2" onclick="cancelar()" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Cancelar</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="modal1_aux" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">CANCELAR CTe OS<strong class="text-danger" id="numero_cancelamento2"></strong></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						x
					</button>
				</div>
				<div class="modal-body">
					<div class="row">
						<input type="hidden" id="id_cancela" name="">
						<div class="form-group validated col-sm-12 col-lg-12">
							<label class="col-form-label" id="">Justificativa</label>
							<div class="">
								<input type="text" id="justificativa2" placeholder="Justificativa minimo de 15 caracteres" name="justificativa" class="form-control" value="">
							</div>
						</div>
					</div>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="button" id="btn-cancelar-3" onclick="cancelar2()" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Cancelar CTe</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal-loading loading-class"></div>

	<div class="modal fade" id="modal4" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">CARTA DE CORREÇÃO CTe OS <strong class="text-danger" id="numero_correcao"></strong></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						x
					</button>
				</div>
				<div class="modal-body">
					<div class="row">

						
						<div class="form-group validated col-sm-6 col-lg-6">
							<label class="col-form-label" id="">Campo</label>
							<div class="">
								<input type="text" id="campo" name="campo" class="form-control" value="">
							</div>
						</div>

						<div class="form-group validated col-sm-6 col-lg-6">
							<label class="col-form-label" id="">Grupo</label>
							<div class="">
								<input type="text" id="grupo" name="grupo" class="form-control" value="">
							</div>
						</div>

						<div class="form-group validated col-sm-12 col-lg-12">
							<label class="col-form-label" id="">Correção</label>
							<div class="">
								<input type="text" id="correcao" placeholder="Correção minimo de 15 caracteres" name="correcao" class="form-control" value="">
							</div>
						</div>
					</div>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="button" id="btn-corrigir-2" onclick="cartaCorrecao()" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Corrigir CTe</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="modal4_aux" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">CARTA DE CORREÇÃO CTe OS <strong class="text-danger" id="numero_correcao_aux"></strong></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						x
					</button>
				</div>
				<div class="modal-body">
					<div class="row">

						
						<input type="hidden" id="id_correcao" name="">
						<div class="form-group validated col-sm-6 col-lg-6">
							<label class="col-form-label" id="">Campo</label>
							<div class="">
								<input type="text" id="campo2" name="Campo" class="form-control" value="">
							</div>
						</div>
						<div class="form-group validated col-sm-6 col-lg-6">
							<label class="col-form-label" id="">Grupo</label>
							<div class="">
								<input type="text" id="grupo2" name="grupo2" class="form-control" value="">
							</div>
						</div>

						<div class="form-group validated col-sm-12 col-lg-12">
							<label class="col-form-label" id="">Correção</label>
							<div class="">
								<input type="text" id="correcao2" placeholder="Correção minimo de 15 caracteres" name="correcao" class="form-control" value="">
							</div>
						</div>
					</div>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="button" id="btn-corrigir-3" onclick="cartaCorrecaoAux()" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Corrigir CTe</button>
				</div>
			</div>
		</div>
	</div>


	<div class="modal fade" id="modal5" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">ENVIAR XML DA NFSe <strong class="text-danger" id="numero_email"></strong></h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						x
					</button>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="form-group validated col-sm-12 col-lg-12">
							<label class="col-form-label" id="">Email</label>
							<input type="hidden" id="id_correcao" name="">
							<div class="">
								<input type="text" id="email" placeholder="Email" name="email" class="form-control" value="">
							</div>
						</div>
					</div>


				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="button" id="btn-send" onclick="enviarEmailXMl()" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Enviar</button>
				</div>
			</div>
		</div>
	</div>


	@endsection	
	@section('javascript')
	@if($config->integracao_nfse == '' || $config->integracao_nfse == 'webmania')
	<script type="text/javascript" src="/js/nfse_envio.js"></script>
	@else
	<script type="text/javascript" src="/js/nfse_integra_notas.js"></script>
	@endif
	@endsection	