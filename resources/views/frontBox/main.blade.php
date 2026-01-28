<!DOCTYPE html>

<html lang="br">
<!-- begin::Head -->

<head>
	<meta charset="utf-8" />

	<title>{{$title}}</title>
	<meta name="description" content="Updates and statistics">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="Cache-Control" content="no-cache">
	<!--begin::Fonts -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700|Roboto:300,400,500,600,700">

	<link href="/metronic/css/fullcalendar.bundle.css" rel="stylesheet" type="text/css" />
	<!-- <link href="/metronic/css/uppy.bundle.css" rel="stylesheet" type="text/css" /> -->
	<link href="/metronic/css/wizard.css" rel="stylesheet" type="text/css" />

	<link href="/css/style.css" rel="stylesheet" type="text/css" />

	<!--end::Page Vendors Styles -->
	@if($tema == 2)
	<link href="/css/escuro.css" rel="stylesheet" type="text/css" />
	@endif

	<!--begin::Global Theme Styles(used by all pages) -->
	<link href="/metronic/css/plugins.bundle.css" rel="stylesheet" type="text/css" />
	<link href="/metronic/css/prismjs.bundle.css" rel="stylesheet" type="text/css" />
	<link href="/metronic/css/style.bundle.css" rel="stylesheet" type="text/css" />

	<link href="/metronic/css/pricing.css" rel="stylesheet" type="text/css" />
	<!--end::Global Theme Styles -->

	<!--begin::Layout Skins(used by all pages) -->

	<link href="/metronic/css/light.css" rel="stylesheet" type="text/css" />
	<link href="/metronic/css/light-menu.css" rel="stylesheet" type="text/css" />
	<link href="/metronic/css/dark-brand.css" rel="stylesheet" type="text/css" />
	<link href="/metronic/css/dark-aside.css" rel="stylesheet" type="text/css" />

	<link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">

	<link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

    <!-- Inclusão Centralizada dos Scripts de Monitoramento -->
    @include('layouts.tracking')

	<style type="text/css">
		.select2-selection__arrow:before {
			content: "";
			position: absolute;
			right: 7px;
			top: 42%;
			border-top: 5px solid #888;
			border-left: 4px solid transparent;
			border-right: 4px solid transparent;
		}
		.no-padding{
			padding-left: 0 !important;
			padding-right: 0 !important;
		}
		.ativo{
			background-color: #55C6BD;
			color: #fff;
		}
		.desativo{
			background-color: #EBEDF3;
			color: #000;
		}
		.img-prod{
			/*height: 100px;*/
			margin-top: 10px;

		}
		@media only screen and (max-width: 1000px) {
			#div-categorias{
				display: none;
			}
		}
		@media only screen and (min-width: 1001px) and (max-width: 3000px){
			#div-categorias{
				display: inline
			}
		}
		#atalho_add:hover{
			cursor: pointer;
		}

		.money-cel{
			width: 120px;
			height: 50px;
		}

		.money-moeda{
			width: 80px;
		}

		#focus-codigo:hover{
			cursor: pointer
		}

		.select2-selection__arroww:before {
			content: "";
			position: absolute;
			right: 7px;
			top: 42%;
			border-top: 5px solid #888;
			border-left: 4px solid transparent;
			border-right: 4px solid transparent;
		}

		.qrcode{
			display: block;
			margin-left: auto;
			margin-right: auto;
			width: 50%;
		}

		.icon-trash:hover{
			cursor: pointer;
		}
	</style>
</head>


<!-- end::Head -->

<!-- begin::Body -->

