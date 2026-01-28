@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-12">
				<div class="row">

					<a href="/contasReceber/new" class="btn btn-lg btn-success">
						<i class="fa fa-plus"></i>Nova Conta a Receber
					</a>

					<a href="/contasReceber/pendentes" class="btn btn-lg btn-warning ml-1">
						<i class="fa fa-tag"></i>Débito por Cliente
					</a>

					<a href="/contasReceber/importacao" class="btn btn-lg btn-danger ml-1">
						<i class="fa fa-arrow-up"></i>Importação
					</a>
				</div>
			</div>
		</div>
		<br>

		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<form method="get" action="/contasReceber/filtro">
				<div class="row align-items-center">

					<div class="form-group col-lg-6 col-xl-5">
						<div class="row align-items-center">

							<div class="col-md-12 my-2 my-md-0">
								<label class="col-form-label">Cliente</label>
								<select class="form-control select2" id="kt_select2_3" name="clienteId">
									<option value="null">Selecione o cliente</option>
									@foreach($clientes as $c)
									<option @isset($clienteId) @if($clienteId == $c->id) selected @endif @endif value="{{$c->id}}">{{$c->id}} - {{$c->razao_social}}/{{$c->nome_fantasia}} ({{$c->cpf_cnpj}}) | {{$c->telefone}}</option>
									@endforeach
								</select>
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Filtro de data</label>

						<select class="custom-select form-control" id="tipo_filtro_data" name="tipo_filtro_data">
							<option @if(isset($tipo_filtro_data) && $tipo_filtro_data == 1) selected @endif value="1">Vencimento</option>
							<option @if(isset($tipo_filtro_data) && $tipo_filtro_data == 2) selected @endif value="2">Data de registro</option>
							<option @if(isset($tipo_filtro_data) && $tipo_filtro_data == 3) selected @endif value="3">Data de recebimento</option>
						</select>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data inicial</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_inicial" class="form-control date-input" value="{{{ isset($dataInicial) ? $dataInicial : '' }}}" id="kt_datepicker_3" />
								<div class="input-group-append">
									<span class="input-group-text">
										<i class="la la-calendar"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Data final</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="data_final" class="form-control date-input" value="{{{ isset($dataFinal) ? $dataFinal : '' }}}" id="kt_datepicker_3" />
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

						<select class="custom-select form-control" id="status" name="status">
							<option @if(isset($status) && $status == 'todos') selected @endif value="todos">TODOS</option>
							<option @if(isset($status) && $status == 'pago') selected @endif value="pago">RECEBIDO</option>
							<option @if(isset($status) && $status == 'pendente') selected @endif value="pendente">PENDENTE</option>
							<option @if(isset($status) && $status == 'vencido') selected @endif value="vencido">VENCIDO</option>
						</select>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Categoria</label>

						<select class="custom-select form-control" id="categoria" name="categoria">
							<option @if(isset($categoria) && $categoria == 'todos') selected @endif value="todos">TODOS</option>
							@foreach($categorias as $c)
							<option @if(isset($categoria) && $categoria == $c->id) selected @endif value="{{$c->id}}">{{$c->nome}}</option>
							@endforeach
						</select>
					</div>

					<div class="form-group validated col-sm-12 col-lg-3">
						<label class="col-form-label" id="">Tipo de Pagamento</label>
						<select class="custom-select form-control" name="tipo_pagamento">
							<option value="">Selecione o tipo de pagamento</option>
							@foreach(App\Models\ContaPagar::tiposPagamento() as $c)
							<option @isset($tipo_pagamento) @if($tipo_pagamento == $c) selected @endif @endif value="{{$c}}">{{$c}}</option>
							@endforeach
						</select>
					</div>

					<div class="form-group col-lg-2 col-xl-2">
						<div class="row align-items-center">

							<div class="col-md-12 my-2 my-md-0">
								<label class="col-form-label">Nº nota fiscal</label>

								<div class="input-icon">
									<input type="text" name="numero_nota_fiscal" value="{{{ isset($numero_nota_fiscal) ? $numero_nota_fiscal : '' }}}" class="form-control" placeholder="Nº nota fiscal" id="kt_datatable_search_query">
									<span>
										<i class="fa fa-thumb-tack"></i>
									</span>
								</div>
							</div>
						</div>
					</div>

					<div class="form-group col-lg-2 col-md-4 col-sm-6">
						<label class="col-form-label">Nº do pedido</label>
						<div class="">
							<div class="input-group date">
								<input type="text" name="numero_pedido" class="form-control" value="{{{ isset($numero_pedido) ? $numero_pedido : '' }}}" id="numero_pedido" />

							</div>
						</div>
					</div>

					@if(empresaComFilial())
					{!! __view_locais_select_filtro("Local", isset($filial_id) ? $filial_id : '') !!}
					@endif

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button style="margin-top: 10px;" class="btn btn-light-primary px-6 font-weight-bold">Filtrar</button>
					</div>
				</div>

			</form>
			<br>
			<h4>Lista de Contas a Receber</h4>

			@isset($paraImprimir)
			<form target="_blank" method="get" action="/contasReceber/relatorio">
				<input type="hidden" name="cliente" value="{{{ isset($clienteId) ? $clienteId : '' }}}">
				<input type="hidden" name="tipo_filtro_data" value="{{{ isset($tipo_filtro_data) ? $tipo_filtro_data : '' }}}">
				<input type="hidden" name="data_inicial" value="{{{ isset($dataInicial) ? $dataInicial : '' }}}">
				<input type="hidden" name="data_final" value="{{{ isset($dataFinal) ? $dataFinal : '' }}}">
				<input type="hidden" name="tipo_pesquisa" value="{{{ isset($tipo_pesquisa) ? $tipo_pesquisa : '' }}}">
				<input type="hidden" name="status" value="{{{ isset($status) ? $status : '' }}}">
				<input type="hidden" name="categoria" value="{{{ isset($categoria) ? $categoria : '' }}}">
				<input type="hidden" name="filial_id" value="{{{ isset($filial_id) ? $filial_id : '' }}}">
				<input type="hidden" name="numero_nota_fiscal" value="{{{ isset($numero_nota_fiscal) ? $numero_nota_fiscal : '' }}}">

				<button href="/contasPagar/new" class="btn btn-info ml-1">
					<i class="fa fa-print"></i>Imprimir Relatório
				</button>
			</form>
			@endisset
			<h6 style="color: red">*{{$infoDados}}</h6>
			<label>Total de registros: {{sizeof($contas)}}</label>

			<div class="row">
				<div class="col-12">
					<button id="btn_seleciona_varios" class="btn btn-light">
						<i class="la la-list"></i>
						Selecionar múltiplos
					</button>

					<button style="display: none" id="btn_gerar" class="btn btn-success">
						<i class="la la-list"></i>
						Gerar boletos
					</button>

					<a href="/remessasBoleto" class="btn btn-danger">
						<i class="la la-list"></i>
						Remessas de boleto
					</a>

					<button id="btn_receber" class="btn btn-dark d-none">
						<i class="la la-list"></i>
						Receber contas
					</button>

                    <!-- Botão para sincronizar as contas a receber sem número de nota fiscal -->
                    <a href="{{ url('/contasReceber/syncNotaFiscal') }}" class="btn btn-light">
                        <i class="fa fa-sync"></i> Sincronizar NF das Vendas
                    </a>

                    <!-- Botão para Exportar para Excel -->
                    @if(isset($contas) && count($contas) > 0)
                        <!-- Botão para Exportar para Excel -->
                        <a href="/contasReceber/export?
                            data_inicial={{ urlencode($dataInicial) }}&
                            data_final={{ urlencode($dataFinal) }}&
                            cliente={{ $clienteId ?? 'null' }}&
                            status={{ $status ?? 'todos' }}&
                            categoria={{ $categoria ?? 'todos' }}&
                            tipo_pagamento={{ $tipo_pagamento ?? '' }}&
                            numero_nota_fiscal={{ $numero_nota_fiscal ?? '' }}&
                            tipo_filtro_data={{ $tipo_filtro_data ?? 1 }}@if(isset($filial_id))&filial_id={{ $filial_id }}@endif"
                           class="btn btn-light">
                            <i class="fa fa-file-excel-o"></i> Exportar para Excel
                        </a>
                    @endif

					<br><br>
				</div>
			</div>

			<div class="row">

				<?php
				$somaValor = 0;
				$somaRecebido = 0;
				$somaPendente = 0;
				?>

				<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

					<div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">
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

						<div class="pb-5" data-wizard-type="step-content">
							<div class="row">
								<div class="col-xl-12">

									<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

										<table class="datatable-table" style="max-width: 100%; overflow: scroll">
											<thead class="datatable-head">
												<tr class="datatable-row" style="left: 0px;">
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">CLIENTE</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">CATEGORIA</span></th>
													@if(empresaComFilial())
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">LOCAL</span></th>
													@endif
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">REFERÊNCIA</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">VALOR INTEGRAL</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">VALOR RECEBIDO</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">DATA VENCIMENTO</span></th>

													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">DATA DE RECEBIMENTO</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">DATA DE CADASTRO</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">ESTADO</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">TIPO DE PAG.</span></th>
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 300px;">AÇÕES</span></th>
												</tr>
											</thead>
											<tbody id="body" class="datatable-body">
												@foreach($contas as $c)
												<tr class="datatable-row">
													<td class="datatable-cell">
														<span class="codigo" style="width: 250px;" id="id">
															@if($c->venda_id != null || $c->venda_caixa_id != null)
															@if($c->venda_id != null)
															{{ $c->venda->cliente->razao_social }}
															@else
															@if($c->vendaCaixa->cliente)
															{{ $c->vendaCaixa->cliente->razao_social }}
															@else
															--
															@endif
															@endif
															@else
															@if($c->cliente_id != null)
															{{ $c->cliente->razao_social }}
															@else
															--
															@endif
															@endif

														</span>
													</td>
													<td class="datatable-cell"><span class="codigo" style="width: 150px;" id="id">{{$c->categoria->nome}}</span>
													</td>

													@if(empresaComFilial())
													<td class="datatable-cell">
														<span class="codigo" style="width: 150px;">
															{{ $c->filial_id ? $c->filial->descricao : 'Matriz' }}
														</span>
													</td>
													@endif

													<td class="datatable-cell"><span class="codigo" style="width: 120px;" id="id">{{$c->referencia}}</span>
													</td>

													<td class="datatable-cell">
														<span class="codigo" style="width: 120px;" id="id">
															R$ {{number_format($c->valor_integral, $casasDecimais, ',', '.')}}
														</span>
													</td>

													<td class="datatable-cell">
														<span class="codigo" style="width: 120px;" id="id">
															R$ {{number_format($c->valor_recebido, $casasDecimais, ',', '.')}}
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 120px;" id="id">
															{{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y')}}
															@if(!$c->status)
															<br>
															<span class="text-danger" style="font-size: 10px">{{ $c->diasAtraso() }}</span>
															@endif
														</span>
													</td>

													<td class="datatable-cell">
														<span class="codigo" style="width: 120px;" id="id">
															@if($c->status)
															{{ \Carbon\Carbon::parse($c->data_recebimento)->format('d/m/Y')}}
															@else
															--
															@endif
														</span>
													</td>

													<td class="datatable-cell">
														<span class="codigo" style="width: 120px;" id="id">
															{{ \Carbon\Carbon::parse($c->created_at)->format('d/m/Y H:i:s')}}
														</span>
													</td>

													<td class="datatable-cell">
														<span class="codigo" style="width: 120px;" id="id">
															@if($c->status == true)
															<span class="label label-xl label-inline label-light-success">Recebido</span>
															@else
															<span class="label label-xl label-inline label-light-danger">Pendente</span>
															@endif
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 120px;" id="id">
															@if($c->tipo_pagamento)
															{{$c->tipo_pagamento}}
															@else
															--
															@endif
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 300px;" id="id">
															@if($c->status == false)
															@if(!$c->boleto)
															<a title="Editar" href="/contasReceber/edit/{{$c->id}}" class="btn btn-warning btn-sm">
																<i class="la la-edit"></i>
															</a>
															<a title="Remover" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/contasReceber/delete/{{ $c->id }}" }else{return false} })' href="#!" class="btn btn-danger btn-sm">
																<i class="la la-trash"></i>
															</a>

															@endif

															<a title="Receber" href="/contasReceber/receber/{{$c->id}}" class="btn btn-success btn-sm">
																<i class="la la-money"></i>
															</a>

															@else
															<a title="Estornar conta" href="/contasReceber/estorno/{{$c->id}}" class="btn btn-dark btn-sm">
																<i class="la la-arrow-alt-circle-left"></i>
															</a>

															@if($c->observacao)
															<button class="btn btn-primary btn-sm" onclick="viewObservacao('{{ $c->observacao }}')">
																<i class="la la-info"></i>
															</button>
															@endif
															@endif

															@if(!$c->boleto)

															<a title="Gerar boleto" href="/boleto/gerar/{{$c->id}}" class="btn btn-info btn-sm">
																<i class="la la-file"></i>
															</a>

															@else

															<a title="Imprimir boleto" target="_blank" href="/boleto/imprimir/{{$c->boleto->id}}" class="btn btn-primary btn-sm">
																<i class="la la-print"></i>
															</a>


															<a title="Gerar remessa" target="_blank" href="/boleto/gerarRemessa/{{$c->boleto->id}}" class="btn btn-info btn-sm">
																<i class="la la-file-alt"></i>
															</a>

															@endif

															@if($c->venda_id != null || $c->venda_caixa_id != null)
															<a title="Detalhes da venda" href="/contasReceber/detalhes_venda/{{$c->id}}" class="btn btn-warning btn-sm" title="Detalhes da venda">
																<i class="la la-list"></i>
															</a>
															@endif

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

						<input type="hidden" id="contas" value="{{json_encode($contas)}}" name="">
						<div class="pb-5" data-wizard-type="step-content">
							<div class="row">
								@foreach($contas as $c)

								<div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
									<div class="card card-custom gutter-b example example-compact">
										<div class="card-header">
											<div class="card-title">

												@if(!$c->status)
												<label style="display: none" class="checkbox checkbox-success" for="sel_{{$c->id}}">
													<input id="sel_{{$c->id}}" class="select" type="checkbox" name="Checkboxes5"/>
													<span></span>
												</label>
												@endif
												<h3 style="width: 230px; font-size: 20px; height: 10px;" class="card-title">
													R$ <strong id="valor_{{$c->id}}">{{number_format($c->valor_integral, $casasDecimais, ',', '.')}}</strong>
												</h3>

											</div>

											<div class="card-toolbar">
												<div class="dropdown dropdown-inline" data-toggle="tooltip" title="" data-placement="left" data-original-title="Ações">
													<a href="#" class="btn btn-hover-light-primary btn-sm btn-icon btn-action" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
														<i class="fa fa-ellipsis-h"></i>
													</a>
													<div class="dropdown-menu p-0 m-0 dropdown-menu-md dropdown-menu-left">
														<!--begin::Navigation-->
														<ul class="navi navi-hover">
															<li class="navi-header font-weight-bold py-4">
																<span class="font-size-lg">Ações:</span>
															</li>

															<li class="navi-separator mb-3 opacity-70"></li>

															@if($c->status == false)
															@if(!$c->boleto)

															<li class="navi-item">
																<a href="/contasReceber/edit/{{$c->id}}" class="navi-link">
																	<span class="navi-text">
																		<span class="label label-xl label-inline label-light-warning">Editar</span>
																	</span>
																</a>
															</li>
															<li class="navi-item">
																<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/contasReceber/delete/{{ $c->id }}" }else{return false} })' href="#!" class="navi-link">
																	<span class="navi-text">
																		<span class="label label-xl label-inline label-light-danger">Excluir</span>
																	</span>
																</a>
															</li>
															@endif
															@endif

															@if($c->status == false)

															<li class="navi-item">
																<a href="/contasReceber/receber/{{$c->id}}" class="navi-link">
																	<span class="navi-text">
																		<span class="label label-xl label-inline label-light-success">Receber</span>
																	</span>
																</a>
															</li>

															@endif

															@if(!$c->boleto)
															<li class="navi-item">
																<a href="/boleto/gerar/{{$c->id}}" class="navi-link">
																	<span class="navi-text">
																		<span class="label label-xl label-inline label-light-info">Gerar Boleto</span>
																	</span>
																</a>
															</li>

															@else

															<li class="navi-item">
																<a target="_blank" href="/boleto/imprimir/{{$c->boleto->id}}" class="navi-link">
																	<span class="navi-text">
																		<span class="label label-xl label-inline label-light-info">Imprimir</span>
																	</span>
																</a>
															</li>

															<li class="navi-item">
																<a target="_blank" href="/boleto/gerarRemessa/{{$c->boleto->id}}" class="navi-link">
																	<span class="navi-text">
																		<span class="label label-xl label-inline label-light-warning">Gerar remessa</span>
																	</span>
																</a>
															</li>

															@endif

															@if($c->venda_id != null || $c->venda_caixa_id != null)
															<li class="navi-item">
																<a href="/contasReceber/detalhes_venda/{{$c->id}}" class="navi-link">
																	<span class="navi-text">
																		<span class="label label-xl label-inline label-light-warning">Detalhes da Venda</span>
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
												<span class="kt-widget__label">Cliente:</span>
												<a target="_blank" class="kt-widget__data text-success">
													@if($c->venda_id != null || $c->venda_caixa_id != null)
													@if($c->venda_id != null)
													<th>{{ $c->venda->cliente->razao_social }}</th>
													@else
													@if($c->vendaCaixa->cliente)
													<th>{{ $c->vendaCaixa->cliente->razao_social }}</th>
													@else
													--
													@endif
													@endif
													@else
													@if($c->cliente_id != null)
													<th>{{ $c->cliente->razao_social }}</th>
													@else
													<th> -- </th>
													@endif
													@endif
												</a>
											</div>
											<div class="kt-widget__info">
												<span class="kt-widget__label">Categoria:</span>
												<a class="kt-widget__data text-success">
													{{$c->categoria->nome}}
												</a>
											</div>
											<div class="kt-widget__info">
												<span class="kt-widget__label">Valor recebido:</span>
												<a class="kt-widget__data text-success">
													{{ number_format($c->valor_recebido, $casasDecimais, ',', '.') }}
												</a>
											</div>
											<div class="kt-widget__info">
												<span class="kt-widget__label">Data de registro:</span>
												<a class="kt-widget__data text-success">
													{{ \Carbon\Carbon::parse($c->created_at)->format('d/m/Y H:i:s')}}
												</a>
											</div>
											<div class="kt-widget__info">
												<span class="kt-widget__label">Data de recebimento:</span>
												<a class="kt-widget__data text-success">
													@if($c->status)
													{{ \Carbon\Carbon::parse($c->data_recebimento)->format('d/m/Y')}}
													@else
													--
													@endif
												</a>
											</div>
											<div class="kt-widget__info">
												<span class="kt-widget__label">Data de vencimento:</span>
												<a class="kt-widget__data text-success">
													{{ \Carbon\Carbon::parse($c->data_vencimento)->format('d/m/Y')}} @if(!$c->status) -
													<span class="text-danger" style="font-size: 10px">{{ $c->diasAtraso() }}</span>@endif
												</a>
											</div>

											<div class="kt-widget__info">
												<span class="kt-widget__label">Referência:</span>
												<a class="kt-widget__data text-success">
													{{ $c->referencia }}
												</a>
											</div>

											<div class="kt-widget__info">
												<span class="kt-widget__label">Estado:</span>
												@if($c->status == true)
												<span class="label label-xl label-inline label-light-success">Recebido</span>
												@else
												<span class="label label-xl label-inline label-light-danger">Pendente</span>
												@endif
											</div>
											<div class="kt-widget__info">
												<span class="kt-widget__label">Tipo de pagamento:</span>

												<a class="kt-widget__data text-info">
													@if($c->tipo_pagamento)
													{{$c->tipo_pagamento}}
													@else
													--
													@endif
												</a>
											</div>

											@if(empresaComFilial())
											<div class="kt-widget__info">
												<span class="kt-widget__label">Local:</span>
												<a target="_blank" class="kt-widget__data text-success">
													{{ $c->filial_id ? $c->filial->descricao : 'Matriz' }}
												</a>
											</div>
											@endif

											@if($c->boleto)
											<div class="kt-widget__info" style="margin-top: 3px;">
												<span class="kt-widget__label">Boleto:</span>

												<span class="label label-xl label-inline label-light-success">{{$c->boleto->banco->banco}}</span>

											</div>
											@else
											<div class="kt-widget__info" style="margin-top: 3px;">
												<span class="kt-widget__label">Boleto:</span>
												<span class="label label-xl label-inline label-light-info">--</span>

											</div>
											@endif
										</div>
									</div>
								</div>

								<?php
								$somaValor += $c->valor_integral;
								$somaRecebido += $c->valor_recebido;

								if($c->status == false)
									$somaPendente += $c->valor_integral;
								?>

								@endforeach

							</div>
						</div>
					</div>
				</div>


				<div class="d-flex justify-content-between align-items-center flex-wrap">
					<div class="d-flex flex-wrap py-2 mr-3">
						@if(isset($links))
						{{$contas->links()}}
						@endif
					</div>
				</div>

				<div class="card-body">
					<div class="row">
						<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
							<div class="card card-custom gutter-b example example-compact">
								<div class="card-body">
									<div class="row">
										<div class="col-4">

											<h3 class="">Valor a Receber: <strong style="margin-left: 5px;"> R$ {{number_format($somaPendente, 2, ',', '.') }}</strong></h3>
										</div>

										<div class="col-4">

											<h3 class="">Valor Recebido: <strong style="margin-left: 5px;"> R$ {{number_format($somaRecebido, 2, ',', '.') }}</strong></h3>
										</div>
										<div style="display: none" class="col-4 div-valor-selecionado">

											<h3 class="">Valor Selecionado: <strong style="margin-left: 5px;" id="valor-selecionado">R$ 0,00</strong></h3>
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

	@section('javascript')
	<script type="text/javascript">
		var CONTAS = [];
		var ADICIONADAS = [];
		var SOMA = 0;
		var BTNSELECIONA = false;
		$(function () {
			CONTAS = JSON.parse($('#contas').val());
		});
		$('.select').click(() => {
			ADICIONADAS = []
			CONTAS.map((c) => {
				let s = $('#sel_'+c.id).is(':checked');
				if(s){
					ADICIONADAS.push(c)
				}
			})
			somaArray();
			verificaBotaoGerar();
		})

		function somaArray(){
			SOMA = 0;
			ADICIONADAS.map((a) => {
				SOMA += parseFloat(a.valor_integral.replace(',', '.'))
			})
			console.log(SOMA)
			$('#valor-selecionado').html(formatReal(SOMA))
		}

		$('#btn_seleciona_varios').click(() => {
			BTNSELECIONA = !BTNSELECIONA

			$('#grade').trigger('click')

			if(BTNSELECIONA){
				$('#btn_seleciona_varios').removeClass('btn-light')
				$('#btn_seleciona_varios').addClass('btn-info')
				$('.checkbox').css('display', 'block')
				$('.checkbox').css('margin-right', '5px')
				$('.btn-action').css('display', 'none')
				$('.div-valor-selecionado').css('display', 'block')
			}else{
				$('#btn_seleciona_varios').removeClass('btn-info')
				$('#btn_seleciona_varios').addClass('btn-light')

				$('.checkbox').css('display', 'none')
				$('.div-valor-selecionado').css('display', 'none')
				$('.btn-action').css('display', 'block')
			}

			ADICIONADAS.map((a) => {
				$('#sel_'+a.id).prop('checked', false)
			})
		})

		function verificaBotaoGerar(){
			if(ADICIONADAS.length > 1){
				$('#btn_gerar').css('display', 'inline-block')
				$('#btn_receber').removeClass('d-none')
			}else{
				$('#btn_gerar').css('display', 'none')
				$('#btn_receber').addClass('d-none')
			}
		}

		$('#btn_gerar').click(() => {
			let temp = [];
			ADICIONADAS.map((a) => {
				temp.push(a.id)
			})
			console.log(temp)
			location.href = path + 'boleto/gerarMultiplos/'+temp
		})

		$('#btn_receber').click(() => {
			let temp = [];
			ADICIONADAS.map((a) => {
				temp.push(a.id)
			})
			console.log(temp)
			location.href = path + 'contasReceber/receberMultiplos/'+temp
		})

		function formatReal(v){
			return v.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'});
		}

		function viewObservacao(observacao){
			swal("", observacao, "info")
		}

	</script>
	@endsection

	@endsection
