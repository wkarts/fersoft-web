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


    /* --- PDV: Select2 mais compacto no mobile (sem mexer na lógica) --- */
    @media (max-width: 576px) {
        .select2-container--default .select2-selection--single {
            height: 34px !important;
            min-height: 34px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            font-size: 13px !important;
            line-height: 32px !important;
            padding-left: 10px !important;
            padding-right: 28px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 34px !important;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            font-size: 13px !important;
            padding: 6px 8px !important;
        }
        .select2-container--default .select2-results__option {
            font-size: 13px !important;
            padding: 6px 10px !important;
            line-height: 1.25 !important;
        }
    }

</style>
@endsection
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			<form method="get" action="/frenteCaixa/filtro">
				<div class="row align-items-center">

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data Inicial</label>
						<input type="datetime-local" name="data_inicial" class="form-control" value="{{{isset($dataInicial) ? $dataInicial : ''}}}"/>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data Final</label>
						<input type="datetime-local" name="data_final" class="form-control" value="{{{isset($dataFinal) ? $dataFinal : ''}}}"/>
					</div>

					@if(sizeof($usuarios) > 0 && $config->caixa_por_usuario == 1)
					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Usuário</label>
						<div class="">
							<div class="input-group ">
								<select class="custom-select form-control" id="sel_usuario" name="usuario">
									<option value="--">Todos</option>
									@foreach($usuarios as $u)
									<option @if($usuario_id == $u->id) selected @endif value="{{$u->id}}">{{$u->nome}}</option>
									@endforeach
								</select>
							</div>
						</div>
					</div>
					@endif

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Valor</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="valor" class="form-control money" value="{{{isset($valor) ? $valor : ''}}}"/>
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-dollar-sign"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Estado</label>
						<div class="">
							<div class="input-group date">
								<select name="status" class="custom-select">
									<option @isset($status) @if($status == '') selected @endif @endif value="">Todas</option>
									<option @isset($status) @if($status == 'fiscal') selected @endif @endif value="fiscal">Fiscal</option>
									<option @isset($status) @if($status == 'nao_fiscal') selected @endif @endif value="nao_fiscal">Não fiscal</option>
									<option @isset($status) @if($status == 'rascunho') selected @endif @endif value="rascunho">Rascunho</option>
								</select>
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-list"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-2 col-sm-3">
						<label class="col-form-label">Número NFCe</label>
						<div class="">
							<div class="input-group">
								<input type="text" name="numero_nfce" class="form-control" value="{{{isset($numero_nfce) ? $numero_nfce : ''}}}" />
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-2 col-sm-3">
						<label class="col-form-label">Código da Venda</label>
						<div class="">
							<div class="input-group">
								<input type="text" name="codigo_venda" class="form-control" value="{{{isset($codigo_venda) ? $codigo_venda : ''}}}" />
							</div>
						</div>
					</div>

					<div class="form-group col-lg-4 col-12">
						<label class="col-form-label" id="">Cliente</label>
						<select class="form-control select2" id="kt_select2_2" name="cliente_id">
							<option value="">Selecione</option>
							@foreach($clientes as $c)
							<option @isset($cliente_id) @if($cliente_id == $c->id) selected @endif @endif value="{{$c->id}}">{{$c->id}} - {{$c->razao_social}}/{{$c->nome_fantasia}} ({{$c->cpf_cnpj}})</option>
							@endforeach
						</select>
					</div>

					<div class="form-group col-lg-2 col-6">
						<label class="col-form-label" id="">Tipo de pagamento</label>
						<select class="custom-select" name="tipo_pagamento">
							<option value="">Selecione</option>
							@foreach(App\Models\Venda::tiposPagamento() as $key => $t)
							<option @isset($tipo_pagamento) @if($tipo_pagamento == $key) selected @endif @endif value="{{$key}}">{{ $t }}</option>
							@endforeach
						</select>
					</div>

					<div class="form-group col-lg-2 col-6">
						<label class="col-form-label" id="">Vendedor</label>
						<select class="custom-select" name="vendedor_id">
							<option value="">Selecione</option>
							@foreach($vendedores as $v)
							<option @isset($vendedor_id) @if($vendedor_id == $v->id) selected @endif @endif value="{{$v->id}}">{{ $v->nome }}</option>
							@endforeach
						</select>
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

			@if($certificado != null)
			<button class="btn btn-dark float-right spinner-white spinner-right btn-consulta-status">
				Consultar Status Sefaz
			</button>
			<br><br>
			@endif


			@if($contigencia != null)
			<h3 class="text-danger">Contigência ativada</h3>
			<p class="text-danger">Tipo: {{$contigencia->tipo}}</p>
			<p class="text-danger">Data de ínicio: {{ __date($contigencia->created_at) }}</p>
			@endif

			<hr>

			<h4>Lista de Vendas de Frente de Caixa</h4>

			<div class="row">
				<div class="col-lg-3 col-xl-3">
					<a style="width: 100%" href="/frenteCaixa" class="btn btn-light-primary">
						<i class="la la-box"></i>
						FRENTE DE CAIXA
					</a>
				</div>

				<div class="col-lg-3 col-xl-3">
					<a target="_blank" style="width: 100%" href="/relatorios/filtroVendaDiariaPdv?data_inicial={{ request()->data_inicial }}&data_final={{ request()->data_final }}&total_resultados=" class="btn btn-light-danger">
						<i class="la la-file"></i>
						BAIXAR RELATÓRIO
					</a>
				</div>

				<div class="col-lg-3 col-xl-3">
					<a style="width: 100%" data-toggle="modal" data-target="#modal-somas" class="btn btn-light-info">
						<i class="las la-calendar-plus"></i>
						SOMA DETALHADA
					</a>
				</div>


				<div class="col-lg-3 col-xl-3">
					<a style="width: 100%;" href="/caixa/list" class="btn btn-light-warning">
						<i class="las la-file"></i>
						CAIXAS FECHADOS
					</a>
				</div>

				<div class="col-lg-3 col-xl-3 mt-1">
					<a style="width: 100%;" href="/frenteCaixa/contigencia" class="btn btn-light-dark">
						<i class="las la-file"></i>
						NFCe em contigência
					</a>
				</div>

			</div>

			<div class="row">

				<div class="card-body">
					<div class="row">
						<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
							<div class="card card-custom gutter-b example example-compact">

								<div class="card-body">

									<h3>Total: <strong class="text-success">R$ {{number_format($total, 2, ',', '.')}}</strong></h3>
									<h3>Soma CashBack: <strong class="text-info">R$ {{moeda($vendas->sum('valor_cashback'))}}</strong></h3>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

					<div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
						<!--begin: Wizard Nav-->

						<div class="wizard-nav">
							<p class="text-danger" style="margin-top: 10px;">{{$info}}</p>

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
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">#</span></th>
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Código da venda</span></th>
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Cliente</span></th>
																<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Data</span></th>
																<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Tipo de pagamento</span></th>

																@if(empresaComFilial())
																<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Local</span></th>
																@endif

																<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Estado</span></th>

																<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">NFCe</span></th>

																<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Concluída</span></th>
																<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Usuário</span></th>
																<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Vendedor</span></th>
																<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor Total</span></th>
																<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Desconto</span></th>
																<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">CashBack</span></th>
																<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Desktop</span></th>
																<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 400px;">Ações</span></th>
															</tr>
														</thead>

														<tbody class="datatable-body">
															
															@foreach($vendas as $v)

															<tr class="datatable-row @if($v->estado == 'REJEITADO') bg-light-danger @endif @if($v->estado == 'APROVADO') bg-light-success @endif" style="left: 0px;">
																<td class="datatable-cell"><span class="codigo" style="width: 80px;">#{{ $v->numero_sequencial }}</span>
																</td>
																<td class="datatable-cell"><span class="codigo" style="width: 80px;">{{ $v->id }}</span>
																</td>
																<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ $v->cliente->razao_social ?? 'NAO IDENTIFCADO' }}</span>
																</td>
																<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i:s')}}</span>
																</td>
																<td class="datatable-cell"><span class="codigo" style="width: 100px;">

																	@if($v->tipo_pagamento == '99')

																	<a href="#!" onclick='swal("", "{{$v->multiplo()}}", "info")' class="btn btn-info">
																		Ver
																	</a>
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
															<td class="datatable-cell"><span class="codigo" style="width: 100px;">
																{{ $v->estado }}
																@if($v->estado == 'APROVADO')
																@if($v->contigencia)
																<span class="text-danger">contigência</span>
																@endif
																@endif
															</span>
														</td>
														<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ $v->NFcNumero > 0 ? $v->NFcNumero : '--' }}</span>
														</td>

														<td class="datatable-cell"><span class="codigo" style="width: 100px;">
															@if($v->rascunho)
															<span class="label label-xl label-inline label-warning">Rascunho</span>

															@elseif($v->consignado)
															<span class="label label-xl label-inline label-warning">Consignado</span>
															@else
															<span class="label label-xl label-inline label-success">Sim</span>
															@endif
														</span>
													</td>
													<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ $v->usuario->nome }}</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 100px;">
															{{ $v->vendedor_setado ? $v->vendedor_setado->nome : '--' }}
														</span>
													</td>
													<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($v->valor_total, $casasDecimais, ',', '.') }}</span>
													</td>
													<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($v->desconto, $casasDecimais, ',', '.') }}</span>
													</td>
													<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($v->valor_cashback, $casasDecimais, ',', '.') }}</span>
													</td>

													<td class="datatable-cell"><span class="codigo" style="width: 100px;">
														@if($v->pdv_java)
														<span class="label label-xl label-inline label-success">Sim</span>
														@else
														<span class="label label-xl label-inline label-danger">Não</span>
														@endif
													</span></td>
													
													<td class="datatable-cell">
														<span class="codigo" style="width: 400px;">

															@if($v->NFcNumero && $v->estado == 'APROVADO')

															<a title="CUPOM FISCAL" target="_blank" href="/nfce/imprimir/{{$v->id}}" class="btn btn-success btn-sm">
																<i class="la la-print"></i>
															</a>

															<a id="btn_consulta_{{$v->id}}" title="CONSULTAR NFCe" onclick="consultarNFCe('{{$v->id}}')" href="#!" class="btn btn-warning spinner-white spinner-right btn-sm">
																<i class="la la-check"></i>
															</a>

															<a title="BAIXAR XML" target="_blank" href="/nfce/baixarXml/{{$v->id}}" class="btn btn-danger btn-sm">
																<i class="la la-download"></i>
															</a>

															@endif

															@if($v->reenvio_contigencia == 0 && $v->contigencia && $contigencia == null)
															<a title="RETRANSMITIR EM CONTIGÊNCIA" id="btn_envia_{{$v->id}}" class="btn btn-warning spinner-white spinner-right btn-sm" onclick='swal("Atenção!", "Deseja enviar esta venda em contigência para Sefaz?", "warning").then((sim) => {if(sim){ transmitirContigencia({{$v->id}}) }else{return false} })' href="#!">
																<i class="las la-paper-plane"></i>
															</a>
															@endif

															<a title="CUPOM NÃO FISCAL" target="_blank" href="/nfce/imprimirNaoFiscal/{{$v->id}}" class="btn btn-primary btn-sm">
																<i class="la la-print"></i>
															</a>

															@if(!$v->NFcNumero && !$v->rascunho && !$v->consignado)
															<a title="GERAR NFCE" id="btn_envia_{{$v->id}}" class="btn btn-warning spinner-white spinner-right btn-sm" onclick='swal("Atenção!", "Deseja enviar esta venda para Sefaz?", "warning").then((sim) => {if(sim){ emitirNFCe({{$v->id}}) }else{return false} })' href="#!">
																<i class="las la-file-invoice"></i>
															</a>
															@endif

															<a title="DETALHES" target="_blank" href="/nfce/detalhes/{{$v->id}}" class="btn btn-info btn-sm">
																<i class="la la-file"></i>
															</a>

															@if($v->rascunho || $v->consignado)
															<a title="EDITAR RASCUNHO" href="/frenteCaixa/edit/{{$v->id}}" class="btn btn-warning btn-sm">
																<i class="la la-edit"></i>
															</a>
															@endif

															@if($v->estado == 'DISPONIVEL' || $v->estado == 'REJEITADO')
															<a class="btn btn-light btn-sm" target="_blank" title="Ver XML" href="/nfce/xmlTemp/{{ $v->id }}">
																<i class="las la-file-excel"></i>
															</a>
															@endif

															@if(!$v->vendaNfe)
															<a title="GERAR VENDA NFE" target="_blank" href="/nfce/vendaNFe/{{$v->id}}" class="btn btn-dark btn-sm">
																<i class="la la-clone"></i>
															</a>
															@endif

															<a class="btn btn-success btn-sm" href="#!" onclick="whatsAppClick('{{$v->id}}')">
																<i class="la la-whatsapp"></i>	
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
						</div>
						<!-- Fim da tabela -->
					</div>

					<!--end: Wizard Step 1-->
					<!--begin: Wizard Step 2-->
					<div class="pb-5" data-wizard-type="step-content">

						<!-- Inicio do card -->

						<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
							<div class="row">

								@foreach($vendas as $v)
								<div class="col-sm-6 col-lg-6 col-md-6 col-xl-6">

									<div class="card card-custom gutter-b example example-compact">
										<div class="card-header">
											<div class="card-title">
												<h3 style="width: 230px; font-size: 15px; height: 10px;" class="card-title">
													<strong class="text-success mr-1">R$ {{ number_format($v->valor_total, 2, ',', '.') }} </strong> - {{ \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i:s')}}

												</h3>

											</div>
										</div>

										<div class="card-body">

											<div class="kt-widget__info">
												<span class="kt-widget__label">ID:</span>
												<a target="_blank" class="kt-widget__data text-success">
													{{ $v->id }}
												</a>
											</div>

											<div class="kt-widget__info">
												<span class="kt-widget__label">Cliente:</span>
												<a target="_blank" class="kt-widget__data text-success">
													{{ $v->cliente->razao_social ?? 'NAO IDENTIFCADO' }}
												</a>
											</div>

											<div class="kt-widget__info">
												<span class="kt-widget__label">NFCe:</span>
												<a target="_blank" class="kt-widget__data text-success">
													{{ $v->NFcNumero > 0 ? $v->NFcNumero : '--' }}
												</a>
											</div>

											<div class="kt-widget__info">
												<span class="kt-widget__label">Estado:</span>
												<a target="_blank" class="kt-widget__data text-success">
													{{ $v->estado }}
												</a>
											</div>

											<div class="kt-widget__info">
												<span class="kt-widget__label">Usuário:</span>
												<a target="_blank" class="kt-widget__data text-success">
													{{ $v->usuario->nome }}
												</a>
											</div>

											<div class="kt-widget__info">
												<span class="kt-widget__label">Vendedor:</span>
												<a target="_blank" class="kt-widget__data text-success">
													{{ $v->vendedor_setado ? $v->vendedor_setado->nome : '--' }}
												</a>
											</div>

											@if(empresaComFilial())
											<div class="kt-widget__info">
												<span class="kt-widget__label">Local:</span>
												<a target="_blank" class="kt-widget__data text-success">
													{{ $v->filial_id ? $v->filial->descricao : 'Matriz' }}
												</a>
											</div>
											@endif

											<div class="kt-widget__info">
												<span class="kt-widget__label">Tipo de pagamento:</span>
												<span class="codigo" style="width: 100px;">

													@if($v->tipo_pagamento == '99')

													<a href="#!" onclick='swal("", "{{$v->multiplo()}}", "info")' class="btn btn-info">
														Ver
													</a>
													@else
													<span class="label label-xl label-inline label-success">
														{{$v->getTipoPagamento($v->tipo_pagamento)}}
													</span>
													@endif

												</span>
											</div>

											<div class="kt-widget__info mt-1">
												<span class="kt-widget__label">Concluída:</span>
												<span class="codigo" style="width: 100px;">

													@if($v->rascunho)

													<span class="label label-xl label-inline label-warning">
														Rascunho
													</span>
													@elseif($v->consignado)

													<span class="label label-xl label-inline label-warning">
														Consignado
													</span>
													@else
													<span class="label label-xl label-inline label-success">
														Sim
													</span>
													@endif

												</span>
											</div>

											<hr>

											<div class="row">

												<div class="col-sm-12 col-lg-12 col-md-12 col-xl-6">

													<a style="width: 100%; margin-top: 5px;" target="_blank" href="/nfce/imprimirNaoFiscal/{{$v->id}}" class="btn btn-primary">
														<i class="la la-print"></i>
														Imprimir não fiscal
													</a>
												</div>


												@if($v->NFcNumero && $v->estado == 'APROVADO')

												<div class="col-sm-12 col-lg-12 col-md-12 col-xl-6">
													<a style="width: 100%; margin-top: 5px;" target="_blank" href="/nfce/imprimir/{{$v->id}}" class="btn btn-success">
														<i class="la la-print"></i>
														Imprimir fiscal
													</a>
												</div>

												<div class="col-sm-12 col-lg-12 col-md-12 col-xl-6">
													<a id="btn_consulta_grid_{{$v->id}}" style="width: 100%; margin-top: 5px;" href="#!" onclick="consultarNFCe('{{$v->id}}')" class="btn btn-warning spinner-white spinner-right">
														<i class="la la-check"></i>
														Consultar NFCe
													</a>
												</div>

												<div class="col-sm-12 col-lg-12 col-md-12 col-xl-6">
													<a style="width: 100%; margin-top: 5px;" target="_blank" href="/nfce/baixarXml/{{$v->id}}" class="btn btn-danger">
														<i class="la la-download"></i>
														Baixar XML
													</a>
												</div>

												@endif

												@if(!$v->NFcNumero)

												<div class="col-sm-12 col-lg-12 col-md-12 col-xl-6">
													<a style="width: 100%; margin-top: 5px;" id="btn_envia_grid_{{$v->id}}" onclick='swal("Atenção!", "Deseja enviar esta venda para Sefaz?", "warning").then((sim) => {if(sim){ emitirNFCe({{$v->id}}) }else{return false} })' href="#!" class="btn btn-warning spinner-white spinner-right">
														<i class="la la-file-invoice"></i>
														Transmitir NFCe
													</a>
												</div>
												@endif

												@if($v->estado == 'DISPONIVEL' || $v->estado == 'REJEITADO')
												<div class="col-sm-12 col-lg-12 col-md-12 col-xl-6">
													<a style="width: 100%; margin-top: 5px;" href="/nfce/xmlTemp/{{$v->id}}" class="btn btn-light spinner-white spinner-right">
														<i class="la la-file-excel"></i>
														Ver XML
													</a>
												</div>
												@endif

											</div>
										</div>
									</div>

								</div>
								@endforeach

							</div>
						</div>
					</div>
					<!--end: Wizard Step 2-->



				</form>

			</div>
		</div>
	</div>
	
</div>
</div>

</div>

<div class="modal fade" id="modal-whatsApp" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<form class="modal-content" method="post" action="/vendasCaixa/enviar-whats">
			@csrf
			<div class="modal-header">
				<h5 class="modal-title">Enviar WhatsApp</h5>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="form-group validated col-sm-6 col-lg-6">
						<label class="col-form-label" id="">WhatsApp</label>
						<div class="">
							<input required type="text" id="celular" name="celular" class="form-control" value="">
						</div>
					</div>
					<input type="hidden" name="venda_id" id="venda_whats_id">
					<div class="form-group validated col-lg-12 col-lg-12">
						<label class="col-form-label" id="">Texto</label>
						<div class="">
							<input required type="text" id="texto" name="texto" class="form-control" value="">
						</div>
					</div>

					<div class="col-12">
						<p>Enviar como anexo</p>
					</div>
					<div class="form-group validated col-lg-4 col-6">
						<input type="checkbox" name="cupom_nao_fiscal"> Cupom não fiscal
					</div>
					<div class="form-group validated col-lg-4 col-6 div-danfe">
						<input type="checkbox" name="danfe"> DANFCE
					</div>

					<div class="form-group validated col-lg-4 col-6 div-danfe">
						<input type="checkbox" name="xml"> XML
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="submit" id="btn-cancelar-3" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Ok</button>
			</div>
		</form>
	</div>
</div>

<div class="modal-loading loading-class"></div>

<div class="modal fade" id="modal-somas" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>

			<div class="modal-body">
				@foreach($somaTiposPagamento as $key => $s)
				@if($s > 0)
				<h5 class="center-align">{{App\Models\VendaCaixa::getTipoPagamento($key)}} = <strong class="red-text">R$ {{moeda($s)}}</strong></h5>
				@endif
				@endforeach
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>

@endsection	