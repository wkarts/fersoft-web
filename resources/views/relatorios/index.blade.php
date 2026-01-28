@extends('default.layout')
@section('css')
<style type="text/css">
	.card-header{
		border-radius: 7px!important;
	}
	.la-angle-double-down{
		color: #fff !important;
	}
</style>
@endsection
@section('content')
<div class="card gutter-b">
	<div class="card-body">

        <!-- Exibe mensagens de sucesso e erro -->
        @if(session('mensagem_sucesso'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('mensagem_sucesso') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(session('mensagem_erro'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('mensagem_erro') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<div class="row">

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne1a">
									<h3 class="card-title">Relatório de Vendas<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne1a" class="collapse" data-parent="#accordionExample1">
								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/filtroVendas2">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Tipo de pagamento</label>
													<div class="">
														<select class="custom-select form-control" id="" name="tipo_pagamento">
															<option value="">Todos</option>
															@foreach(App\Models\Venda::tiposPagamento() as $key => $t)
															<option value="{{$key}}">{{$t}}</option>

															@endforeach
														</select>

													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label ">Vendedor</label>

													<select class="form-control select2" style="width: 100%" id="kt_select2_8" name="funcionario">
														<option value="null">Selecione o vendedor</option>
														@foreach($funcionarios as $p)
														<option value="{{$p->id}}">{{$p->id}} - {{$p->nome}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-12 col-lg-8">
													<label class="col-form-label">Cliente</label>

													<select class="form-control select2" style="width: 100%" id="kt_select2_9" name="cliente_id">
														<option value="null">Selecione o cliente</option>
														@foreach($clientes as $c)
														<option value="{{$c->id}}">{{$c->razao_social}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group col-lg-4 col-12">
													<label class="col-form-label">Número NFCe</label>
													<div class="">
														<input id="numero_nfce" type="text" class="form-control" name="numero_nfce" value="">
													</div>
												</div>

												@if(empresaComFilial())
												{!! __view_locais_select_relatorios() !!}
												@endif

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-primary px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionCompras">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseCompra">
									<h3 class="card-title">Relatório de Compras<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseCompra" class="collapse" data-parent="#accordionCompras">
								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/filtroComprasDetalhado">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Nro. NFe</label>
													<div class="">
														<input id="numero_nfe" type="text" class="form-control" name="numero_nfe" value="">
													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Fornecedor</label>

													<select class="form-control select2" style="width: 100%" id="kt_select2_13" name="fornecedor_id">
														<option value="null">Selecione o fornecedor</option>
														@foreach($fornecedores as $p)
														<option value="{{$p->id}}">{{$p->id}} - {{$p->razao_social}}</option>
														@endforeach
													</select>
												</div>

												@if(empresaComFilial())
												{!! __view_locais_select_relatorios() !!}
												@endif

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-danger px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne1">
									<h3 class="card-title">Relatório de Somatório de Vendas<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne1" class="collapse" data-parent="#accordionExample1">
								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/filtroVendas">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Nro. Resultados</label>
													<div class="">
														<input id="razao_social" type="text" class="form-control" name="total_resultados" value="">
													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Ordem</label>

													<select class="custom-select form-control" id="" name="ordem">
														<option value="desc">Maior Valor</option>
														<option value="asc">Menor Valor</option>
														<option value="data">Data</option>
													</select>

												</div>

												@if(empresaComFilial())
												{!! __view_locais_select_relatorios() !!}
												@endif

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-primary px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
					<div class="accordion" id="accordionExample1">
						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne2">
									<h3 class="card-title">Relatório de Somatório de Compras <i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne2" class="collapse" data-parent="#accordionExample1">
								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/filtroCompras">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Nro. Resultados</label>
													<div class="">
														<input id="razao_social" type="text" class="form-control" name="total_resultados" value="">
													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Ordem</label>

													<select class="custom-select form-control" id="" name="ordem">
														<option value="desc">Maior Valor</option>
														<option value="asc">Menor Valor</option>
														<option value="data">Data</option>
													</select>

												</div>

												@if(empresaComFilial())
												{!! __view_locais_select_relatorios() !!}
												@endif

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- <div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne3">
									<h3 class="card-title">Relatório de Vendas por Cliente <i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne3" class="collapse" data-parent="#accordionExample1">
								<div class="card-content">
									<div class="col-xl-12">
										<form method="get" action="/relatorios/filtroVendaClientes">
											<div class="row">

												<div class="form-group validated col-12">
													<label class="col-form-label">Cliente</label>

													<select class="form-control select2" style="width: 100%" id="kt_select2_9" name="cliente_id">
														<option value="null">Selecione o cliente</option>
														@foreach($clientes as $c)
														<option value="{{$c->id}}">{{$c->razao_social}}</option>
														@endforeach
													</select>
												</div>
												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Nro. Resultados</label>
													<div class="">
														<input id="razao_social" type="text" class="form-control" name="total_resultados" value="">
													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Ordem</label>

													<select class="custom-select form-control" id="" name="ordem">
														<option value="desc">Mais Vendas</option>
														<option value="asc">Menos Vendas</option>
													</select>

												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-info px-6 font-weight-bold">Gerar Relatório</button>
												</div>



											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div> -->

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne4">
									<h3 class="card-title">Relatório de Lucratividade Sintético <i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne4" class="collapse" data-parent="#accordionExample1">
								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/filtroLucro">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label dt">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6" id="lucro_col">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Ordem</label>

													<select class="custom-select form-control" id="tipo_lucro" name="tipo">
														<option value="grupo">Agrupado</option>
														<option value="detalhado">Detalhado</option>
													</select>

												</div>

												@if(empresaComFilial())
												{!! __view_locais_select_relatorios() !!}
												@endif

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-danger px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExampleAnalitico">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOneLucroAnalitico">
									<h3 class="card-title">Relatório de Lucratividade Analítico <i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOneLucroAnalitico" class="collapse" data-parent="#accordionExampleAnalitico">
								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/relatorioLucroAnalitico" id="form-analitico">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label dt">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6" id="lucro_col">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Código da venda</label>
													<div class="">
														<input id="razao_social" type="text" class="form-control" name="codigo_venda" value="">
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Produto</label>
													<div class="">
														<select class="form-control select2" style="width: 100%" id="kt_select2_12" name="produto_id">
															<option value="null">Selecione o produto</option>
															@foreach($produtos as $p)
															<option value="{{$p->id}}">{{$p->id}} - {{$p->nome}}</option>
															@endforeach
														</select>
													</div>
												</div>

												@if(empresaComFilial())
												{!! __view_locais_select_relatorios() !!}
												@endif

												<input type="hidden" name="excel" id="input_analitico_excel" value="0">

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-warning px-6 font-weight-bold">Gerar Relatório</button>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button id="btn-excel-analitico" type="button" style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Exportar excel</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne5">
									<h3 class="card-title">Relatório de estoque de produtos <i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne5" class="collapse" data-parent="#accordionExample1">
								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" id="form-estoque" method="get" action="/relatorios/estoqueProduto">
											<div class="row">

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Ordem</label>

													<select class="custom-select form-control" id="usuario" name="ordem">
														<option value="nome">Nome</option>
														<option value="qtd">Quantidade</option>
														<!-- <option value="ultima_movimentacao">Ultima movimentação</option> -->
													</select>

												</div>
												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Categoria</label>

													<select class="custom-select form-control" id="categoria" name="categoria">
														<option value="todos">todas</option>
														@foreach($categorias as $c)
														<option value="{{$c->id}}">{{$c->nome}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Marca</label>

													<select class="custom-select form-control" id="marca" name="marca">
														<option value="todos">todas</option>
														@foreach($marcas as $c)
														<option value="{{$c->id}}">{{$c->nome}}</option>
														@endforeach
													</select>
												</div>
												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Nro. Resultados</label>
													<div class="">
														<input id="razao_social" type="text" class="form-control" name="total_resultados" value="">
													</div>
												</div>

												<input type="hidden" name="excel" id="input_estoque_excel" value="0">

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-info px-6 font-weight-bold">Gerar Relatório</button>
												</div>
												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button id="btn-excel-estoque" type="button" style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Exportar excel</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne51">
									<h3 class="card-title">Relatório de lista de preço<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne51" class="collapse" data-parent="#accordionExample1">
								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" id="form-lista-preco" method="get" action="/relatorios/listaPreco">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6" id="lucro_col">
													<label class="col-form-label">Data de criação</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data" class="form-control" value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>
												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Lista de preços</label>

													<select required class="custom-select form-control" id="lista_id" name="lista_id">
														<option value=""></option>
														@foreach($listaPrecos as $l)
														<option value="{{$l->id}}">{{$l->nome}}</option>
														@endforeach
													</select>
												</div>

												<input type="hidden" name="excel" id="input_lista_preco_excel" value="0">

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-info px-6 font-weight-bold">Gerar Relatório</button>
												</div>
												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button id="btn-excel-lista-preco" type="button" style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Exportar excel</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne6">

									<h3 class="card-title">Relatório de Comissão de Vendas <i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne6" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/comissaoVendas">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Produto</label>
													<div class="">
														<select class="form-control select2" style="width: 100%" id="kt_select2_1" name="produto">
															<option value="null">Selecione o produto</option>
															@foreach($produtos as $p)
															<option value="{{$p->id}}">{{$p->id}} - {{$p->nome}}</option>
															@endforeach
														</select>
													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label ">Vendedor</label>

													<select class="form-control select2" style="width: 100%" id="kt_select2_2" name="funcionario">
														<option value="null">Selecione o vendedor</option>
														@foreach($funcionarios as $p)
														<option value="{{$p->id}}">{{$p->id}} - {{$p->nome}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne7">
									<h3 class="card-title">Relatório de Estoque Mínimo <i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne7" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/filtroEstoqueMinimo">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Nro. Resultados</label>
													<div class="">
														<input id="razao_social" type="text" class="form-control" name="total_resultados" value="">
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Produto</label>
													<div class="">
														<select class="form-control select2" style="width: 100%" id="kt_select2_14" name="produto_id">
															<option value="null">Selecione o produto</option>
															@foreach($produtos as $p)
															<option value="{{$p->id}}">{{$p->id}} - {{$p->nome}}</option>
															@endforeach
														</select>
													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label ">Categoria</label>

													<select class="custom-select form-control" id="categoria" name="categoria_id">
														<option value="">--</option>
														@foreach($categorias as $c)
														<option value="{{$c->id}}">
															{{$c->nome}}
														</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-warning px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne8">
									<h3 class="card-title">Relatório de Vendas Diária(s) Detalhado <i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne8" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/filtroVendaDiaria">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">ID do Produto</label>
													<div class="">
														<input id="produto_id" type="text" class="form-control" name="produto_id" value="">
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Número NFCe</label>
													<div class="">
														<input id="numero_nfce" type="text" class="form-control" name="numero_nfce" value="">
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Nro. Resultados</label>
													<div class="">
														<input id="razao_social" type="text" class="form-control" name="total_resultados" value="">
													</div>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-dark px-6 font-weight-bold">Gerar Relatório</button>
												</div>


											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne9">

									<h3 class="card-title">Relatório Custo/Venda <i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<input type="hidden" id="subs" value="{{json_encode($subs)}}">

							<div id="collapseOne9" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/filtroVendaProdutos">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Nro. Resultados</label>
													<div class="">
														<input id="razao_social" type="text" class="form-control" name="total_resultados" value="">
													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Ordem</label>

													<select class="custom-select form-control" id="" name="ordem">
														<option value="desc">Mais Vendidos</option>
														<option value="asc">Menos Vendidos</option>
														<option value="alfa">Alfabética</option>
													</select>

												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label ">Marca</label>

													<select class="custom-select form-control" id="" name="marca_id">
														<option value="">--</option>
														@foreach($marcas as $m)
														<option value="{{$m->id}}">
															{{$m->nome}}
														</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label ">Categoria</label>

													<select class="custom-select form-control" id="categoria" name="categoria_id">
														<option value="">--</option>
														@foreach($categorias as $c)
														<option value="{{$c->id}}">
															{{$c->nome}}
														</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label ">Sub Categoria</label>

													<select class="custom-select form-control" id="sub_categoria_id" name="sub_categoria_id">
														<option value="">--</option>

													</select>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-danger px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne10">

									<h3 class="card-title">Relatório Tipos de Pagamento <i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne10" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/tiposPagamento">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>


												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-danger px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne12">

									<h3 class="card-title">Relatório Cadastro de Produtos <i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne12" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/cadastroProdutos">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Status</label>
													<div class="">
														<select class="custom-select form-control" id="status" name="status">
															<option value="0">Ativo</option>
															<option value="1">Inativo</option>
														</select>
													</div>
												</div>


												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-info px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne13">

									<h3 class="card-title">Relatório de Venda de Produtos <i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne13" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/vendaDeProdutos">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Ordem</label>

													<select class="custom-select form-control" id="" name="ordem">
														<option value="desc">Mais Vendidos</option>
														<option value="asc">Menos Vendidos</option>
														<option value="alfa">Alfabética</option>
													</select>

												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Categoria</label>

													<select class="custom-select form-control" id="categoria" name="categoria">
														<option value="todos">todas</option>
														@foreach($categorias as $c)
														<option value="{{$c->id}}">{{$c->nome}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Marca</label>

													<select class="custom-select form-control" id="marca" name="marca">
														<option value="todos">todas</option>
														@foreach($marcas as $c)
														<option value="{{$c->id}}">{{$c->nome}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Produto</label>
													<div class="">
														<select class="form-control select2" style="width: 100%" id="kt_select2_15" name="produto_id">
															<option value="null">Selecione o produto</option>
															@foreach($produtos as $p)
															<option value="{{$p->id}}">{{$p->id}} - {{$p->nome}}</option>
															@endforeach
														</select>
													</div>
												</div>


												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Natureza de operação</label>

													<select class="form-control" name="natureza_id">
														<option value="">Selecione a natureza de operação</option>
														@foreach($naturezas as $n)
														<option value="{{$n->id}}">{{$n->natureza}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label ">Vendedor</label>

													<select class="form-control select2 select2-custom" style="width: 100%" name="vendedor">
														<option value="">selecione</option>
														@foreach($funcionarios as $p)
														<option value="{{$p->id}}">{{$p->nome}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-info px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne21">

									<h3 class="card-title">Relatório de Venda de Produtos por comissão/vendedor<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne21" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/vendaDeProdutos2">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input required type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input required type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Ordem</label>

													<select class="custom-select form-control" id="" name="ordem">
														<option value="desc">Mais Vendidos</option>
														<option value="asc">Menos Vendidos</option>
														<option value="alfa">Alfabética</option>
													</select>

												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Categoria</label>
													<select class="custom-select form-control" id="categoria-select" name="categoria">
														<option value="">Todos</option>
														@foreach($categorias as $c)
														<option value="{{$c->id}}">{{$c->nome}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Produto</label>
													<div class="">
														<select class="form-control select2-custom produtos-select" name="produtos[]" multiple>
														</select>
													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label ">Vendedor</label>

													<select class="form-control select2 select2-custom" style="width: 100%" id="" required name="vendedores[]" multiple>
														@foreach($funcionarios2 as $p)
														<option value="{{$p->id}}">{{$p->nome}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Natureza de operação</label>

													<select class="form-control" name="natureza_id">
														<option value="">Selecione a natureza de operação</option>
														@foreach($naturezas as $n)
														<option value="{{$n->id}}">{{$n->natureza}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-info px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne14">

									<h3 class="card-title">Relatório Fiscal<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne14" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/fiscal">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control date-input" value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control date-input" value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group validated col-12">
													<label class="col-form-label">Cliente</label>

													<select class="form-control select2" style="width: 100%" id="kt_select2_11" name="cliente_id">
														<option value="null">Selecione o cliente</option>
														@foreach($clientes as $c)
														<option value="{{$c->id}}">{{$c->razao_social}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-12">
													<label class="col-form-label">Natureza de Operação</label>

													<select class="form-control" id="kt_select2_9" name="natureza_id">
														<option value="">Selecione a natureza de operação</option>
														@foreach($naturezas as $n)
														<option value="{{$n->id}}">{{$n->natureza}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">CFOP</label>
													<div class="">
														<input id="cfop" type="text" class="form-control cfop" name="cfop" value="">
													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Estado</label>
													<select class="custom-select form-control" id="estado" name="estado">
														<option value="aprovados">APROVADOS</option>
														<option value="cancelados">CANCELADOS</option>
													</select>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Tipos documento</label>
													<select class="custom-select form-control" id="tipo" name="tipo">
														<option value="todos">TODOS</option>
														<option value="nfe">NFe</option>
														<option value="nfce">NFCe</option>
														<option value="cte">CTe</option>
														<option value="mdfe">MDFe</option>
													</select>
												</div>

												@if(empresaComFilial())
												{!! __view_locais_select_relatorios() !!}
												@endif


												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-info px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne15">
									<h3 class="card-title">Relatório de Produto por CFOP<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne15" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/porCfop">
											<div class="row">

												<div class="form-group validated col-12">
													<label class="col-form-label">Selecione o CFOP</label>

													<select class="form-control select2" style="width: 100%" id="kt_select2_10" name="cfop">
														<option value="null">Selecione o cfop</option>
														@foreach($cfops as $c)
														<option value="{{$c}}">{{$c}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-info px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne16">
									<h3 class="card-title">Relatório de Boletos<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne16" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/boletos">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Status</label>
													<div class="">
														<select class="custom-select form-control" id="" name="status">
															<option value="">Todos</option>
															<option value="recebido">Recebido</option>
															<option value="pendente">Pendente</option>

														</select>

													</div>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne17">
									<h3 class="card-title">Relatório de Comissão de Assessor<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne17" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/comissaoAssessor">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Assessor</label>
													<div class="">
														<select class="form-control select2-custom" style="width: 100%" name="assessor_id">
															<option value="">Selecione o assessor</option>
															@foreach($assessores as $a)
															<option value="{{$a->id}}">{{ $a->razao_social }}</option>
															@endforeach
														</select>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Status</label>
													<div class="">
														<select class="custom-select form-control" id="" name="status">
															<option value="">Todos</option>
															<option value="pago">Pago</option>
															<option value="pendente">Pendente</option>

														</select>

													</div>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne18">
									<h3 class="card-title">Relatório de CTe<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne18" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/cte">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Remetente</label>
													<div class="">
														<select class="form-control select2-custom" style="width: 100%" name="remetente_id">
															<option value="">Selecione o remetente</option>
															@foreach($clientes as $a)
															<option value="{{$a->id}}">{{ $a->razao_social }}</option>
															@endforeach
														</select>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Status</label>
													<div class="">
														<select class="custom-select form-control" id="" name="status">
															<option value="">Todos</option>
															<option value="pago">Pago</option>
															<option value="pendente">Pendente</option>

														</select>

													</div>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-danger px-6 font-weight-bold">Gerar Relatório</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne19">
									<h3 class="card-title">Relatório de Clientes<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne19" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/cliente" id="form-clientes">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Grupo</label>
													<div class="">
														<select class="custom-select form-control" id="" name="grupo_id">
															<option value="">Todos</option>
															@foreach($gruposCliente as $g)
															<option value="{{$g->id}}">{{$g->nome}}</option>
															@endforeach
														</select>

													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Assessor</label>
													<div class="">
														<select class="form-control select2-custom" style="width: 100%" name="assessor_id">
															<option value="">Selecione o assessor</option>
															@foreach($assessores as $a)
															<option value="{{$a->id}}">{{ $a->razao_social }}</option>
															@endforeach
														</select>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Status</label>
													<div class="">
														<select class="custom-select form-control" id="" name="status">
															<option value="">Todos</option>
															<option value="ativo">Ativo</option>
															<option value="inativo">Inativo</option>

														</select>

													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Limite de resultado</label>
													<div class="">
														<div class="input-group">
															<input type="text" name="limite" class="form-control" value="100" required/>
														</div>
													</div>
												</div>

												<input type="hidden" name="excel" id="input_clientes_excel" value="0">

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-info px-6 font-weight-bold">Gerar Relatório</button>
												</div>
												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button id="btn-excel-clientes" type="button" style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Exportar excel</button>
												</div>

											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne20">
									<h3 class="card-title">Relatório de Locação<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne20" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/locacao" id="form-locacao">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Cliente</label>

													<select class="form-control select2-custom" style="width: 100%" name="cliente_id">
														<option value="">Selecione o cliente</option>
														@foreach($clientes as $c)
														<option value="{{$c->id}}">{{$c->razao_social}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Produto</label>
													<div class="">
														<select class="form-control select2-custom" style="width: 100%" name="produto_id">
															<option value="">Selecione o produto</option>
															@foreach($produtosLocacao as $p)
															<option value="{{$p->id}}">{{$p->id}} - {{$p->nome}}</option>
															@endforeach
														</select>
													</div>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Gerar Relatório</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne22">
									<h3 class="card-title">Relatório de Perca<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne22" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/perca" id="form-locacao">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Usuario</label>

													<select class="form-control select2-custom" style="width: 100%" name="usuario_id">
														<option value=""></option>
														@foreach($usuarios as $c)
														<option value="{{$c->id}}">{{$c->nome}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Motivo</label>

													<select class="form-control" style="width: 100%" name="motivo">
														<option value="">todos</option>
														@foreach(\App\Models\Estoque::listaMotivosReducao() as $l)
														<option value="{{$l}}">{{$l}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Produto</label>
													<div class="">
														<select class="form-control select2-custom" style="width: 100%" name="produto_id">
															<option value=""></option>
															@foreach($produtosLocacao as $p)
															<option value="{{$p->id}}">{{$p->id}} - {{$p->nome}}</option>
															@endforeach
														</select>
													</div>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Gerar Relatório</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne23">
									<h3 class="card-title">Relatório de Sangrias<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne23" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/sangrias" id="form-locacao">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Usuario</label>

													<select class="form-control select2-custom" style="width: 100%" name="usuario_id">
														<option value=""></option>
														@foreach($usuarios as $c)
														<option value="{{$c->id}}">{{$c->nome}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Gerar Relatório</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne24">
									<h3 class="card-title">Relatório de Taxas<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne24" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/taxas" id="form-locacao">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Gerar Relatório</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne25">
									<h3 class="card-title">Relatório de Descontos<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne25" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/descontos" id="form-locacao">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Gerar Relatório</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne26">
									<h3 class="card-title">Relatório de Acréscimos<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne26" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/acrescimos" id="form-locacao">
											<div class="row">

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Gerar Relatório</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne27">
									<h3 class="card-title">Relatório de Contas Recebidas<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne27" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/contas-recebidas" id="form-locacao">
											<div class="row">

												<div class="form-group validated col-12 col-lg-8">
													<label class="col-form-label">Cliente</label>

													<select class="form-control select2-custom" style="width: 100%" id="" name="cliente_id">
														<option value="null">Selecione o cliente</option>
														@foreach($clientes as $c)
														<option value="{{$c->id}}">{{$c->razao_social}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Gerar Relatório</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header card-report">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne28">
									<h3 class="card-title">Relatório de Curva ABC<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne28" class="collapse" data-parent="#accordionExample1">

								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorios/curva" id="form-locacao">
											<div class="row">


												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Inicial</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_inicial" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Data Final</label>
													<div class="">
														<div class="input-group date">
															<input type="text" name="data_final" class="form-control" readonly value="" id="kt_datepicker_3" />
															<div class="input-group-append">
																<span class="input-group-text">
																	<i class="la la-calendar"></i>
																</span>
															</div>
														</div>
													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label ">Vendedor</label>

													<select class="form-control select2-custom" style="width: 100%" name="funcionario">
														<option value="null">Selecione o vendedor</option>
														@foreach($funcionarios as $p)
														<option value="{{$p->id}}">{{$p->id}} - {{$p->nome}}</option>
														@endforeach
													</select>
												</div>

												<div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
													<button style="width: 100%" class="btn btn-light-success px-6 font-weight-bold">Gerar Relatório</button>
												</div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

                {{-- COLUNA – RELATÓRIO DE PESAGEM (SIMPLES) --}}
                <div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                    <div class="accordion" id="accordionPesagemSimples">
                        <div class="card gutter-b example example-compact">
                            <div class="card-header card-report">
                                <div class="card-title collapsed" data-toggle="collapse" data-target="#collapsePesagemSimples">
                                    <h3 class="card-title">
                                        Relatório de Pesagem
                                        <i class="la la-angle-double-down"></i>
                                    </h3>
                                </div>
                            </div>

                            <div id="collapsePesagemSimples" class="collapse" data-parent="#accordionPesagemSimples">
                                <div class="card-content">
                                    <div class="col-xl-12">
                                        <form target="_blank" method="get" action="{{ url('/relatorios/pesagem') }}" id="form-pesagem-simples">
                                            <div class="row">
                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                    <label class="col-form-label">ID da Pesagem</label>
                                                    <input type="text" name="pesagem_id" class="form-control" placeholder="Digite o ID da Pesagem" value="">
                                                </div>

                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                    <label class="col-form-label">Data Inicial</label>
                                                    <div class="input-group date">
                                                        <input
                                                            type="text"
                                                            name="data_inicial"
                                                            class="form-control js-datepicker-pesagem-simples"
                                                            value=""
                                                            id="kt_datepicker_3"
                                                            placeholder="dd/mm/aaaa"
                                                        >

                                                        <div class="input-group-append">
                                                            <span class="input-group-text"><i class="la la-calendar"></i></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                    <label class="col-form-label">Data Final</label>
                                                    <div class="input-group date">
                                                        <input
                                                            type="text"
                                                            name="data_final"
                                                            class="form-control js-datepicker-pesagem-simples"
                                                            value=""
                                                            id="kt_datepicker_3"
                                                            placeholder="dd/mm/aaaa"
                                                        >
                                                        <div class="input-group-append">
                                                            <span class="input-group-text"><i class="la la-calendar"></i></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                    <label class="col-form-label">Veículo</label>
                                                    <select class="form-control select2-custom" name="veiculo_id">
                                                        <option value="">Selecione o veículo</option>
                                                        @foreach($veiculos as $veiculo)
                                                            <option value="{{ $veiculo->id }}">{{ $veiculo->placa }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                    <label class="col-form-label">Status</label>
                                                    <select class="form-control select2-custom" name="status">
                                                        <option value="">Todos</option>
                                                        <option value="em andamento">Em Andamento</option>
                                                        <option value="concluído">Concluído</option>
                                                    </select>
                                                </div>

                                                <div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
                                                    <button class="btn btn-light-success px-6 font-weight-bold" style="width: 100%;">
                                                        Gerar Relatório
                                                    </button>
                                                </div>
                                            </div> {{-- row --}}
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- COLUNA – RELATÓRIO ANALÍTICO DE PESAGEM --}}
                <div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                    <div class="accordion" id="accordionPesagemAnalitico">
                        <div class="card gutter-b example example-compact mt-5 mt-lg-0">
                            <div class="card-header card-report">
                                <div class="card-title collapsed" data-toggle="collapse" data-target="#collapsePesagemAnalitico">
                                    <h3 class="card-title">
                                        Relatório Analítico de Pesagem
                                        <i class="la la-angle-double-down"></i>
                                    </h3>
                                </div>
                            </div>

                            <div id="collapsePesagemAnalitico" class="collapse" data-parent="#accordionPesagemAnalitico">
                                <div class="card-content">
                                    <div class="col-xl-12">
                                        <form target="_blank" method="get" action="{{ route('pesagens.relatorios.analitico') }}" id="form-pesagem-analitico">
                                            <div class="row">
                                                <input type="hidden" name="formato" id="formato_analitico" value="html">

                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                    <label class="col-form-label">Data Inicial</label>
                                                    <div class="input-group date">
                                                        <input
                                                            type="text"
                                                            name="data_inicial"
                                                            class="form-control js-datepicker-pesagem-analitico"
                                                            value=""
                                                            id="kt_datepicker_3"
                                                            placeholder="dd/mm/aaaa"
                                                        >
                                                        <div class="input-group-append">
                                                            <span class="input-group-text"><i class="la la-calendar"></i></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                    <label class="col-form-label">Data Final</label>
                                                    <div class="input-group date">
                                                        <input
                                                            type="text"
                                                            name="data_final"
                                                            class="form-control js-datepicker-pesagem-analitico"
                                                            value=""
                                                            id="kt_datepicker_3"
                                                            placeholder="dd/mm/aaaa"
                                                        >
                                                        <div class="input-group-append">
                                                            <span class="input-group-text"><i class="la la-calendar"></i></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                    <label class="col-form-label">Tipo</label>
                                                    <select class="form-control select2-custom" name="tipo">
                                                        <option value="">Todos</option>
                                                        <option value="compra">Compra</option>
                                                        <option value="venda">Venda</option>
                                                        <option value="avulsa">Avulsa</option>
                                                    </select>
                                                </div>

                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                    <label class="col-form-label">Status</label>
                                                    <select class="form-control select2-custom" name="status">
                                                        <option value="">Todos</option>
                                                        <option value="concluído">Concluído</option>
                                                        <option value="em andamento">Em andamento</option>
                                                    </select>
                                                </div>

                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                    <label class="col-form-label">NF-e vinculada</label>
                                                    <select class="form-control select2-custom" name="nota">
                                                        <option value="">Com e sem</option>
                                                        <option value="com">Somente com NF-e</option>
                                                        <option value="sem">Somente sem NF-e</option>
                                                    </select>
                                                </div>

                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                    <label class="col-form-label">Cliente</label>
                                                    <select class="form-control select2-custom" name="cliente_id">
                                                        <option value="">Todos</option>
                                                        @foreach($clientes as $c)
                                                            <option value="{{ $c->id }}">{{ $c->razao_social }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                    <label class="col-form-label">Fornecedor</label>
                                                    <select class="form-control select2-custom" name="fornecedor_id">
                                                        <option value="">Todos</option>
                                                        @foreach($fornecedores as $f)
                                                            <option value="{{ $f->id }}">{{ $f->razao_social }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
                                                    <div class="d-flex flex-wrap" style="gap: 10px;">
                                                        <button type="submit" class="btn btn-light-primary px-6 font-weight-bold">
                                                            Visualizar HTML
                                                        </button>
                                                        <button type="button" class="btn btn-light-success px-6 font-weight-bold" onclick="gerarPdfAnalitico()">
                                                            Baixar PDF
                                                        </button>
                                                    </div>
                                                </div>
                                            </div> {{-- row --}}
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div> {{-- card analítico --}}
                    </div>
                </div>

                {{--
                                <div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
                                    <div class="accordion" id="accordionNFeGap">
                                        <div class="card gutter-b example example-compact">
                                            <div class="card-header card-report">
                                                <div class="card-title collapsed" data-toggle="collapse" data-target="#collapseNFeGap">
                                                    <h3 class="card-title">Relatório de Salto de Numeração NFe/NFCe <i class="la la-angle-double-down"></i></h3>
                                                </div>
                                            </div>

                                            <div id="collapseNFeGap" class="collapse" data-parent="#accordionNFeGap">
                                                <div class="card-content">
                                                    <div class="col-xl-12">
                                                        <form target="_blank" method="get" action="{{ url('/relatorios/nfe-numeracao-gaps') }}">
                                                            <div class="row">

                                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                                    <label class="col-form-label">Data Inicial</label>
                                                                    <div class="input-group date">
                                                                        <input type="text" name="data_inicial" class="form-control date-input" value="" id="kt_datepicker_3" />
                                                                        <div class="input-group-append">
                                                            <span class="input-group-text">
                                                                <i class="la la-calendar"></i>
                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                                    <label class="col-form-label">Data Final</label>
                                                                    <div class="input-group date">
                                                                        <input type="text" name="data_final" class="form-control date-input" value="" id="kt_datepicker_3" />
                                                                        <div class="input-group-append">
                                                            <span class="input-group-text">
                                                                <i class="la la-calendar"></i>
                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                                    <label class="col-form-label">Tipo de documento</label>
                                                                    <select class="custom-select form-control" name="tipo">
                                                                        <option value="todos">TODOS</option>
                                                                        <option value="nfe">NFe</option>
                                                                        <option value="nfce">NFCe</option>
                                                                    </select>
                                                                </div>

                                                                <div class="form-group validated col-lg-6 col-md-6 col-sm-6">
                                                                    <label class="col-form-label">Série</label>
                                                                    <input type="text" name="serie" class="form-control" placeholder="Ex.: 1" value="">
                                                                </div>

                                                                @if(empresaComFilial())
                                                                    {!! __view_locais_select_relatorios() !!}
                                                                @endif

                                                                <div class="form-group validated col-lg-12 col-xl-12 mt-12 mt-lg-0">
                                                                    <button style="width: 100%" class="btn btn-light-info px-6 font-weight-bold">
                                                                        Gerar Relatório
                                                                    </button>
                                                                </div>

                                                            </div> {{-- row --}}
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                --}}

            </div>
		</div>
	</div>
</div>

@section('javascript')
<script type="text/javascript">
	var SUBCATEGORIAS = [];
	$(function () {

		SUBCATEGORIAS = JSON.parse($('#subs').val())
		console.log(SUBCATEGORIAS)
	})

	$('#categoria').change(() => {
		montaSubs()
	})

	function montaSubs(){
		let categoria_id = $('#categoria').val()
		let subs = SUBCATEGORIAS.filter((x) => {
			return x.categoria_id == categoria_id
		})

		let options = ''
		subs.map((s) => {
			options += '<option value="'+s.id+'">'
			options += s.nome
			options += '</option>'
		})
		$('#sub_categoria_id').html('<option value="">--</option>')
		$('#sub_categoria_id').append(options)
	}

	$('#btn-excel-estoque').click(() => {
		$('#input_estoque_excel').val('1')
		$('#form-estoque').submit()
	})

	$('#btn-excel-lista-preco').click(() => {
		$('#input_lista_preco_excel').val('1')
		$('#form-lista-preco').submit()
	})

        $('#btn-excel-analitico').click(() => {
                $('#input_analitico_excel').val('1')
                $('#form-analitico').submit()
        })

        $('#btn-excel-clientes').click(() => {
                $('#input_clientes_excel').val('1')
                $('#form-clientes').submit()
        })

        window.gerarPdfAnalitico = () => {
                const input = document.getElementById('formato_analitico');
                const original = input.value;
                input.value = 'pdf';
                input.form.submit();
                input.value = original;
        }

	$(function () {
		setTimeout(() => {
			$(".produtos-select").select2({
				minimumInputLength: 2,
				width: "100%",
				ajax: {
					cache: true,
					url: path + "produtos/pesquisaSelect2",
					dataType: "json",
					data: function (params) {
						console.clear();
						var query = {
							pesquisa: params.term,
							categoria_id: $("#categoria-select").val(),
						};
						return query;
					},
					processResults: function (response) {
						console.log("response", response);
						var results = [];

						$.each(response, function (i, v) {
							var o = {};
							o.id = v.id;

							o.text = v.nome + (v.codBarras != "SEM GTIN" ? (" | " + v.codBarras) : "");
							o.value = v.id;

							results.push(o);
						});
						return {
							results: results,
						};
					},
				},
			});
		}, 500);
	});

</script>
@endsection

@endsection