<body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled subheader-fixed aside-enabled aside-fixed aside-minimize-hoverable page-loading">

    <input type="hidden" id="filial" value="{{ session('user_logged')['filial_id'] }}">

	<input type="hidden" id="produtos" value="{{json_encode($produtos)}}" name="">

	<input type="hidden" id="categorias" value="{{json_encode($categorias)}}" name="">
	<input type="hidden" id="clientes" value="{{json_encode($clientes)}}" name="">
	<input type="hidden" id="agendamento_id" value="{{$agendamento_id ?? 0}}" name="">
	<input type="hidden" id="caixa_livre" value="{{$usuario->caixa_livre}}" name="">

	<input type="hidden" id="_token" value="{{ csrf_token() }}">
	<input type="hidden" id="credito_troca" value="0" name="credito_troca">

	<input type="hidden" id="valor_entrega" @if(isset($valor_entrega)) value="{{$valor_entrega}}" @else value='0' @endif>

	@if(isset($itens))
	<input type="hidden" id="itens_pedido" value="{{json_encode($itens)}}">
	<input type="hidden" id="valor_total" @if(isset($valor_total)) value="{{$valor_total}}" @else value='0' @endif>
	<input type="hidden" id="delivery_id" @if(isset($delivery_id)) value="{{$delivery_id}}" @else value='0' @endif>
	<input type="hidden" id="bairro" @if(isset($bairro)) value="{{$bairro}}" @else value='0' @endif>

	<input type="hidden" id="codigo_comanda_hidden" @if(isset($cod_comanda)) value="{{$cod_comanda}}" @else value='0' @endif name="">
	@endif

	@if(isset($venda))
	<input type="hidden" id="venda" value="{{$venda}}">
	@endif

	<input type="hidden" id="PDV_VALOR_RECEBIDO" @if(isset($atalhos)) value="{{$atalhos->valor_recebido_automatico}}" @else value='0' @endif>
	<input type="hidden" id="ATALHOS" value="{{ json_encode($atalhos) }}">
	<input type="hidden" value="{{$usuario->permite_desconto}}" id="permite_desconto">
	<input type="hidden" value="{{$config->percentual_max_desconto}}" id="percentual_max_desconto">
	@if(isset($config))
	<input type="hidden" id="pass" value="{{ $config->senha_remover }}">
	@endif

	@php
	$caixa_tipos_pagamento = App\Models\ConfigCaixa::getTiposPagamento();
	$caixa_tipo_pagamento_padrao = App\Models\ConfigCaixa::getTipoPagamentoPadrao();
	@endphp

	<div class="card card-custom gutter-b example example-compact">
		<div class="col-lg-12">
			<div class="container">
				<div class="row" style="margin-top: 10px;">

					<div class="col-sm-2 col-lg-2 col-xl-3 col-md-6 col-6">

						<h4><strong id="timer">00:00:00</strong>
							@if($usuario->caixa_livre)
							<span class="text-info">Caixa Livre</span>
							<button data-toggle="modal" data-target="#modal-funcionarios" class="btn btn-sm btn-light-info">
								<i class="la la-user"></i>
							</button>
							@endif
						</h4>
					</div>

					<div class="col-sm-4 col-lg-4 col-md-6 col-xl-6 col-6">
						<h4>Ambiente: <strong class="text-success">{{session('user_logged')['ambiente']}}</strong>
							@if($video_url != null)
							<a target="_blank" href="{{$video_url}}" class="btn btn-light-info">
								<i class="la la-video"></i>
								Video Ajuda
							</a>
							@endif

						</h4>

					</div>


					<div class="col-sm-6 col-lg-6 col-xl-3 col-md-12 col-12">

						<div class="dropdown dropdown-inline show" data-toggle="tooltip" title="" data-placement="left" data-original-title="Ações para PDV">


							<a href="/frenteCaixa/list" class="btn btn-light-danger">
								<i class="la la-angle-double-left"></i>

								Sair
								<!--end::Svg Icon-->
							</a>
							<a href="#" class="btn btn-light-info" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
								Ações
								<i class="la la-angle-down"></i>
								<!--end::Svg Icon-->

							</a>

							<div class="dropdown-menu dropdown-menu-md dropdown-menu-right p-0 m-0 " style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(-211px, 39px, 0px);" x-placement="bottom-end">
								<!--begin::Navigation-->
								<ul class="navi navi-hover">
									<li class="navi-header font-weight-bold py-4">
										<span class="font-size-lg">Selecione:</span>
									</li>
									<li class="navi-separator mb-3 opacity-70"></li>

									<li class="navi-item">
										<a href="/frenteCaixa/list" class="navi-link">
											<span class="navi-text">
												<span class="label label-xl label-inline label-light-success">Lista de vendas</span>
											</span>
										</a>
									</li>
									<li class="navi-item">
										<a href="/frenteCaixa/devolucao" class="navi-link">
											<span class="navi-text">
												<span class="label label-xl label-inline label-light-danger">Devolução/Cancelamento</span>
											</span>
										</a>
									</li>
									<li class="navi-item">
										<a data-toggle="modal" href="#!" data-target="#modal2" class="navi-link">
											<span class="navi-text">
												<span class="label label-xl label-inline label-light-dark">Sangria</span>
											</span>
										</a>
									</li>

									<li class="navi-item">
										<a data-toggle="modal" href="#!" data-target="#modal-supri" class="navi-link">
											<span class="navi-text">
												<span class="label label-xl label-inline label-light-warning">Suprimento de Caixa</span>
											</span>
										</a>
									</li>
									<li class="navi-item">
										<a data-toggle="modal" href="#!" data-target="#modal3" onclick="fluxoDiario()" class="navi-link">
											<span class="navi-text">
												<span class="label label-xl label-inline label-light-primary">
													Fluxo Diário
												</span>
											</span>
										</a>
									</li>
									<li class="navi-item">
										<a data-toggle="modal" href="#!" data-target="#modal-comanda" class="navi-link">
											<span class="navi-text">
												<span class="label label-xl label-inline label-light-success">Apontar Comanda</span>
											</span>
										</a>
									</li>

									<li class="navi-item">
										<a href="/frenteCaixa/fechar" class="navi-link">
											<span class="navi-text">
												<span class="label label-xl label-inline label-light-danger">
													Fechar Caixa
												</span>
											</span>
										</a>
									</li>

									<li class="navi-item">
										<a href="/frenteCaixa/config" class="navi-link">
											<span class="navi-text">
												<span class="label label-xl label-inline label-light-dark">
													Configuração
												</span>
											</span>
										</a>
									</li>

									<li class="navi-item">
										<a data-toggle="modal" href="#!" data-target="#modal-rascunhos" class="navi-link">
											<span class="navi-text">
												<span class="label label-xl label-inline label-light-warning">
													Rascunhos
												</span>
											</span>
										</a>
									</li>

									<li class="navi-item">
										<a data-toggle="modal" href="#!" data-target="#modal-consignadas" class="navi-link">
											<span class="navi-text">
												<span class="label label-xl label-inline label-light-primary">
													Consignadas
												</span>
											</span>
										</a>
									</li>

									<li class="navi-item">
										<a href="/frenteCaixa/list" class="navi-link">
											<span class="navi-text">
												<span class="label label-xl label-inline label-light-info">
													Sair
												</span>
											</span>
										</a>
									</li>
								</ul>

							</div>
						</div>
					</div>

				</div>
			</div>
			<input class="mousetrap" type="" autofocus style="border: none; width: 0px; height: 0px; " id="codBarras" name="">

			<hr>
			<div class="row">

				<div class="col-sm-12 col-lg-7 col-md-12 col-12">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<div class="row align-items-center" style="margin-top: 10px;">
								<div class="form-group validated col-sm-6 col-lg-6 col-12 col-sm-12">
									<label>Produto</label>
									<div class="input-group">

										<div class="input-group-prepend">
											<span class="input-group-text" id="focus-codigo">
												<li class="la la-barcode"></li>
											</span>
										</div>

										<select class="form-control select2" id="kt_select2_1" name="produto">

											<option value="-1">Selecione um produto, ou use código de barra</option>
											@foreach($produtosGroup as $p)
											<option value="{{$p->id}}">{{$p->nome}}@if($p->referencia != "")
												| REF: {{$p->referencia}}
												@endif
												@if($p->estoqueAtual() > 0)
												| Estoque: {{$p->estoqueAtual()}} {{$p->unidade_venda}}
												@endif
											</option>
											@endforeach
										</select>

									</div>
								</div>

								<div class="form-group validated col-sm-2 col-lg-2 col-5 col-sm-5">
									<div class="">
										<label>Valor</label>
										<input @if(!$usuario->permite_desconto) disabled @endif id="valor_item" placeholder="Valor" type="text" class="form-control" name="valor" value="{{number_format(0, $casasDecimais, ',', '.')}}">

									</div>
								</div>

								<div class="form-group validated col-sm-2 col-lg-2 col-5 col-sm-5">
									<div class="">
										<label>Quantidade</label>
										<input id="quantidade" placeholder="QTD" type="text" class="form-control" name="quantidade" value="1">
									</div>
								</div>

								<div class="form-group validated col-sm-2 col-lg-2 col-6 col-sm-6">
									<button style="margin-top: 18px;" id="adicionar-item" type="submit" class="btn btn-success">Adicionar</button>
								</div>
							</div>
						</div>
						<div class="card-body" style="height: 420px;">

							<div class="col-xl-12">
								<div class="row">
									<div class="col-xl-12">
										<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded" >

											<table class="datatable-table tbl-prod" style="max-width: 100%; overflow: scroll; max-height: 420px;">
												<thead class="datatable-head">
													<tr class="datatable-row" style="left: 0px;">
														<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 50px;">ITEM</span></th>
														<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 50px;">ID</span></th>
														<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">PRODUTO</span></th>
														<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">QTD</span></th>
														<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">VALOR</span></th>
														<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">SUBTOTAL</span></th>
														<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 30px;">#</span></th>
													</tr>
												</thead>
												<tbody class="datatable-body" id="body">


												</tbody>

											</table>

										</div>
									</div>
								</div>

							</div>
						</div>

					</div>

					<div class="card card-custom gutter-b example example-compact" style="margin-top: -20px; height: auto;">
						<div class="card-body">
							<div class="row align-items-center">

								<div class="col-sm-3 col-lg-3 col-6">
									<label>Desconto: R$ <strong id="valor_desconto">0,00</strong></label>
									<button @if(!$usuario->permite_desconto) disabled @endif onclick="setaDesconto()" style="margin-left: 4px; margin-top: -10px;" class="btn btn-link-primary btn-desconto">
										<i class="la la-edit"></i>
									</button>
								</div>
								<div class="col-sm-3 col-lg-3 col-6">

									<label>Acrescimo: R$ <strong id="valor_acrescimo">0,00</strong></label>
									<button onclick="setaAcresicmo()" style="margin-left: 4px; margin-top: -10px;" class="btn btn-link-primary">
										<i class="la la-edit"></i>
									</button>
								</div>
								<div class="col-sm-2 col-lg-2 col-3">
									<label>Lista de Preços</label>
								</div>

								<div class="col-sm-4 col-lg-4 col-6" style="margin-top: -8px;">
									@if(isset($listaPreco))

									<select class="custom-select form-control" id="lista_id" name="lista_id">

										<option value="0">Padrão</option>
										@foreach($listaPreco as $l)
										<option value="{{$l->id}}">{{$l->nome}} - {{$l->percentual_alteracao}}%
										</option>
										@endforeach
									</select>

									@else


									<select class="custom-select form-control" id="lista_id" name="lista_id">

										<option value="0">Padrão</option>
									</select>


									@endif
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="col-sm-5 col-lg-5 col-md-12 col-12" id="div-categorias">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<div class="row" style="height: 72px; overflow-x: auto; width: auto; white-space: nowrap">
								<div class="form-group validated col-sm-12 col-lg-12 col-12 col-sm-12" style="margin-top: 10px;">
									<a href="#!" id="cat_todos" onclick="categoria('todos')" style="height: 40px; min-width: 80px;" class="label label-xl label-inline label-light-muted ativo">Todos</a>
									@foreach($categorias as $c)
									<a href="#!" id="cat_{{$c->id}}" onclick="categoria('{{$c->id}}')" style="height: 40px; min-width: 80px;" class="label label-xl label-inline desativo">{{$c->nome}}</a>
									@endforeach

								</div>

							</div>
						</div>
						<div class="card-body" style="height: 533px; overflow-y: auto;">
							<div class="row" id="prods" style="visibility: hidden">
								@foreach($produtos as $p)
								<div class="col-sm-12 col-lg-6 col-md-6 col-xl-4" id="atalho_add" onclick="adicionarProdutoRapido('{{$p}}')">
									<div class="card card-custom gutter-b example example-compact">
										<div class="card-header" style="height: 200px;">
											<div class="symbol symbol-circle symbol-lg-100">
												@if($p->imagem)
												<img class="img-prod" src="/imgs_produtos/{{$p->imagem}}">
												@else
												<img class="img-prod" src="/imgs/no_image.png">
												@endif
											</div>

											<h6 style="font-size: 12px; width: 100%" class="kt-widget__label">
												{{substr($p->nome, 0, 40)}}
											</h6>

											<h6 style="font-size: 12px;" class="text-danger" class="kt-widget__label">
												R$ {{number_format($p->valor_venda, $casasDecimais, ',', '.')}}
											</h6>

											@if($p->gerenciar_estoque == 1)
											<h6 style="font-size: 10px; margin-right: -15px;" class="text-info" class="kt-widget__label">
												Estoque: {{$p->estoqueAtual()}}
											</h6>
											@endif
										</div>

									</div>
								</div>
								@endforeach

							</div>
						</div>
					</div>
				</div>

			</div>

			<div class="row">
				<div class="col-sm-12 col-lg-12 col-md-12 col-12">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-body">

							<div class="row">
								<div class="form-group validated col-sm-2 col-lg-2 col-6 col-md-6" style="margin-top: 5px;">
									<div class="">
										<input type="text" placeholder="Valor recebido" id="valor_recebido" name="valor_recebido" class="form-control money" value="">
									</div>
								</div>
								<!-- <div class="form-group validated col-sm-3 col-lg-2 col-6 col-md-6" style="margin-top: 5px;">
									<div class="">
										<select class="custom-select form-control" id="tipo-pagamento" name="tipo-pagamento">
											<option value="--">Selecione o Tipo de pagamento</option>
											@foreach($tiposPagamento as $key => $t)
											<option
											@if($config->tipo_pagamento_padrao == $key)
											selected
											@endif
											value="{{$key}}">{{$key}} - {{$t}}</option>
											@endforeach
										</select>
									</div>
								</div> -->

								<div class="form-group validated col-sm-3 col-lg-2 col-6 col-md-6" style="margin-top: 5px;">
									<div class="">
										<select class="custom-select form-control" id="tipo-pagamento" name="tipo-pagamento">
											<option value="--">Selecione o tipo de pagamento</option>

											@foreach($tiposPagamento as $key => $t)

											@if(sizeof($caixa_tipos_pagamento) > 0)
											@if(in_array($key, $caixa_tipos_pagamento))
											<option @if($caixa_tipo_pagamento_padrao == $key) selected @endif value="{{$key}}">{{$key}} - {{$t}}</option>
											@endif
											@else
											<option @if($config->tipo_pagamento_padrao == $key) selected @endif value="{{$key}}">{{$key}} - {{$t}}</option>
											@endif
											@endforeach

										</select>
									</div>
								</div>

								<div class="form-group validated col-sm-7 col-lg-4 col-12 col-md-12">
									<button title="Informar cliente" id="click-client" class="btn btn-danger btn-lg">
										<i class="la la-user"></i>
									</button>
									<button title="Pagamento multiplo" id="click-multi" class="btn btn-info btn-lg">
										<i class="la la-list"></i>
									</button>
									<button title="Observação da venda" onclick="setaObservacao()" class="btn btn-primary btn-lg">
										<i class="la la-marker"></i>
									</button>
									<button disabled id="finalizar-rascunho" title="Salvar como rascunho" onclick="salvarRascuho()" class="btn btn-warning btn-lg">
										<i class="la la-folder"></i>
									</button>
									<button disabled id="finalizar-consignado" title="Salvar em consignado" onclick="salvarConsignado()" class="btn btn-dark btn-lg">
										<i class="la la-user-tag"></i>
									</button>
								</div>

								<div class="form-group validated col-sm-6 col-lg-2 col-12 col-md-12">

									<span style="font-size: 20px; padding: 25px;" class="label label-xl label-inline label-light-info">Troco
										<strong style="margin-left: 5px;" id="valor-troco"> R$ 0,00</strong>
									</span>

								</div>
								<div class="form-group validated col-sm-6 col-lg-2 col-12 col-md-12">
									<div class="">

										<button id="finalizar-venda" style="width: 100%" class="btn btn-success btn-lg spinner-white spinner-right" disabled>
											<i class="la la-check"></i>
											Finalizar
											<strong id="total-venda">R$ 0,00</strong>
										</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Modals -->
		<input type="hidden" id="_token" value="{{csrf_token()}}" name="">

		<div class="modal fade" id="modal1" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-sm" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">É necessário abrir o caixa com um valor</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="form-group validated col-sm-12 col-lg-12">
								<label class="col-form-label" id="">Valor</label>
								<div class="">
									<input type="text" id="valor" name="valor" class="form-control money" value="">
								</div>
							</div>
						</div>

					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
						<button type="button" onclick="abrirCaixa()" class="btn btn-light-success font-weight-bold">Abrir</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal-pix" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Pagamento PIX</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="col-lg-12">
								<img src="" class="qrcode">
							</div>
						</div>

					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal-pag-mult" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-xl" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">PAGAMENTO MULTIPLO R$ <strong class="text-info" id="v-multi"></strong></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="col-12">
								<h6>Adicione as linhas de pagamento</h6>
							</div>
							<div class="form-group validated col-sm-6 col-lg-3">
								<select class="custom-select form-control" id="tipo_pagamento" name="tipo_pagamento">
									<option value="">Tipo de pagamento</option>


									@foreach($tiposPagamento as $key => $t)

									@if(sizeof($caixa_tipos_pagamento) > 0)
									@if(in_array($key, $caixa_tipos_pagamento))
									<option @if($caixa_tipo_pagamento_padrao == $key) selected @endif value="{{$key}}">{{$key}} - {{$t}}</option>
									@endif
									@else
									<option @if($caixa_tipo_pagamento_padrao == $key) selected @endif value="{{$key}}">{{$key}} - {{$t}}</option>
									@endif
									@endforeach
								</select>
							</div>
							<div class="form-group validated col-sm-6 col-lg-2">
								<input type="text" placeholder="Valor" id="valor_pagamento" name="valor_pagamento" class="form-control money" value="">
							</div>
							<div class="form-group validated col-sm-6 col-lg-4">
								<input type="text" placeholder="Observação/Descrição" id="observacao_pagamento" name="observacao_pagamento" class="form-control" value="">
							</div>
							<div class="form-group validated col-sm-6 col-lg-2">
								<input type="text" placeholder="Vencimento" id="vencimento_pagamento" name="vencimento_pagamento" class="form-control date-input kt_datepicker_3" value="">
							</div>
							<div class="form-group validated col-sm-6 col-lg-1">
								<button id="btnAddPag" onclick="addPag()" class="btn btn-success">
									<i class="la la-plus"></i>
								</button>
							</div>
						</div>
						<hr>
						<div class="row">
							<div class="col-12">
								<h6>Pagamentos adicionados</h6>
							</div>

							<div class="col-12">
								<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded" >

									<table class="datatable-table" style="max-width: 100%; overflow: scroll; max-height: 420px;">
										<thead class="datatable-head">
											<tr class="datatable-row" style="left: 0px;">
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">TIPO DO PAGAMENTO</span></th>
												<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">VENCIMENTO</span></th>
												<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">VALOR</span></th>
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">OBSERVAÇÃO</span></th>
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 60px;">AÇÕES</span></th>

											</tr>
										</thead>
										<tbody class="datatable-body" id="tbl_pag">

										</tbody>

									</table>

								</div>
							</div>

						</div>

						<div class="row">
							<div class="col-sm-6 col-lg-6">
								<br>
								<h4 class="">Valor restante
									<strong id="vl_restante" class="text-danger"></strong>
								</h4>
							</div>
						</div>

					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
						<button type="button" id="btn-ok-multi" class="btn btn-light-success font-weight-bold" disabled="true">OK</button>
					</div>
				</div>
			</div>
		</div>

		@if($usuario->somente_fiscal == 0)
		<div class="modal fade" id="modal-venda" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">FINALIZAR VENDA</h5>
						<button type="button" class="close btn-close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="form-group validated col-sm-4 col-lg-4 col-12 @if($certificado == null) disabled @endif">
								<button @if($certificado == null) disabled @endif class="btn btn-success" onclick="verificaCliente()" style="height: 50px; width: 100%">

									CUPOM FISCAL
									@if($certificado == null)
									<br>
									<b class="text-danger">Sem certificado</b>
									@endif

									@if($atalhos != null && $atalhos->finalizar_fiscal != "")
									<br>
									<b class="text-white">{{$atalhos->finalizar_fiscal}}</b>
									@endif
								</button>
							</div>

							<div class="form-group validated col-sm-4 col-lg-4 col-12">
								<button class="btn btn-info" id="btn_nao_fiscal" onclick="finalizarVenda('nao_fiscal')" style="height: 50px; width: 100%">
									CUPOM NÃO FISCAL
									@if($atalhos != null && $atalhos->finalizar_nao_fiscal != "")
									<br>
									<b class="text-white">{{$atalhos->finalizar_nao_fiscal}}</b>
									@endif
								</button>
							</div>

							<div class="form-group validated col-sm-4 col-lg-4 col-12">
								<button class="btn btn-warning disabled" id="conta_credito-btn" onclick="finalizarVenda('credito')" style="height: 50px; width: 100%">
									CONTA CRÉDITO
								</button>
							</div>


						</div>


					</div>
				</div>
			</div>
		</div>
		@else

		<div class="modal fade" id="modal-venda" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-sm" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">FINALIZAR VENDA</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="form-group validated col-12 @if($certificado == null) disabled @endif">
								<button @if($certificado == null) disabled @endif class="btn btn-success" onclick="verificaCliente()" style="height: 50px; width: 100%">

									CUPOM FISCAL
									@if($certificado == null)
									<br>
									<b class="text-danger">Sem certificado</b>
									@endif

									@if($atalhos != null && $atalhos->finalizar_fiscal != "")
									<br>
									<b class="text-white">{{$atalhos->finalizar_fiscal}}</b>
									@endif
								</button>
							</div>

						</div>
					</div>
				</div>
			</div>
		</div>

		@endif

		<div class="modal fade" id="modal-cpf-nota" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-md" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">CPF/CNPJ NA NOTA?</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<input type="hidden" id="nome" name="nome" class="form-control money" value="">
					<div class="modal-body">
						<div class="row">
							<div class="form-group validated col-sm-4 col-lg-4 col-12">
								<label class="col-form-label">TIPO</label>
								<select class="custom-select" id="select-doc">
									<option selected value="CPF">CPF</option>
									<option value="CNPJ">CNPJ</option>
								</select>
							</div>
							<div class="form-group validated col-sm-8 col-lg-8 col-12">
								<label class="col-form-label" id="tipo-doc">CPF</label>
								<input type="text" placeholder="CPF" id="cpf" name="cpf" class="form-control pula" value="">
							</div>
						</div>

						<div class="row">
							<div class="form-group validated col-12">
								<label class="col-form-label">Nome (opcional)</label>
								<input type="text" placeholder="Nome" id="nome-cpf" name="nome" class="form-control pula" value="">
							</div>
						</div>

					</div>
					<div class="modal-footer">
						<button style="width: 100%" id="btn-cpf" type="button" onPress="finalizarVenda('fiscal')" onclick="finalizarVenda('fiscal')" class="btn btn-success font-weight-bold spinner-white spinner-right pula">EMITIR</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal-funcionarios" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">VENDEDOR/FUNCINÁRIO COMISSÃO</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<input type="hidden" id="nome" name="nome" class="form-control money" value="">
					<div class="modal-body">
						<div class="row">
							<div class="form-group validated col-sm-12 col-lg-12 col-12">
								<label class="col-form-label" id="">Vendedor/Funcionário</label><br>
								<select class="form-control select2" style="width: 100%" id="kt_select2_4" name="cliente">
									<option value="null">Selecione</option>
									@foreach($funcionarios as $f)
									<option value="{{$f->id}}">{{$f->id}} - {{$f->nome}}</option>
									@endforeach
								</select>
							</div>
						</div>

					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
						<button type="button" onclick="selecionarFuncionario()" class="btn btn-light-success font-weight-bold">OK</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal-cliente" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">SELECIONAR CLIENTE</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<input type="hidden" id="nome" name="nome" class="form-control money" value="">
					<div class="modal-body">
						<div class="row">
							<div class="form-group validated col-sm-12 col-lg-12 col-12">
								<label class="col-form-label" id="">Cliente</label><br>
								<select class="form-control select2" style="width: 90%" id="kt_select2_3" name="cliente">
									<option value="null">Selecione o cliente</option>
									@foreach($clientes as $c)
									<option value="{{$c->id}}">{{$c->id}} - {{$c->razao_social}}</option>
									@endforeach
								</select>

								<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal-cliente-cad">
									<i class="la la-plus-circle icon-add"></i>
								</button>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
						<button type="button" onclick="selecionarCliente()" class="btn btn-light-success font-weight-bold">OK</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal2" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">SANGRIA DE CAIXA</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="form-group validated col-sm-6 col-lg-6 col-6">
								<label class="col-form-label" id="">Valor</label>
								<input type="text" placeholder="Valor" id="valor_sangria" name="valor_sangria" class="form-control" value="">
							</div>


							<div class="form-group validated col-sm-12 col-lg-12 col-12">
								<label class="col-form-label" id="">Observação</label>
								<input type="text" placeholder="Observação" id="obs_sangria" name="obs" class="form-control" value="">
							</div>

						</div>

					</div>
					<div class="modal-footer">
						<button style="width: 100%" type="button" onclick="sangriaCaixa()" class="btn btn-info font-weight-bold">OK</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal-supri" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">SUPRIMENTO DE CAIXA</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="form-group validated col-lg-2 col-6">
								<label class="col-form-label" id="">Valor</label>
								<input type="text" placeholder="Valor" id="valor_suprimento" name="valor_sangria" class="form-control money" value="">
							</div>

							<div class="form-group validated col-lg-4 col-6">
								<label class="col-form-label" id="">Tipo</label>
								<select id="tipo_suprimento" class="custom-select">
									<option value="">Selecione</option>
									@foreach(\App\Models\SuprimentoCaixa::tiposPagamento() as $key => $t)
									<option value="{{ $key }}">{{ $t }}</option>
									@endforeach
								</select>
							</div>
						</div>

						<div class="row">
							<div class="form-group validated col-sm-12 col-lg-12 col-12">
								<label class="col-form-label" id="">Observação</label>
								<input type="text" placeholder="Observação" id="obs_suprimento" name="obs" class="form-control" value="">
							</div>
						</div>

					</div>
					<div class="modal-footer">
						<button style="width: 100%" type="button" onclick="suprimentoCaixa()" class="btn btn-success font-weight-bold">OK</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal-rascunhos" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-xl" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">ÚLTIMOS RASCUNHOS</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						@if(sizeof($rascunhos) > 0)
						<div class="col-xl-12">
							<p class="text-info">*Últimos 20 rascunhos</p>
							<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

								<table class="datatable-table" style="max-width: 100%; overflow: scroll">
									<thead class="datatable-head">
										<tr class="datatable-row" style="left: 0px;">

											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Cliente</span></th>
											<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Data</span></th>
											<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor</span></th>
											<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Ações</span></th>
										</tr>
									</thead>
									<tbody class="datatable-body">
										@foreach($rascunhos as $v)
										<tr class="datatable-row">
											<td class="datatable-cell"><span class="codigo" style="width: 150px;">{{ $v->cliente->razao_social ?? 'NAO IDENTIFCADO' }}</span>
											</td>
											<td class="datatable-cell"><span class="codigo" style="width: 150px;">{{ \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i:s')}}</span>
											</td>
											<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($v->valor_total, $casasDecimais, ',', '.') }}</span>
											</td>
											<td class="datatable-cell">
												<a title="EDITAR RASCUNHO" href="/frenteCaixa/edit/{{$v->id}}" class="btn btn-warning">
													<i class="la la-edit"></i>
												</a>
												<a title="REMOVER RASCUNHO" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/frenteCaixa/deleteRascunho/{{ $v->id }}" }else{return false} })' href="#!" class="btn btn-danger">
													<i class="la la-trash"></i>
												</a>
											</td>

										</tr>
										@endforeach
									</tbody>
								</table>
							</div>
						</div>
						@else
						<p>Nenhum rascunho encontrado</p>
						@endif
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal-consignadas" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-xl" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">ÚLTIMOS CONSIGNADOS</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						@if(sizeof($consignadas) > 0)
						<div class="col-xl-12">
							<p class="text-info">*Últimas 20 vendas consignadas</p>
							<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

								<table class="datatable-table" style="max-width: 100%; overflow: scroll">
									<thead class="datatable-head">
										<tr class="datatable-row" style="left: 0px;">

											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Cliente</span></th>
											<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Data</span></th>
											<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor</span></th>
											<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Ações</span></th>
										</tr>
									</thead>
									<tbody class="datatable-body">
										@foreach($consignadas as $v)
										<tr class="datatable-row">
											<td class="datatable-cell"><span class="codigo" style="width: 150px;">{{ $v->cliente->razao_social ?? 'NAO IDENTIFCADO' }}</span>
											</td>
											<td class="datatable-cell"><span class="codigo" style="width: 150px;">{{ \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i:s')}}</span>
											</td>
											<td class="datatable-cell"><span class="codigo" style="width: 100px;">{{ number_format($v->valor_total, $casasDecimais, ',', '.') }}</span>
											</td>
											<td class="datatable-cell">
												<span class="codigo" style="width: 100px;">
													<a title="EDITAR RASCUNHO" href="/frenteCaixa/edit/{{$v->id}}" class="btn btn-warning">
														<i class="la la-edit"></i>
													</a>
												</span>
											</td>

										</tr>
										@endforeach
									</tbody>
								</table>
							</div>
						</div>
						@else
						<p>Nenhum consignado encontrado</p>
						@endif
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal3" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">FLUXO DIÁRIO</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>

					<div class="modal-body">

						<div class="row" style="height: 350px; overflow-y: auto;">
							<div class="col-sm-12 col-lg-12 col-12">
								<h5>Abertura de Caixa:</h5>
								<div id="fluxo_abertura_caixa"></div>
							</div>

							<div class="col-sm-12 col-lg-12 col-12">
								<h5>Sangrias:</h5>
								<div id="fluxo_sangrias"></div>
							</div>

							<div class="col-sm-12 col-lg-12 col-12">
								<h5>Suprimentos:</h5>
								<div id="fluxo_suprimentos"></div>
							</div>

							<div class="col-sm-12 col-lg-12 col-12">
								<h5>Vendas:</h5>
								<div id="fluxo_vendas"></div>
							</div>

							<div class="col-sm-12 col-lg-12 col-12">
								<h5>Total em caixa:
									<strong id="total_caixa" class="text-success"></strong></h5>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal4" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">SUGESTÃO DE TROCO</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>

					<div class="modal-body">
						<h2>Valor do troco: <strong id="valor_troco" class="text-danger">0,00</strong></h2>

						<div class="row" style="height: 300px; overflow-y: auto;">
							<div class="col-sm-12 col-lg-12 col-12">

								<div class="row 50_reais" style="display: none">
									<div class="col-sm-3 col-lg-3 col-3">
										<img class="money-cel" src="/imgs/50_reais.jpeg">
									</div>
									<div class="col-sm-3 col-lg-3 col-3">
										<h4 id="qtd_50_reais"></h4>
									</div>
								</div>
								<div class="row 20_reais" style="display: none">
									<div class="col-sm-3 col-lg-3 col-3">
										<img class="money-cel" src="/imgs/20_reais.jpeg">
									</div>
									<div class="col-sm-3 col-lg-3 col-3">
										<h4 id="qtd_20_reais"></h4>
									</div>
								</div>

								<div class="row 10_reais" style="display: none">
									<div class="col-sm-3 col-lg-3 col-3">
										<img class="money-cel" src="/imgs/10_reais.jpeg">
									</div>
									<div class="col-sm-3 col-lg-3 col-3">
										<h4 id="qtd_10_reais"></h4>
									</div>
								</div>

								<div class="row 5_reais" style="display: none">
									<div class="col-sm-3 col-lg-3 col-3">
										<img class="money-cel" src="/imgs/5_reais.jpeg">
									</div>
									<div class="col-sm-3 col-lg-3 col-3">
										<h4 id="qtd_5_reais"></h4>
									</div>
								</div>

								<div class="row 2_reais" style="display: none">

									<div class="col-sm-3 col-lg-3 col-3">
										<img class="money-cel" src="/imgs/2_reais.jpeg">
									</div>
									<div class="col-sm-3 col-lg-3 col-3">
										<h4 id="qtd_2_reais"></h4>
									</div>
								</div>

								<div class="row 1_real" style="display: none">
									<div class="col-sm-3 col-lg-3 col-3">
										<img class="money-moeda" src="/imgs/1_real.png">
									</div>
									<div class="col-sm-3 col-lg-3 col-3">
										<h4 id="qtd_1_real"></h4>
									</div>
								</div>

								<div class="row 50_centavo" style="display: none">
									<div class="col-sm-3 col-lg-3 col-3">
										<img class="money-moeda" src="/imgs/50_centavo.png">
									</div>
									<div class="col-sm-3 col-lg-3 col-3">
										<h4 id="qtd_50_centavos"></h4>
									</div>
								</div>

								<div class="row 25_centavo" style="display: none">
									<div class="col-sm-3 col-lg-3 col-3">
										<img class="money-moeda" src="/imgs/25_centavo.png">
									</div>
									<div class="col-sm-3 col-lg-3 col-3">
										<h4 id="qtd_25_centavos"></h4>
									</div>
								</div>

								<div class="row 10_centavo" style="display: none">
									<div class="col-sm-3 col-lg-3 col-3">
										<img class="money-moeda" src="/imgs/10_centavo.png">
									</div>
									<div class="col-sm-3 col-lg-3 col-3">
										<h4 id="qtd_10_centavos"></h4>
									</div>
								</div>


								<div class="row 5_centavo" style="display: none">
									<div class="col-sm-3 col-lg-3 col-3">
										<img class="money-moeda" src="/imgs/5_centavo.png">
									</div>
									<div class="col-sm-3 col-lg-3 col-3">
										<h4 id="qtd_5_centavos"></h4>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal-cliente-cad" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-xl" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Novo Cliente</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">

						<div class="row">
							<div class="col-xl-12">

								<div class="row">
									<div class="form-group col-sm-12 col-lg-12">
										<label>Pessoa:</label>
										<div class="radio-inline">
											<label class="radio radio-success">
												<input name="group1" type="radio" id="pessoaFisica"/>
												<span></span>
												FISICA
											</label>
											<label class="radio radio-success">
												<input name="group1" type="radio" id="pessoaJuridica"/>
												<span></span>
												JURIDICA
											</label>
										</div>
									</div>
								</div>
								<div class="row">

									<div class="form-group validated col-sm-3 col-lg-4">
										<label class="col-form-label" id="lbl_cpf_cnpj">CPF</label>
										<div class="">
											<input type="text" id="cpf_cnpj" class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif" name="cpf_cnpj">

										</div>
									</div>
									<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
										<label class="col-form-label">UF</label>

										<select class="custom-select form-control" id="sigla_uf" name="sigla_uf">
											@foreach(App\Models\Cidade::estados() as $c)
											<option value="{{$c}}">{{$c}}
											</option>
											@endforeach
										</select>

									</div>
									<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
										<br><br>
										<a type="button" id="btn-consulta-cadastro" onclick="consultaCadastro()" class="btn btn-success spinner-white spinner-right">
											<span>
												<i class="fa fa-search"></i>
											</span>
										</a>
									</div>

								</div>

								<div class="row">
									<div class="form-group validated col-sm-10 col-lg-6">
										<label class="col-form-label">Razao Social/Nome</label>
										<div class="">
											<input id="razao_social2" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif">

										</div>
									</div>

									<div class="form-group validated col-sm-10 col-lg-6">
										<label class="col-form-label">Nome Fantasia</label>
										<div class="">
											<input id="nome_fantasia2" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif">
										</div>
									</div>

									<div class="form-group validated col-sm-3 col-lg-3">
										<label class="col-form-label" id="lbl_ie_rg">RG</label>
										<div class="">
											<input type="text" id="ie_rg" class="form-control @if($errors->has('ie_rg')) is-invalid @endif">
										</div>
									</div>
									<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
										<label class="col-form-label">Consumidor Final</label>

										<select class="custom-select form-control" id="consumidor_final">
											<option value="1">SIM</option>
											<option value="0">NAO</option>
										</select>

									</div>

									<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
										<label class="col-form-label">Contribuinte</label>

										<select class="custom-select form-control" id="contribuinte">

											<option value="1">SIM</option>
											<option value="0">NAO</option>
										</select>
									</div>

									<div class="form-group validated col-sm-3 col-lg-2">
										<label class="col-form-label" id="lbl_ie_rg">Limite de Venda</label>
										<div class="">
											<input type="text" id="limite_venda" class="form-control @if($errors->has('limite_venda')) is-invalid @endif money"  value="0">

										</div>
									</div>

								</div>
								<hr>
								<h5>Endereço de Faturamento</h5>
								<div class="row">
									<div class="form-group validated col-sm-8 col-lg-6">
										<label class="col-form-label">Rua</label>
										<div class="">
											<input id="rua" type="text" class="form-control @if($errors->has('rua')) is-invalid @endif">

										</div>
									</div>

									<div class="form-group validated col-sm-2 col-lg-2">
										<label class="col-form-label">Número</label>
										<div class="">
											<input id="numero2" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif">

										</div>
									</div>

									<div class="form-group validated col-sm-8 col-lg-4">
										<label class="col-form-label">Bairro</label>
										<div class="">
											<input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif">

										</div>
									</div>

									<div class="form-group validated col-sm-8 col-lg-2">
										<label class="col-form-label">CEP</label>
										<div class="">
											<input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif">
										</div>
									</div>

									<div class="form-group validated col-sm-8 col-lg-3">
										<label class="col-form-label">Email</label>
										<div class="">
											<input id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif">

										</div>
									</div>

									@php
									$cidade = App\Models\Cidade::getCidadeCod($config->codMun);
									@endphp
									<div class="form-group validated col-lg-4 col-md-6 col-sm-10">
										<label class="col-form-label">Cidade</label><br>
										<select style="width: 100%" class="form-control select2" id="kt_select2_8">
											@foreach(App\Models\Cidade::all() as $c)
											<option @if($cidade->id == $c->id) selected @endif value="{{$c->id}}">
												{{$c->nome}} ({{$c->uf}})
											</option>
											@endforeach
										</select>

									</div>

									<div class="form-group validated col-sm-8 col-lg-3">
										<label class="col-form-label">Telefone (Opcional)</label>
										<div class="">
											<input id="telefone" type="text" class="form-control @if($errors->has('telefone')) is-invalid @endif">
										</div>
									</div>

									<div class="form-group validated col-sm-8 col-lg-3">
										<label class="col-form-label">Celular (Opcional)</label>
										<div class="">
											<input id="celular" type="text" class="form-control @if($errors->has('celular')) is-invalid @endif">
										</div>
									</div>
								</div>
							</div>
						</div>

					</div>
					<div class="modal-footer">
						<button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
						<button type="button" onclick="salvarCliente()" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal-cartao" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">INFORME OS DADOS DO CARTÃO</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="form-group validated col-sm-3 col-lg-3 col-6">
								<label class="col-form-label">Bandeira</label>
								<select class="custom-select" id="bandeira_cartao">
									<option value="">--</option>
									@foreach(App\Models\VendaCaixa::bandeiras() as $key => $b)
									<option value="{{$key}}">{{$b}}</option>
									@endforeach
								</select>
							</div>
							<div class="form-group validated col-sm-4 col-lg-4 col-6">
								<label class="col-form-label">Código autorização(opcional)</label>
								<input type="text" placeholder="Código autorização" id="cAut_cartao" class="form-control" value="">
							</div>

							<div class="form-group validated col-sm-4 col-lg-5 col-12">
								<label class="col-form-label">CNPJ(opcional)</label>
								<input type="text" placeholder="CNPJ" id="cnpj_cartao" data-mask="00.000.000/0000-00" name="cnpj_cartao" class="form-control" value="">
							</div>
						</div>

					</div>
					<div class="modal-footer">
						<button style="width: 100%" type="button" data-dismiss="modal" class="btn btn-success font-weight-bold">Salvar</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal-pag-outros" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">INFORME A DESCRIÇAO DO TIPO DE PAGAMENTO OUTROS</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="form-group validated col-12">
								<label class="col-form-label">Descrição</label>
								<input type="text" placeholder="Descrição" id="descricao_pag_outros" name="descricao_pag_outros" class="form-control" value="">
							</div>
						</div>

					</div>
					<div class="modal-footer">
						<button style="width: 100%" type="button" data-dismiss="modal" class="btn btn-success font-weight-bold">Salvar</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal-comanda" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-sm" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">INFORME O CÓDIGO DA COMANDA</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="form-group validated col-sm-12 col-lg-12 col-12">
								<label class="col-form-label" id="">Código da comanda</label>
								<input type="text" placeholder="Comanda" id="cod-comanda" name="cod-comanda" class="form-control" value="">
							</div>
						</div>

					</div>
					<div class="modal-footer">
						<button style="width: 100%" type="button" onclick="apontarComanda()" class="btn btn-success font-weight-bold spinner-white spinner-right btn-apontar">APONTAR</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal-cod-barras" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-sm" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">INFORME O CÓDIGO MANUAL</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="form-group validated col-sm-12 col-lg-12 col-12">
								<label class="col-form-label" id="">Código de barras</label>
								<input type="text" placeholder="Código de barras" id="cod-barras2" name="cod-barras2" class="form-control pula" value="">
							</div>
						</div>

					</div>
					<div class="modal-footer">
						<button style="width: 100%" type="button" onclick="apontarCodigoDeBarras()" class="btn btn-success font-weight-bold pula">OK</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal-grade" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-md" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Grade</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body grade-prod">

					</div>
				</div>
			</div>
		</div>


		<div class="modal fade" id="modal-obs" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">OBSERVAÇÃO</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="form-group validated col-sm-12 col-lg-12 col-12">
								<label class="col-form-label" id="">Observação</label>
								<input type="text" placeholder="Observação" id="obs" class="form-control" @if(isset($observacao)) value="{{$observacao}}" @endif>
							</div>
						</div>

					</div>
					<div class="modal-footer">
						<button style="width: 100%" type="button" onclick="apontarObs()" class="btn btn-success font-weight-bold">OK</button>
					</div>
				</div>
			</div>
		</div>


		<script>
			var KTAppSettings = {
				"breakpoints": {
					"sm": 576,
					"md": 768,
					"lg": 992,
					"xl": 1200,
					"xxl": 1400
				},
				"colors": {
					"theme": {
						"base": {
							"white": "#ffffff",
							"primary": "#3699FF",
							"secondary": "#E5EAEE",
							"success": "#1BC5BD",
							"info": "#8950FC",
							"warning": "#FFA800",
							"danger": "#F64E60",
							"light": "#E4E6EF",
							"dark": "#181C32"
						},
						"light": {
							"white": "#ffffff",
							"primary": "#E1F0FF",
							"secondary": "#EBEDF3",
							"success": "#C9F7F5",
							"info": "#EEE5FF",
							"warning": "#FFF4DE",
							"danger": "#FFE2E5",
							"light": "#F3F6F9",
							"dark": "#D6D6E0"
						},
						"inverse": {
							"white": "#ffffff",
							"primary": "#ffffff",
							"secondary": "#3F4254",
							"success": "#ffffff",
							"info": "#ffffff",
							"warning": "#ffffff",
							"danger": "#ffffff",
							"light": "#464E5F",
							"dark": "#ffffff"
						}
					},
					"gray": {
						"gray-100": "#F3F6F9",
						"gray-200": "#EBEDF3",
						"gray-300": "#E4E6EF",
						"gray-400": "#D1D3E0",
						"gray-500": "#B5B5C3",
						"gray-600": "#7E8299",
						"gray-700": "#5E6278",
						"gray-800": "#3F4254",
						"gray-900": "#181C32"
					}
				},
				"font-family": "Poppins"
			};
		</script>

		<!-- end::Global Config -->
		<!--begin::Global Theme Bundle(used by all pages) -->
		<script src="/metronic/js/plugins.bundle.js" type="text/javascript"></script>
		<script src="/metronic/js/prismjs.bundle.js" type="text/javascript"></script>
		<script src="/metronic/js/scripts.bundle.js" type="text/javascript"></script>
		<script src="/metronic/js/wizard.js" type="text/javascript"></script>

		<script src="/metronic/js/fullcalendar.bundle.js" type="text/javascript"></script>
		<script type="text/javascript" src="/js/jquery.mask.min.js"></script>
		<script type="text/javascript" src="/js/mascaras.js"></script>
		<script src="/metronic/js/select2.js" type="text/javascript"></script>
		<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.2.0/sweetalert2.all.min.js"></script> -->
		<script src="/js/sweetalert.min.js"></script>

		<script type="text/javascript">
			function audioSuccess(){
				console.clear()
				@if($alerta_sonoro != "")
				var audio = new Audio('audio/{{$alerta_sonoro}}');
				audio.addEventListener('canplaythrough', function() {
					audio.play();
				});
				@endif
			}
		</script>

		<?php $path = env('PATH_URL') . "/"; ?>
		<script type="text/javascript">
			var casas_decimais = 2;
			var casas_decimais_qtd = 2;
			casas_decimais = {{$casasDecimais}}
			casas_decimais_qtd = {{$casasDecimaisQtd}}
			let prot = window.location.protocol;
			let host = window.location.host;
			const path = prot + "//" + host + "/";
		</script>

		<script type="text/javascript" src="/js/mousetrap.js"></script>
		<script type="text/javascript" src="/js/frenteCaixa.js"></script>

		<script src="/js/bootstrap-datepicker.pt-BR.min.js"></script>

		<script>

			jQuery(document).ready(function() {
				KTSelect2.init();
				$('.select2-selection__arrow').addClass('select2-selection__arroww')

				$('.select2-selection__arrow').removeClass('select2-selection__arrow')
				var KTBootstrapDatepicker = function() {

					var arrows;
					if (KTUtil.isRTL()) {
						arrows = {
							leftArrow: '<i class="la la-angle-right"></i>',
							rightArrow: '<i class="la la-angle-left"></i>'
						}
					} else {
						arrows = {
							leftArrow: '<i class="la la-angle-left"></i>',
							rightArrow: '<i class="la la-angle-right"></i>'
						}
					}

					// Private functions
					var demos = function() {

						// minimum setup
						$('#kt_datepicker_1').datepicker({
							rtl: KTUtil.isRTL(),
							todayHighlight: true,
							orientation: "bottom left",
							templates: arrows
						});

						// minimum setup for modal demo
						$('#kt_datepicker_1_modal').datepicker({
							rtl: KTUtil.isRTL(),
							todayHighlight: true,
							orientation: "bottom left",
							templates: arrows
						});

						// input group layout
						$('#kt_datepicker_2').datepicker({
							rtl: KTUtil.isRTL(),
							todayHighlight: true,
							orientation: "bottom left",
							templates: arrows
						});

						// input group layout for modal demo
						$('#kt_datepicker_2_modal').datepicker({
							rtl: KTUtil.isRTL(),
							todayHighlight: true,

							orientation: "bottom left",
							templates: arrows
						});

						// enable clear button
						$('.kt_datepicker_3').datepicker({
							rtl: KTUtil.isRTL(),
							todayBtn: "linked",
							clearBtn: false,
							format: 'dd/mm/yyyy',
							todayHighlight: false,
							templates: arrows,
							language: "pt-BR"
						});

						// enable clear button for modal demo
						$('#kt_datepicker_3_modal').datepicker({
							rtl: KTUtil.isRTL(),
							todayBtn: "linked",
							clearBtn: false,
							format: 'dd/mm/yyyy',
							todayHighlight: false,
							templates: arrows
						});

						// orientation
						$('#kt_datepicker_4_1').datepicker({
							rtl: KTUtil.isRTL(),
							orientation: "top left",
							todayHighlight: true,
							templates: arrows
						});

						$('#kt_datepicker_4_2').datepicker({
							rtl: KTUtil.isRTL(),
							orientation: "top right",
							todayHighlight: true,
							templates: arrows
						});

						$('#kt_datepicker_4_3').datepicker({
							rtl: KTUtil.isRTL(),
							orientation: "bottom left",
							todayHighlight: true,
							templates: arrows
						});


					}

					return {

						init: function() {
							demos();
						}
					};
				}();

				KTBootstrapDatepicker.init({
					format: 'dd/mm/yyyy'
				});

			});

			setInterval(() => {
				let hora = formatar(new Date())
				$('#timer').html(hora)
			}, 1000)

			const formatar = (data) => {
				const hora = data.getHours() < 10 ? '0'+data.getHours() : data.getHours();
				const min = data.getMinutes() < 10 ? '0'+data.getMinutes() : data.getMinutes();
				const seg = data.getSeconds() < 10 ? '0'+data.getSeconds() : data.getSeconds();

				return `${hora}:${min}:${seg}`;
			};

		</script>

		@if($deliveryConfig && env("DELIVERY") == 1)
		<script type="text/javascript" src="/js/pedido_delivery.js"></script>
		<script type="text/javascript" src="/js/pedido_mesa.js"></script>
		@endif

	</body>


	</html>
