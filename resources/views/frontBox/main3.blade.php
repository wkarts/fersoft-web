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
			margin-top: 10px;

		}
		@media only screen and (max-width: 1000px) {
			#div-categorias{
				{{-- display: none; --}}
			}
			#div-categorias > .card-body{
				height: inherit;
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

		.search-prod{
			position: absolute;
			top: 0;
			margin-top: 40px;
			left: 10;
			width: 100%;
			max-height: 200px;
			overflow: auto;
			z-index: 9999;
			border: 1px solid #eeeeee;
			border-radius: 4px;
			background-color: #fff;
			box-shadow: 0px 1px 6px 1px rgba(0, 0, 0, 0.4);
		}

		.search-prod label:hover{
			cursor: pointer;
		}

		.search-prod label{
			margin-left: 10px;
			width: 100%;
			margin-top: 7px;
			font-size: 14px;
		}

		.lbl-prod{
			color: #000 !important;
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
	<style type="text/css">
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

		.select2-results__option {
			font-size: 20px !important;
		}
	</style>
</head>


<!-- end::Head -->

<!-- begin::Body -->

<body id="kt_body" class="header-fixed header-mobile-fixed subheader-enabled subheader-fixed aside-enabled aside-fixed aside-minimize-hoverable page-loading">

	<input type="hidden" id="categorias" value="{{json_encode($categorias)}}" name="">
	<input type="hidden" id="clientes" value="{{json_encode($clientes)}}" name="">
	<input type="hidden" id="agendamento_id" value="{{$agendamento_id ?? 0}}" name="">

	<input type="hidden" id="_token" value="{{ csrf_token() }}">

	<input type="hidden" id="valor_entrega" @if(isset($valor_entrega)) value="{{$valor_entrega}}" @else value='0' @endif>
	<input type="hidden" id="exibe_modal_cartoes" @if(isset($atalhos)) value="{{$atalhos->exibe_modal_cartoes}}" @else value='0' @endif>

	@if(isset($itens))
	<input type="hidden" id="itens_pedido" value="{{json_encode($itens)}}">
	<input type="hidden" id="valor_total" @if(isset($valor_total)) value="{{$valor_total}}" @else value='0' @endif>
	<input type="hidden" id="delivery_id" @if(isset($delivery_id)) value="{{$delivery_id}}" @else value='0' @endif>
	<input type="hidden" id="bairro" @if(isset($bairro)) value="{{$bairro}}" @else value='0' @endif>

	<input type="hidden" id="codigo_comanda_hidden" @if(isset($cod_comanda)) value="{{$cod_comanda}}" @else value='0' @endif name="">
	<input type="hidden" id="pedido_ifood" @if(isset($pedido_ifood)) value="{{$pedido_ifood}}" @else value='0' @endif name="">
	@endif

	@if(isset($venda))
	<input type="hidden" id="venda" value="{{$venda}}">
	@endif

	<input type="hidden" id="PDV_VALOR_RECEBIDO" @if(isset($atalhos)) value="{{$atalhos->valor_recebido_automatico}}" @else value='0' @endif>
	<input type="hidden" id="ATALHOS" value="{{ json_encode($atalhos) }}">

	<input type="hidden" value="{{$usuario->permite_desconto}}" id="permite_desconto">
	<input type="hidden" value="{{$usuario->caixa_livre}}" id="caixa_livre">
	<input type="hidden" value="{{$config->percentual_max_desconto}}" id="percentual_max_desconto">
	@if(isset($config))
	<input type="hidden" id="pass" value="{{ $config->senha_remover }}">
	@endif

	@if(isset($config))
	<input type="hidden" id="senha_alterar_preco" value="{{ ($config->senha_alterar_preco)? 'true': ''; }}">
	<input type="hidden" id="parcelamento_maximo" value="{{ $config->parcelamento_maximo }}">
	@endif

	@if(isset($os_id))
	<input type="hidden" name="os_id" id="os_id" value="{{ $os_id }}">
	@endif

	@if(isset($dfe))
	<input type="hidden" name="dfe_id" id="dfe_id" value="{{ $dfe->id }}">
	@endif

	@if(isset($vendedores))
	<input type="hidden" id="vendedores" value="{{ json_encode($vendedores) }}">
	<input type="hidden" id="acessores" value="{{ json_encode($acessores) }}">
	@endif

	@php
	$caixa_tipos_pagamento = App\Models\ConfigCaixa::getTiposPagamento();
	$caixa_tipo_pagamento_padrao = App\Models\ConfigCaixa::getTipoPagamentoPadrao();

	@endphp

	@isset($filial)
        <input type="hidden" id="filial" name="filial" value="{{$filial == null ? null : $filial->id}}">
	@else
	    <input type="hidden" id="filial" name="filial" value="">
	@endif

	<div class="card card-custom gutter-b example example-compact">
		<div class="col-lg-12">
			<div class="">

				<div class="row mt-5">

					<div class="col-lg-1 col-md-12">

						<div style="display: flex;align-items: center;height: 100%;">

							<h4 class="mb-0 ml-6">
								<strong id="timer">00:00:00</strong>
							</h4>
						</div>
					</div>
					<div class="col-lg-9 col-md-7 mt-2 mb-2" style="display: flex;flex-wrap: wrap;align-items: center;gap: 6px;">
						@if(isset($is_troca))
						<button class="btn text-dark mx-1" style="min-width: 150px;border-radius: 0;border: 1px solid #8950FC;">
							REALIZANDO TROCA
						</button>
						@php
						$atalhos->exibe_produtos = 0;
						@endphp
						@elseif (isset($venda) && (json_decode($venda)->prevenda_nivel == 2))
						<button class="btn text-dark mx-1" style="min-width: 150px;border-radius: 0;border: 1px solid #8950FC;">
							FINALIZANDO PRÉ-VENDA
						</button>

						<a href="/frenteCaixa" class="btn text-dark mx-1" style="min-width: 150px;border-radius: 0;border: 1px solid #EE2D41;">
							ENCERRAR
						</a>
						@elseif (isset($venda) && !isset($editPrevenda))
						<button class="btn text-dark mx-1" style="min-width: 150px;border-radius: 0;border: 1px solid #8950FC;">
							EDITANDO VENDA
						</button>
						@endif

						@isset($editPrevenda)
						<button class="btn text-dark mx-1" style="min-width: 150px;border-radius: 0;border: 1px solid #8950FC;">
							EDITANDO PRÉ-VENDA
						</button>
						@endif

						@if(isset($venda))
						<div class="btn btn-dark mx-1" style="background: #000;min-width: 150px;border-radius: 0;">
							<i class="las la-user-tie"></i>
							@php
							$vendedor_nome = 'Não Informado';
							foreach($vendedores as $v)
							if($v->id == json_decode($venda)->usuario_id){
								$vendedor_nome = $v->funcionario->nome;
							}
							@endphp

							{{$vendedor_nome}}

						</div>

						<input type="hidden" value="{{json_decode($venda)->usuario_id}}" id="vendedor_pre_venda">

						@else
						<div class="btn btn-dark mx-1" style="background: #000;min-width: 150px;border-radius: 0;"  data-toggle="modal" data-target="#selecionar_vendedor_modal" aria-haspopup="true" aria-expanded="true">
							<i class="las la-user-tie"></i> <span id="btn_informar_vendedor"> Informar Vendedor</span>
						</div>

						@if(sizeof($acessores) > 0)
						<div class="btn btn-warning mx-1" style="min-width: 150px;border-radius: 0;"  data-toggle="modal" data-target="#selecionar_assessor_modal" aria-haspopup="true" aria-expanded="true">
							<i class="las la-user"></i> <span id="btn_informar_assessor"> Informar Assessor</span>
						</div>
						@endif

						@if(isset($preVenda))
						<div class="btn btn-info mx-1" id="open_prevenda_lista_edit" style="min-width: 150px;border-radius: 0;" data-toggle="modal" data-target="#lista_prevenda_nivel2">
							<i class="la la-list"></i> Lista de Pré-vendas
						</div>

						@endif
						@endif

						@if(isset($preVenda))
						<a href="/frenteCaixa/prevenda" class="btn btn-primary mx-1" style="min-width: 150px;border-radius: 0;">
							<i class="la la-shopping-basket"></i> Nova Pré-venda
						</a>

						<a href="#" class="btn btn-secondary mx-1"  style="min-width: 150px;border-radius: 0;" data-toggle="modal" data-target="#prevenda_rascunhos_lista" aria-haspopup="true" aria-expanded="true">
							<i class="las la-th-list"></i> Lista de rascunhos
						</a>

						<a href="/frenteCaixa" class="btn btn-success mx-1" target="_blank" style="min-width: 150px;border-radius: 0;" aria-haspopup="true" aria-expanded="true">
							<i class="las la-barcode"></i> Ir para o PDV
						</a>
						@else
						@if(!isset($venda))
						<div class="btn btn-success mx-1" id="open_prevenda_lista" style="min-width: 150px;border-radius: 0;" data-toggle="modal" data-target="#lista_prevenda_nivel2">
							<i class="la la-shopping-basket"></i> Lista de Pré-vendas
						</div>
						@endif

						<a href="/frenteCaixa/list" class="btn btn-dark mx-1" style="background:#353269;min-width: 150px;border-radius: 0;">
							<i class="las la-th-list"></i> Lista de Vendas
						</a>

						@if(!isset($venda))

						<a class="btn btn-primary mx-1" style="min-width: 150px;border-radius: 0;" href="/frenteCaixa/troca">
							<i class="las la-sync"></i> Lista de Trocas
						</a>

						<a class="btn btn-dark mx-1" style="min-width: 150px;border-radius: 0;" data-toggle="modal" data-target="#modal-consignadas">
							<i class="las la-user-tag"></i> Lista de Consinado
						</a>
						@endif
						@endif

						<h4>
							<i class="la la-user text-info" style="font-size: 23px; margin-left: 50px;"></i>
							{{session('user_logged')['nome']}}
						</h4>

					</div>
					<div class="col-lg-2 col-md-4">

						<div class="dropdown dropdown-inline show" data-toggle="tooltip" title="" data-placement="left" data-original-title="Ações">

							<a @if(isset($preVenda) && $preVenda) href="/frenteCaixa/list" @else href="/frenteCaixa/list" @endif class="btn btn-light-danger" style="border-radius: 0;">
								<i class="la la-angle-double-left"></i>

								Sair
								<!--end::Svg Icon-->
							</a>

							@if(!isset($preVenda))
							<a href="#" class="btn btn-light-info" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" style="border-radius: 0;">
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
										<a href="/frenteCaixa/devolucao" class="navi-link">
											<span class="navi-text">
												<span class="label label-xl label-inline label-light-danger">Devolução</span>
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
										<a data-toggle="modal" href="#!" data-target="#modal-comanda" class="navi-link">
											<span class="navi-text">
												<span class="label label-xl label-inline label-light-success">Apontar Comanda</span>
											</span>
										</a>
									</li>

									<li class="navi-item">
										<a data-toggle="modal" onclick="getMesas()" href="#!" data-target="#modal-mesas" class="navi-link">
											<span class="navi-text">
												<span class="label label-xl label-inline label-info">Mesas</span>
											</span>
										</a>
									</li>

									<li class="navi-item">
										<a href="/caixa" class="navi-link">
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
										<a data-toggle="modal" href="#!" data-toggle="modal" data-target="#modal3" onclick="fluxoDiario()" class="navi-link">
											<span class="navi-text">
												<span class="label label-xl label-inline label-light-primary">
													Fluxo Diário
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
							@endif
						</div>
					</div>
				</div>
			</div>


			<hr>
			<div class="row">

				<div class="col-sm-12 col-lg-9 col-md-12 col-12">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<div class="row" style="margin-top: 10px;width:100%">
								<div class="col-12">
									<div class="pr-2" id="focus-codigo">
										<button class="btn p-1 pl-2 barcode-btnbarcode-btn">
											<li class="la la-barcode" style="font-size: 2rem;color: #000;"></li>
										</button>

										<input class="mousetrap" type="" autofocus id="codBarras" name="">

										<span id="mousetrapTitle"><span>CLIQUE AQUI PARA ATIVAR O LEITOR</span> <i class="las la-sort-down" style="margin-top: 4px;"></i></span>
									</div>
								</div>
							</div>

							<div class="row align-items-center" style="margin-top: 10px;width:100%">
								<div class="form-group validated col-sm-6 col-lg-6 col-12 col-sm-12 add-prod">
									<label>Produto</label>
									<div class="input-group">

										<div class="input-group-prepend">
											<span class="input-group-text" id="focus-codigo">
												<li class="la la-search"></li>
											</span>
										</div>

										<!-- <input placeholder="Digite para buscar o produto" type="search" id="produto-search" class="form-control">
										<div class="search-prod" style="display: none">
										</div> -->

										<select class="form-control select2 produto-search select-search" style="width: 90%" id="kt_select2_1" name="produto-search">
											<option value="">Digite para buscar o produto</option>
										</select>

									</div>
								</div>

								<div class="form-group validated col-sm-2 col-lg-2 col-5 col-sm-5 add-prod">
									<div class="">
										<label>Valor unitário</label>
										<input @if(!$usuario->permite_desconto) disabled @endif id="valor_item" placeholder="Valor unitário" type="text" class="form-control" name="valor" value="{{number_format(0, $casasDecimais, ',', '.')}}">
									</div>
								</div>

								<div class="form-group validated col-sm-2 col-lg-2 col-5 col-sm-5 add-prod">
									<div class="">
										<label>Quantidade</label>
										<input id="quantidade" placeholder="Quantidade" type="text" class="form-control" name="quantidade" value="1">

									</div>
								</div>

								<div class="form-group validated col-sm-2 col-lg-2 col-6 col-sm-6 add-prod">
									<button style="margin-top: 18px;" id="adicionar-item" type="submit" class="btn btn-success">Adicionar</button>
								</div>
							</div>
						</div>
						<div class="card-body" style="height: 445px;">

							<div class="col-xl-12">
								<div class="row">
									<div class="col-xl-12">
										<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded" >

											<table class="datatable-table" style="max-width: 100%; max-height: 420px; overflow: scroll;">
												<thead class="datatable-head">
													<tr class="datatable-row" style="left: 0px;">
														<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 50px;"></span></th>
														<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 50px;">ID</span></th>
														<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">PRODUTO</span></th>
														<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">QUANTIDADE</span></th>
														<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">VALOR</span></th>
														<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">SUBTOTAL</span></th>

														@if (isset($is_troca))
														<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Remover</span></th>
														@else
														<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 50px;">#</span></th>
														@endif
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

								<div class="col-sm-3 col-lg-3 col-6" @if(isset($is_troca)) style="display:none" @endif>
									<label>Desconto: R$ <strong id="valor_desconto">0,00</strong></label>
									<button @if(!$usuario->permite_desconto) disabled @endif onclick="setaDesconto()" style="margin-left: 4px; margin-top: -10px;" class="btn btn-link-primary">
										<i class="la la-edit"></i>
									</button>
								</div>
								<div class="col-sm-3 col-lg-3 col-6" @if(isset($is_troca)) style="display:none" @endif>

									<label>Acrescimo: R$ <strong id="valor_acrescimo">0,00</strong></label>
									<button onclick="setaAcresicmo()" style="margin-left: 4px; margin-top: -10px;" class="btn btn-link-primary">
										<i class="la la-edit"></i>
									</button>
								</div>
								<div class="col-sm-2 col-lg-2 col-3" @if(isset($is_troca)) style="display:none" @endif>
									<label>Lista de Preços</label>
								</div>

								<div class="col-sm-4 col-lg-4 col-6" style="margin-top: -8px;  @if(isset($is_troca)) display:none; @endif">
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
							@isset($valor_entrega)
							<p class="text-info">valor entrega: <strong>R${{ moeda($valor_entrega) }}</strong></p>
							@endif
							@isset($abertura)
							@if(empresaComFilial() && $abertura)
							<div class="row align-items-center">
								<div class="col-lg-12">
									<div style="display: flex;align-items: center;height: 100%;">
										<h4 class="mb-0">
											Local: <strong class="text-danger">{{$filial != null ? $filial->descricao : 'Matriz'}}</strong>
										</h4>

										@if($certificado != null)
										<button class="btn btn-sm btn-dark float-right spinner-white spinner-right btn-consulta-status ml-3">
											Consultar Status Sefaz
										</button>
										@endif
									</div>


								</div>
							</div>
							@endif
							@endif
						</div>
					</div>

					<!-- Para produtos footer -->
					@if($atalhos && $atalhos->exibe_produtos)
					<div class="card card-custom gutter-b example example-compact" style="margin-top: -20px; height: auto;">
						<div class="card-body">
							<div class="row align-items-center">
								<div class="form-group col-xl-2 col-lg-4">
									<button title="Informar Cliente" id="click-client" class="btn btn-danger"  @if(isset($is_troca)) style="display:none" @endif>
										<i class="la la-user"></i>
									</button>
									<button title="Pagamento Múltiplo" id="click-multi" class="btn btn-info ml-1" @if(isset($is_troca) || isset($preVenda)) style="display:none;" @endif>
										<i class="la la-list"></i>
									</button>
									<button title="Observação" onclick="setaObservacao()" class="btn btn-primary ml-1">
										<i class="la la-marker"></i>
									</button>
								</div>

								<div class="form-group validated col-xl-2 col-lg-4">

									<select class="custom-select form-control" id="tipo-pagamento" name="tipo-pagamento" @if(isset($is_troca) || isset($preVenda)) style="display:none" @endif>
										<option value="--">Selecione o tipo de pagamento</option>
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

								<div class="form-group validated col-lg-2">
									<input type="text" placeholder="Valor recebido" id="valor_recebido" name="valor_recebido" class="form-control money" value="" @if(isset($is_troca) || isset($preVenda)) style="display:none" @endif>
								</div>

								<div class="form-group validated col-lg-2 col-md-12 col-6">
									<h4 style="font-size: 1.5rem">
										TOTAL R$
										<span id="total-venda" style="color:#274f51;font-size: 2.5rem;font-weight:400;">0</span>

									</h4>
									<span style="font-size: 15px; padding: 20px; width: 100% !important;
									@if(isset($preVenda)) display:none; @endif margin-top: 1px;" class="label label-xl label-inline label-light-info">Troco
									<strong style="margin-left: 5px;" id="valor-troco"> R$ 0,00</strong>
								</span>

							</div>

							<div class="form-group col-lg-4 col-md-6">
								@if(isset($is_troca))
								<button id="finalizar-troca" class="btn btn-success btn-lg">
									<i class="la la-check"></i>
									Finalizar Troca
								</button>
								@endif

								@if(isset($preVenda))

								@isset($editPrevenda)
								<button id="edit-prevenda" class="btn btn-info btn-lg">
									<i class="la la-edit"></i>
									Salvar Pré-venda
								</button>
								@endif

								<button id="enviar-prevenda" class="btn btn-success btn-lg">
									<i class="la la-paper-plane"></i>
									Enviar para o caixa
								</button>
								<button id="rascunho-prevenda" class="btn btn-secondary btn-lg">
									<i class="la la-sticky-note"></i>
									Manter em rascunhos
								</button>

								@else
								<div class="row">
									<div class="col-12">
										<button id="finalizar-venda" class="btn btn-success btn-lg spinner-white spinner-right w-100" disabled @if(isset($is_troca)) style="display:none;" @endif>
											<i class="la la-check"></i>
											Finalizar Venda
										</button>
									</div>
									<div class="col-6">
										<button id="finalizar-rascunho" onclick="salvarRascuho()" class="btn btn-warning btn-lg w-100 mt-1" disabled @if(isset($is_troca)) style="display:none" @endif>
											<i class="la la-edit"></i>
											Salvar <br>Rascunho
										</button>
									</div>
									<div class="col-6">
										<button id="finalizar-consignado" onclick="salvarConsignado()" class="btn btn-dark btn-lg mt-1 w-100" disabled @if(isset($is_troca)) style="display:none" @endif>
											<i class="la la-user"></i>
											Salvar Consignado
										</button>
									</div>
								</div>
								@endif
							</div>
						</div>
					</div>
				</div>
				@endif
			</div>

			<div class="col-sm-3 col-lg-3 col-md-12 col-12 @if($atalhos && $atalhos->exibe_produtos) d-none @endif" id="div-categorias">
				<div class="card card-custom gutter-b example example-compact">
					<div class="card-header">
						<div class="form-group validated col-12" style="margin-top: 15px;">
							<button title="Informar Cliente" id="click-client" class="btn btn-danger btn-lg"  @if(isset($is_troca)) style="display:none" @endif>
								<i class="la la-user"></i>
							</button>
							<button title="Pagamento Múltiplo" id="click-multi" class="btn btn-info btn-lg" @if(isset($is_troca) || isset($preVenda)) style="display:none;" @endif>
								<i class="la la-list"></i>
							</button>
							<button title="Observação" onclick="setaObservacao()" class="btn btn-primary btn-lg">
								<i class="la la-marker"></i>
							</button>

							@if(isset($is_troca) && json_decode($venda)->cliente)
							<div class="row">
								<div class="col-12 mt-5">
									<h4><i class="la la-user text-dark"></i> {{json_decode($venda)->cliente->razao_social}}</h4>
								</div>
							</div>
							@endif
						</div>
					</div>

					<div class="card-body pt-3 px-2" style="overflow-y: auto;">
						<div class="rounded py-5 mb-10 px-1" style="background:#F3F6F9;">
							<div class="col-12 px-10">
								<span style="color:#868686;font-size: 1.2rem;font-weight:800;">TOTAL R$:</span>
								<br/>
								<span id="total-venda" style="color:#274f51;font-size: 3.5rem;font-weight:400;">0</span>
								@isset($is_troca)
								<br>
								<span>Total restante: <strong class="text-danger" id="total-restante">R$ 0,00</strong></span>
								<br>
								<span style="font-size: 20px;">Valor da venda original: <strong class="text-info" id="total-original">R$ 0,00</strong></span>
								@endif
							</div>
						</div>

						<div class="form-group validated col-12 px-10" style="margin-top: 5px;">
							<div class="">
								<select class="custom-select form-control" id="tipo-pagamento" name="tipo-pagamento" @if(isset($is_troca) || isset($preVenda)) style="display:none" @endif>
									<option value="--">Selecione o tipo de pagamento</option>
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
						</div>
						<div class="form-group validated col-sm-8 px-10" style="margin-top: 15px;">
							<div class="">
								<input type="text" placeholder="Valor recebido" id="valor_recebido" name="valor_recebido" class="form-control money" value="" @if(isset($is_troca) || isset($preVenda)) style="display:none" @endif>
							</div>
						</div>
						<div class="form-group validated col-12 px-10">
							<span style="font-size: 20px; padding: 25px; width: 100% @if(isset($preVenda)) ;display:none; @endif" class="label label-xl label-inline label-light-info">Troco
								<strong style="margin-left: 5px;" id="valor-troco"> R$ 0,00</strong>
							</span>
						</div>

						<div class="form-group validated col-12 px-10">
							@if(isset($is_troca))
							<button id="finalizar-troca" class="btn btn-success btn-lg w-100">
								<i class="la la-check"></i>
								Finalizar Troca
							</button>
							@endif

							@if(isset($preVenda))

							@isset($editPrevenda)
							<button id="edit-prevenda" class="btn btn-info btn-lg w-100 mb-5">
								<i class="la la-edit"></i>
								Salvar Pré-venda
							</button>
							@endif

							<button id="enviar-prevenda" class="btn btn-success btn-lg w-100">
								<i class="la la-paper-plane"></i>
								Enviar para o caixa
							</button>
							<button id="rascunho-prevenda" class="btn btn-secondary btn-lg w-100 mt-5">
								<i class="la la-sticky-note"></i>
								Manter em rascunhos
							</button>

							@else
							<button id="finalizar-venda" class="btn btn-success btn-lg w-100 spinner-white spinner-right" disabled @if(isset($is_troca)) style="display:none;" @endif>
								<i class="la la-check"></i>
								Finalizar
								<!-- <strong id="total-venda">R$ 0,00</strong> -->
							</button>
							<button id="finalizar-rascunho" onclick="salvarRascuho()" class="btn btn-warning btn-lg mt-1 w-100" disabled  @if(isset($is_troca)) style="display:none" @endif>
								<i class="la la-edit"></i>
								Salvar rascunho
							</button>
							<button id="finalizar-consignado" onclick="salvarConsignado()" class="btn btn-dark btn-lg mt-1 w-100" disabled @if(isset($is_troca)) style="display:none" @endif>
								<i class="la la-user-tag"></i>
								Salvar Consignado
							</button>
							@endif
						</div>
					</div>
				</div>
			</div>

			<!-- para produtos -->
			<div class="col-sm-3 col-lg-3 col-md-12 col-12 @if($atalhos && !$atalhos->exibe_produtos) d-none @endif" id="div-categorias">
				<div class="card card-custom gutter-b example example-compact">
					<div class="card-header" style="">
						<div class="row" style="height: 72px; overflow-x: auto; width: auto; white-space: nowrap">
							<div class="form-group validated col-sm-12 col-lg-12 col-12 col-sm-12" style="margin-top: 10px;">
								<a href="#!" id="cat_todos" onclick="filtroCategoria('todos')" style="height: 40px; min-width: 80px;" class="label label-xl label-inline label-light-muted ativo">Todos</a>
								@foreach($categorias as $c)
								<a href="#!" id="cat_{{$c->id}}" onclick="filtroCategoria('{{$c->id}}')" style="height: 40px; min-width: 80px;" class="label label-xl label-inline desativo">{{$c->nome}}</a>
								@endforeach

							</div>

						</div>
					</div>

					<div class="card-body" style="height: 747px; overflow-y: auto;">

						<div class="form-group validated col-sm-12 col-lg-12 col-12 col-sm-12" style="margin-top: -20px;">
							<div class="input-icon">
								<input style="margin-top: 8px;" placeholder="Pesquisar produto" id="pesquisa-produto-lateral" type="" class="form-control" name="">
								<span>
									<i class="fa fa-search"></i>
								</span>
							</div>

						</div>

						<div class="row" id="prods" style="visibility: hidden">
							@foreach($produtosMaisVendidos as $p)
							<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6" id="atalho_add" onclick="adicionarProdutoRapido2('{{$p->id}}')">
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

										@if($p->gerenciar_estoque == 1 && $filial != null)
										<h6 style="font-size: 10px; margin-right: -15px;" class="text-info" class="kt-widget__label">
											Estoque: {{$p->estoquePorLocalPavaVenda($filial->id)}}
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

						{!! __view_locais_select_pdv() !!}

						<div class="form-group validated col-sm-12 col-lg-12">
							<label class="col-form-label" id="">Valor</label>
							<div class="">
								<input type="text" id="valor" name="valor" class="form-control money" value="">
							</div>
						</div>

						@if(is_array($contasEmpresa) && sizeof($contasEmpresa) > 0)
						<div class="form-group validated col-12">
							<label>Conta</label>
							<select required id="conta_id" name="conta_id" class="select2-custom custom-select" style="width: 100%">
								<option value=""></option>
								@foreach($contasEmpresa as $c)
								<option value="{{ $c->id }}">
									{{ $c->nome }}
								</option>
								@endforeach
							</select>
						</div>
						@endif

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

	<div class="modal fade" id="modal-barcode" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
		<div class="modal-dialog modal-sm" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Digite o código de barras</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						x
					</button>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="form-group validated col-12">
							<label class="col-form-label" id="">Código de barras</label>
							<div class="">
								<input type="text" id="barcode" name="barcode" class="form-control" value="">
							</div>
						</div>
					</div>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="button" onclick="buscarProdutoCodigoBarras()" class="btn btn-light-success font-weight-bold btn-barcode-buscar">Buscar</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="modal-pag-mult" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
		<div class="modal-dialog modal-xl" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">PAGAMENTO MULTIPLO <strong class="text-info" id="v-multi"></strong></h5>

					<button onclick="setaParcelas()" type="button" class="close" data-dismiss="modal" aria-label="Close">
						x
					</button>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-12">
							<h6>Adicione as linhas de pagamento</h6>
						</div>

						<div class="row">
							<button data-toggle="modal" onclick="renderizarPagamento()" id="btn-modal-pagamentos"data-target="#modal-pagamentos" type="button" style="margin-top: 20px;" class="btn btn-light-info font-weight-bold disabled col-12 ml-4">
								<i class="la la-list"></i> Adicionar pagamentos
							</button>
						</div>
						<div class="row mt-2">
							<div class="col-12" style="overflow-x: hidden;overflow-y: auto; max-height: 300px;">
								<div class="row">

									<table class="table table-dynamic">
										<thead>
											<tr>
												<th>Tipo pagamento</th>
												<th>Valor</th>
												<th>Observação</th>
												<th>Entrada</th>
												<th>Vencimento</th>
												<th></th>
											</tr>
										</thead>
										<tbody>
											<tr class="dynamic-form">
												<td>
													<select class="custom-select form-control tipo_pagamento inp-pag">
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
												</td>
												<td>
													<input type="text" placeholder="Valor" class="form-control money valor_pagamento inp-pag" value="">
												</td>
												<td>
													<input type="text" placeholder="Observação" class="form-control observacao_pagamento inp-pag ignore">
												</td>
												<td>
													<select class="custom-select entrada_pagamento inp-pag">
														<option value="0">Não</option>
														<option value="1">Sim</option>
													</select>
												</td>
												<td>
													<input value="{{ date('Y-m-d') }}" type="date" placeholder="Vencimento" class="form-control vencimento_pagamento inp-pag">
												</td>
												<td>
													<button type="button" class="btn btn-sm btn-danger btn-line-delete">
														<i class="la la-trash"></i>
													</button>
												</td>
											</tr>
										</tbody>
									</table>
								</div>
								<div class="row">
									<button class="btn btn-success ml-3 btn-clone-tbl">
										<i class="la la-plus"></i> Adicionar linha
									</button>
								</div>
							</div>

						</div>
					</div>
					<hr>

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
					<button onclick="setaParcelas()" type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button onclick="setaParcelas()" type="button" id="btn-ok-multi" class="btn btn-light-success font-weight-bold" disabled>OK</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="modal-pagamentos" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
		<div class="modal-dialog modal-md" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">PAGAMENTOS</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						x
					</button>
				</div>
				<div class="modal-body">
					<div class="row">

						<div class="form-group validated col-sm-4 col-lg-4">
							<label class="col-form-label" id="">Intervalo (dias)</label>
							<div class="">
								<input type="text" id="intervalo" id="intervalo" class="form-control" value="30">
							</div>
						</div>

						<div class="form-group validated col-sm-6 col-lg-6">
							<label class="col-form-label" id="">Quantidade de parcelas</label>
							<div class="">
								<select class="custom-select form-control" id="qtd_parcelas">

								</select>
							</div>
						</div>
					</div>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="submit" id="gerar-pagamentos" class="btn btn-light-info font-weight-bold">Gerar</button>

				</div>

			</div>
		</div>
	</div>

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
						<div class="form-group validated col-sm-4 col-lg-6 col-12 @if($certificado == null) disabled @endif @if($atalhos && !$atalhos->botao_nao_fiscal) col-lg-6 @endif">
							<button @if($certificado == null) disabled @endif id="btn_verifica_cliente" class="btn btn-success" onclick="verificaCliente()" style="height: 50px; width: 100%">

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

						@if($atalhos && $atalhos->botao_nao_fiscal)
						<div class="form-group validated col-sm-4 col-lg-6 col-12">
							<button class="btn btn-info" id="btn_nao_fiscal" onclick="finalizarVenda('nao_fiscal')" style="height: 50px; width: 100%">
								FINALIZAR VENDA
								@if($atalhos != null && $atalhos->finalizar_nao_fiscal != "")
								<br>
								<b class="text-white">{{$atalhos->finalizar_nao_fiscal}}</b>
								@endif
							</button>
						</div>
						@else
						<button class="btn btn-info d-none" id="btn_nao_fiscal" onclick="finalizarVenda('nao_fiscal')"></button>
						@endif

							<!-- <div class="form-group validated col-sm-4 col-lg-4 col-12 @if($atalhos && !$atalhos->botao_nao_fiscal) col-lg-6 @endif">
								<button class="btn btn-warning disabled" id="conta_credito-btn" onclick="finalizarVenda('credito')" style="height: 50px; width: 100%">
									CONTA CRÉDITO
								</button>
							</div> -->
						</div>


					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="modal-emitir-cupom-troca" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">EMITIR CUPOM</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="form-group validated col-md-6 col-12 @if($certificado == null) disabled @endif">
								<button @if($certificado == null) disabled @endif id="btn-finalizarTrocaFiscal" class="btn btn-success" onclick="finalizarTrocaFiscal()" style="height: 50px; width: 100%">

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

							<div class="form-group validated col-md-6 col-12">
								<button class="btn btn-info" id="btn_nao_fiscal" onclick="finalizarVenda('nao_fiscal')" style="height: 50px; width: 100%">
									FINALIZAR VENDA
									@if($atalhos != null && $atalhos->finalizar_nao_fiscal != "")
									<br>
									<b class="text-white">{{$atalhos->finalizar_nao_fiscal}}</b>
									@endif
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

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
								<select class="form-control select2" style="width: 100%" id="kt_select2_3" name="cliente">
									<option value="null">Selecione o cliente</option>
									@foreach($clientes as $c)
									<option value="{{$c->id}}">{{$c->id}} - {{$c->razao_social}}</option>
									@endforeach
								</select>
							</div>

							<h5 class="col-12">Valor de Cashback disponível: <strong class="valor_cashback text-success">0</strong></h5>

							<p class="info_cash_back col-12 text-danger"></p>

							<div class="form-group validated col-lg-4 col-12">
								<label class="col-form-label">Valor de CashBack</label>
								<input type="text" id="inp-valor_cashback" class="form-control money" value="">
							</div>


							<div class="form-group validated col-lg-4">
								<label class="col-form-label">Não permitir crédito</label>

								<span class="switch switch-outline switch-danger">
									<label>
										<input value="true" type="checkbox" id="inp-nao_permitir_credito">
										<span></span>
									</label>
								</span>
							</div>
						</div>

					</div>
					<div class="modal-footer">
						<button type="button" onclick="novoClienteModal()" class="btn btn-light-primary mr-auto">Adicionar novo cliente</button>
						<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
						<button type="button" onclick="selecionarCliente()" class="btn btn-light-success font-weight-bold">OK</button>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="prevenda_rascunhos_lista" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="sprevenda_rascunhos_lista" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Rascunhos de Pré-venda</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="col-sm-12 col-lg-12 col-12 d-flex" style="flex-direction: column;gap: 4px;max-height: 500px;overflow-y: scroll;">
								@if(isset($preVendaRascunhos) && count($preVendaRascunhos) > 0)
								@foreach ($preVendaRascunhos as $v)
								<div class="row">
									<a style="width: 85% !important;" href="/frenteCaixa/prevenda/edit/{{$v->id}}" class="bg-secondary">
										<span class="btn text-left">
											{{ $v->vendedor() }}
										</span>
										<span class="btn text-left">
											#{{$v->id}} - {{$v->observacao}} - {{$v->cliente->razao_social ?? '' }} - {{\Carbon\Carbon::parse($v->updated_at)->format('d/m/Y H:i:s')}}
										</span>
									</a>
									<a style="width: 12% !important;" class="btn btn-danger ml-1" title="REMOVER RASCUNHO" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/frenteCaixa/deleteRascunhoPreVenda/{{ $v->id }}" }else{return false} })'>
										<i class="la la-trash"></i>
									</a>
								</div>
								@endforeach
								@else
								<p class="p-3">Sem rascunhos de pré-venda.</p>
								@endif
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="selecionar_vendedor_modal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="selecionar_vendedor_modal" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Selecione o vendedor</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="col-12 d-flex">
								<select class="form-control mb-5" id="select-vendedor" style="width: 100%" name="Vendedor" tabindex="-1" aria-hidden="true">
									<option value="" data-select2-id="5854">Selecione o vendedor</option>
									@foreach($vendedores as $v)
									<option value="{{$v->id}}">{{$v->funcionario->nome}}</option>
									@endforeach
								</select>
							</div>
						</div>
					</div>
					<div class="modal-footer py-2">
						<div class="btn btn-success" onclick="selectVendedor()" data-dismiss="modal" aria-label="Close">OK</div>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="selecionar_assessor_modal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="selecionar_assessor_modal" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Selecione o assessor</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="col-12 d-flex">
								<select class="form-control mb-5" id="select-assessor" style="width: 100%" name="assessor" tabindex="-1" aria-hidden="true">
									<option value="">Selecione o assessor</option>
									@foreach($acessores as $v)
									<option value="{{$v->id}}">{{$v->razao_social}}</option>
									@endforeach
								</select>
							</div>
						</div>
					</div>
					<div class="modal-footer py-2">
						<div class="btn btn-success" onclick="selectAssessor()" data-dismiss="modal" aria-label="Close">OK</div>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="lista_prevenda_nivel2" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="lista_prevenda_nivel2" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Pré-vendas</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="col-sm-12 col-lg-12 col-12 d-flex" style="flex-direction: column;gap: 4px;max-height: 500px;overflow-y: scroll;">
								<div class="" id="lista_prevenda_container" style="min-height: 50px;">
									<div class="bg-secondary">
										<span class="btn text-left">

										</span>
										<span class="btn text-left">

										</span>
									</div>
								</div>
							</div>
						</div>
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
							<div class="form-group validated col-lg-3 col-12">
								<label class="col-form-label" id="">Valor</label>
								<input type="text" placeholder="Valor" id="valor_sangria" name="valor_sangria" class="form-control" value="">
							</div>

							@if(is_array($contasEmpresa) && sizeof($contasEmpresa) > 0)
							<div class="form-group validated col-lg-9 col-12">
								<label class="col-form-label">Conta</label>
								<select required id="conta_sagria_id" class="select2-custom custom-select" style="width: 100%">
									<option value=""></option>
									@foreach($contasEmpresa as $c)
									<option value="{{ $c->id }}">
										{{ $c->nome }}
									</option>
									@endforeach
								</select>
							</div>
							@endif

							<div class="form-group validated col-sm-12 col-lg-12 col-12">
								<label class="col-form-label" id="">Observação</label>
								<input type="text" placeholder="Observação" id="obs_sangria" name="obs" class="form-control" value="">
							</div>
						</div>

					</div>
					<div class="modal-footer">
						<button style="width: 100%" type="button" onclick="sangriaCaixa()" class="btn btn-success font-weight-bold">OK</button>
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
							@if(is_array($contasEmpresa) && sizeof($contasEmpresa) > 0)
							<div class="form-group validated col-lg-6 col-12">
								<label class="col-form-label">Conta</label>
								<select required id="conta_suprimento_id" class="select2-custom custom-select" style="width: 100%">
									<option value=""></option>
									@foreach($contasEmpresa as $c)
									<option value="{{ $c->id }}">
										{{ $c->nome }}
									</option>
									@endforeach
								</select>
							</div>
							@endif
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

		<div class="modal fade" id="modal-pedido-delivery" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-xl" role="document">
				<form method="post" action="/pedidosDelivery/lerPedido" id="form-pedido-delivery">
					@csrf
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title">Novo Pedido</h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								x
							</button>
						</div>
						<div class="modal-body">

						</div>

						<div class="modal-footer">
							<button id="btn-cancelar-pedido" type="button" class="btn btn-danger font-weight-bold spinner-white spinner-right">Recusar pedido</button>
							<button id="btn-confirmar-pedido" type="button" class="btn btn-success font-weight-bold spinner-white spinner-right">Confirmar</button>
						</div>
					</div>
				</form>
			</div>
		</div>

		<div class="modal fade" id="modal-pedido-mesa" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<form method="post" action="/pedidosMesa/alterarStatusPedido" id="form-pedido-mesa">
					@csrf
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title">Novo Atendimento</h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								x
							</button>
						</div>
						<div class="modal-body">

						</div>

						<div class="modal-footer">
							<button id="btn-cancelar-pedido-mesa" type="button" class="btn btn-danger font-weight-bold spinner-white spinner-right">Recusar</button>
							<button id="btn-confirmar-pedido-mesa" type="button" class="btn btn-success font-weight-bold spinner-white spinner-right">Confirmar</button>
						</div>
					</div>
				</form>
			</div>
		</div>

		<div class="modal fade" id="modal-pedido-ifood" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-xl" role="document">
				<form method="post" action="/ifood/readOrder" id="form-pedido-ifood">
					@csrf
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title">Novo Pedido iFood <img width="50" src="/icones/ifood.png"></h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								x
							</button>
						</div>
						<div class="modal-body">

						</div>

						<div class="modal-footer">
							<button id="btn-cancelar-pedido-ifood" type="button" class="btn btn-danger font-weight-bold spinner-white spinner-right">Recusar pedido</button>
							<button id="btn-confirmar-pedido-ifood" type="button" class="btn btn-success font-weight-bold spinner-white spinner-right">Confirmar</button>
						</div>
					</div>
				</form>
			</div>
		</div>

		<div class="modal fade" id="modal-cancelar-pedido-ifood" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Cancelando Pedido</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="form-group col-lg-6 col-12">
							<label>Código cancelamento</label>
							<select required id="codigo-ifood" class="custom-select form-control">
								@foreach(\App\Models\IfoodConfig::getStatusErros() as $key => $c)
								<option value="{{ $key }}">{{ $key }} - {{ $c }}</option>
								@endforeach
							</select>
						</div>
						<div class="form-group col-lg-12 col-12">
							<label>Descrição de cancelamento</label>
							<input type="text" id="motivo-ifood" class="form-control">
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
						<button type="button" onclick="cancelarPedidoIfood()" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Cancelar</button>
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
							<p class="text-info">*Ultimos 20 rascunhos</p>
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
												<a title="REMOVER RASCUNHO" onclick='swal("Atenção!", "Deseja editar este registro?", "warning").then((sim) => {if(sim){ location.href="/frenteCaixa/deleteRascunho/{{ $v->id }}" }else{return false} })' href="#!" class="btn btn-danger">
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
						@if(isset($consignadas) && sizeof($consignadas) > 0)
						<div class="col-xl-12">

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
													<a title="EDITAR RASCUNHO" href="/frenteCaixa/edit/{{$v->id}}" class="btn btn-warning btn-sm">
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

		<div id="modal4" class="modal">
			<div class="modal-content">
				<div class="row">
					<h4>Valor do troco: <strong id="valor_troco" class="orange-text">0,00</strong></h4>

					<h5>Sugestão:</h5>
					<div class="row 50_reais" style="display: none">
						<div class="col s3">
							<img class="money-cel" src="/imgs/50_reais.jpeg">
						</div>
						<div class="col s3">
							<h4 id="qtd_50_reais"></h4>
						</div>
					</div>
					<div class="row 20_reais" style="display: none">
						<div class="col s3">
							<img class="money-cel" src="/imgs/20_reais.jpeg">
						</div>
						<div class="col s3">
							<h4 id="qtd_20_reais"></h4>
						</div>
					</div>

					<div class="row 10_reais" style="display: none">
						<div class="col s3">
							<img class="money-cel" src="/imgs/10_reais.jpeg">
						</div>
						<div class="col s3">
							<h4 id="qtd_10_reais"></h4>
						</div>
					</div>

					<div class="row 5_reais" style="display: none">
						<div class="col s3">
							<img class="money-cel" src="/imgs/5_reais.jpeg">
						</div>
						<div class="col s3">
							<h4 id="qtd_5_reais"></h4>
						</div>
					</div>

					<div class="row 2_reais" style="display: none">
						<div class="col s3">
							<img class="money-cel" src="/imgs/2_reais.jpeg">
						</div>
						<div class="col s3">
							<h4 id="qtd_2_reais"></h4>
						</div>
					</div>

					<div class="row 1_real" style="display: none">
						<div class="col s3">
							<img class="money-moeda" src="/imgs/1_real.png">
						</div>
						<div class="col s3">
							<h4 id="qtd_1_real"></h4>
						</div>
					</div>

					<div class="row 50_centavo" style="display: none">
						<div class="col s3">
							<img class="money-moeda" src="/imgs/50_centavo.png">
						</div>
						<div class="col s3">
							<h4 id="qtd_50_centavos"></h4>
						</div>
					</div>

					<div class="row 25_centavo" style="display: none">
						<div class="col s3">
							<img class="money-moeda" src="/imgs/25_centavo.png">
						</div>
						<div class="col s3">
							<h4 id="qtd_25_centavos"></h4>
						</div>
					</div>

					<div class="row 10_centavo" style="display: none">
						<div class="col s3">
							<img class="money-moeda" src="/imgs/10_centavo.png">
						</div>
						<div class="col s3">
							<h4 id="qtd_10_centavos"></h4>
						</div>
					</div>


					<div class="row 5_centavo" style="display: none">
						<div class="col s3">
							<img class="money-moeda" src="/imgs/5_centavo.png">
						</div>
						<div class="col s3">
							<h4 id="qtd_5_centavos"></h4>
						</div>
					</div>

				</div>
			</div>
			<div class="modal-footer">
				<div class="modal-footer">
					<a href="#!" class="modal-action modal-close waves-effect waves-green btn-flat">Fechar</a>
				</div>

			</div>
		</div>

		<div class="modal fade" id="modal-cartao" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">INFORME OS DADOS DO CARTÃO <strong class="tipo-cartao"></strong></h5>
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

		<div class="modal fade" id="modal-mesas" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-xl" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Mesas</h5>
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

		{{-- Novo cliente modal --}}

		<div class="modal fade" id="add_cliente_modal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
			<div class="modal-dialog modal-xl" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">ADICIONAR NOVO CLIENTE</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							x
						</button>
					</div>
					<div class="modal-body">
						<div class="row">
							<form method="post" id="new_client_form">

								@csrf
								<div class="row">
									<div class="col-xl-12">
										<div class="kt-section kt-section--first">
											<div class="kt-section__body">

												<div class="row">
													<div class="form-group col-sm-12 col-lg-12">
														<label>Pessoa:</label>
														<div class="radio-inline">


															<label class="radio radio-success">
																<input value="p_fisica" name="group1" type="radio" id="pessoaFisica" @if(isset($cliente)) @if(strlen($cliente->cpf_cnpj)
																< 15) checked @endif @endif @if(old('group1') == 'p_fisica') checked @endif/>
																<span></span>
																FISICA
															</label>
															<label class="radio radio-success">
																<input value="p_juridica" name="group1" type="radio" id="pessoaJuridica" @if(isset($cliente)) @if(strlen($cliente->cpf_cnpj) > 15) checked @endif @endif @if(old('group1') == 'p_juridica') checked @endif/>
																<span></span>
																JURIDICA
															</label>

															<label class="radio radio-success">
																<input value="p_ext" name="group1" type="radio" id="pessoaExt" @if(isset($cliente)) @if($cliente->cpf_cnpj == '00.000.000/0000-00') checked @endif @endif @if(old('group1') == 'p_ext') checked @endif/>
																<span></span>
																EXTERIOR
															</label>
														</div>

													</div>
												</div>

												<div class="row">
													<div class="form-group validated col-sm-3 col-lg-4">
														<label class="col-form-label" id="lbl_cpf_cnpj">CPF</label>
														<div class="">
															<input type="text" id="cpf_cnpj" class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif cpf_cnpj" name="cpf_cnpj" value="{{{ isset($cliente) ? $cliente->cpf_cnpj : old('cpf_cnpj') }}}">
															@if($errors->has('cpf_cnpj'))
															<div class="invalid-feedback">
																{{ $errors->first('cpf_cnpj') }}
															</div>
															@endif
														</div>
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
															<input id="razao_social" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif" name="razao_social" value="{{{ isset($cliente) ? $cliente->razao_social : old('razao_social') }}}">
															@if($errors->has('razao_social'))
															<div class="invalid-feedback">
																{{ $errors->first('razao_social') }}
															</div>
															@endif
														</div>
													</div>

													<div class="form-group validated col-sm-10 col-lg-6">
														<label class="col-form-label">Nome Fantasia</label>
														<div class="">
															<input id="nome_fantasia" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif" name="nome_fantasia" value="{{{ isset($cliente) ? $cliente->nome_fantasia : old('nome_fantasia') }}}">
															@if($errors->has('nome_fantasia'))
															<div class="invalid-feedback">
																{{ $errors->first('nome_fantasia') }}
															</div>
															@endif
														</div>
													</div>
												</div>


												<div class="row">

													<div class="form-group validated col-sm-3 col-lg-4">
														<label class="col-form-label" id="lbl_ie_rg">IE/RG</label>
														<div class="">
															<input type="text" id="ie_rg" class="form-control @if($errors->has('ie_rg')) is-invalid @endif" name="ie_rg" value="{{{ isset($cliente) ? $cliente->ie_rg : old('ie_rg') }}}">
															@if($errors->has('ie_rg'))
															<div class="invalid-feedback">
																{{ $errors->first('ie_rg') }}
															</div>
															@endif
														</div>
													</div>
													<div class="form-group validated col-lg-3 col-md-3 col-sm-10">
														<label class="col-form-label">Consumidor Final</label>

														<select class="custom-select form-control" id="consumidor_final" name="consumidor_final">
															<option value=""></option>
															<option @if(isset($cliente) && $cliente->consumidor_final == 1) selected @endif value="1" @if(old('consumidor_final') == 1) selected @endif selected>SIM</option>
															<option @if(isset($cliente) && $cliente->consumidor_final == 0) selected @endif value="0" @if(old('consumidor_final') == 0) @endif>NAO</option>
														</select>
														@if($errors->has('consumidor_final'))
														<div class="invalid-feedback">
															{{ $errors->first('consumidor_final') }}
														</div>
														@endif

													</div>

													<div class="form-group validated col-lg-3 col-md-3 col-sm-10">
														<label class="col-form-label">Contribuinte</label>

														<select class="custom-select form-control" id="contribuinte" name="contribuinte">
															<option value=""></option>
															<option @if(isset($cliente) && $cliente->contribuinte == 1) selected @endif value="1" @if(old('contribuinte') == 1) selected @endif selected>SIM</option>
															<option @if(isset($cliente) && $cliente->contribuinte == 0) selected @endif value="0" @if(old('contribuinte') == 0) @endif>NAO</option>
														</select>
														@if($errors->has('contribuinte'))
														<div class="invalid-feedback">
															{{ $errors->first('contribuinte') }}
														</div>
														@endif

													</div>

													<div class="form-group validated col-sm-3 col-lg-4">
														<label class="col-form-label" id="lbl_ie_rg">Limite de Venda</label>
														<div class="">
															<input type="text" id="limite_venda" class="form-control @if($errors->has('limite_venda')) is-invalid @endif money" name="limite_venda" value="{{{ isset($cliente) ? $cliente->limite_venda : old('limite_venda') }}}">
															@if($errors->has('limite_venda'))
															<div class="invalid-feedback">
																{{ $errors->first('limite_venda') }}
															</div>
															@endif
														</div>
													</div>

													<div class="form-group validated col-sm-3 col-lg-3">
														<label class="col-form-label" id="lbl_ie_rg">Data de Aniversário</label>
														<div class="">
															<input type="text" id="data_aniversario" class="form-control @if($errors->has('data_aniversario')) is-invalid @endif" data-mask="00/00" data-mask-reverse="true" name="data_aniversario" value="{{{ isset($cliente) ? $cliente->data_aniversario : old('data_aniversario') }}}">
															@if($errors->has('data_aniversario'))
															<div class="invalid-feedback">
																{{ $errors->first('data_aniversario') }}
															</div>
															@endif
														</div>
													</div>

												</div>
												<hr>
												<h5>Endereço de Faturamento</h5>
												<div class="row">
													<div class="form-group validated col-sm-8 col-lg-8">
														<label class="col-form-label">Rua</label>
														<div class="">
															<input id="rua" type="text" class="form-control @if($errors->has('rua')) is-invalid @endif" name="rua" value="{{{ isset($cliente) ? $cliente->rua : old('rua') }}}">
															@if($errors->has('rua'))
															<div class="invalid-feedback">
																{{ $errors->first('rua') }}
															</div>
															@endif
														</div>
													</div>

													<div class="form-group validated col-sm-2 col-lg-2">
														<label class="col-form-label">Número</label>
														<div class="">
															<input id="numero" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" value="{{{ isset($cliente) ? $cliente->numero : old('numero') }}}">
															@if($errors->has('numero'))
															<div class="invalid-feedback">
																{{ $errors->first('numero') }}
															</div>
															@endif
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group validated col-sm-8 col-lg-10">
														<label class="col-form-label">Complemento</label>
														<div class="">
															<input id="complemento" type="text" class="form-control @if($errors->has('complemento')) is-invalid @endif" name="complemento" value="{{{ isset($cliente) ? $cliente->complemento : old('complemento') }}}">
															@if($errors->has('complemento'))
															<div class="invalid-feedback">
																{{ $errors->first('complemento') }}
															</div>
															@endif
														</div>
													</div>
												</div>
												<div class="row">
													<div class="form-group validated col-sm-8 col-lg-5">
														<label class="col-form-label">Bairro</label>
														<div class="">
															<input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" value="{{{ isset($cliente) ? $cliente->bairro : old('bairro') }}}">
															@if($errors->has('bairro'))
															<div class="invalid-feedback">
																{{ $errors->first('bairro') }}
															</div>
															@endif
														</div>
													</div>

													<div class="form-group validated col-sm-8 col-lg-3">
														<label class="col-form-label">CEP</label>
														<div class="">
															<input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif" name="cep" value="{{{ isset($cliente) ? $cliente->cep : old('cep') }}}">
															@if($errors->has('cep'))
															<div class="invalid-feedback">
																{{ $errors->first('cep') }}
															</div>
															@endif
														</div>
													</div>

													<div class="form-group validated col-sm-8 col-lg-4">
														<label class="col-form-label">Email</label>
														<div class="">
															<input id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{{ isset($cliente) ? $cliente->email : old('email') }}}">
															@if($errors->has('email'))
															<div class="invalid-feedback">
																{{ $errors->first('email') }}
															</div>
															@endif
														</div>
													</div>

												</div>

												<div class="row">

													<div class="form-group validated col-lg-5 col-md-5 col-sm-10">
														<label class="col-form-label">Cidade</label>
														<select class="form-control select2" style="width: 100%" id="kt_select2_4" name="cidade">
															@foreach($cidades as $c)
															<option value="{{$c->id}}" @isset($cliente) @if($c->id == $cliente->cidade_id) selected @endif @endisset
																@if(old('cidade') == $c->id)
																selected
																@endif
																>
																{{$c->nome}} ({{$c->uf}})
															</option>
															@endforeach
														</select>
														@if($errors->has('cidade'))
														<div class="invalid-feedback">
															{{ $errors->first('cidade') }}
														</div>
														@endif
													</div>

													<div class="form-group validated col-lg-3 col-md-3 col-sm-6">
														<label class="col-form-label">Pais</label>
														<select class="form-control select2" id="kt_select2_5" name="cod_pais" style="width: 100%;">
															@foreach($pais as $p)
															<option value="{{$p->codigo}}" @if(isset($cliente)) @if($p->codigo == $cliente->cod_pais) selected @endif @else @if($p->codigo == 1058) selected @endif @endif >{{$p->codigo}} -  ({{$p->nome}})</option>
															@endforeach
														</select>
														@if($errors->has('cod_pais'))
														<div class="invalid-feedback">
															{{ $errors->first('cod_pais') }}
														</div>
														@endif
													</div>

													<div class="form-group validated col-sm-8 col-lg-4">
														<label class="col-form-label">ID estrangeiro (Opcional)</label>
														<div class="">
															<input id="id_estrangeiro" type="text" class="form-control @if($errors->has('id_estrangeiro')) is-invalid @endif" name="id_estrangeiro" value="{{{ isset($cliente) ? $cliente->id_estrangeiro : old('id_estrangeiro') }}}">
															@if($errors->has('id_estrangeiro'))
															<div class="invalid-feedback">
																{{ $errors->first('id_estrangeiro') }}
															</div>
															@endif
														</div>
													</div>
												</div>

												<div class="row">
													<div class="form-group validated col-sm-8 col-lg-3">
														<label class="col-form-label">Telefone (Opcional)</label>
														<div class="">
															<input id="telefone" type="text" class="form-control @if($errors->has('telefone')) is-invalid @endif" name="telefone" value="{{{ isset($cliente) ? $cliente->telefone : old('telefone') }}}">
															@if($errors->has('telefone'))
															<div class="invalid-feedback">
																{{ $errors->first('telefone') }}
															</div>
															@endif
														</div>
													</div>

													<div class="form-group validated col-sm-8 col-lg-3">
														<label class="col-form-label">Celular (Opcional)</label>
														<div class="">
															<input id="celular" type="text" class="form-control @if($errors->has('celular')) is-invalid @endif" name="celular" value="{{{ isset($cliente) ? $cliente->celular : old('celular') }}}">
															@if($errors->has('celular'))
															<div class="invalid-feedback">
																{{ $errors->first('celular') }}
															</div>
															@endif
														</div>
													</div>

													<div class="form-group validated col-sm-8 col-lg-3">
														<label class="col-form-label">Grupo (Opcional)</label>
														<div class="">

															<select class="custom-select form-control" name="grupo_id">
																<option value="0">--</option>
																@foreach($grupos as $g)
																<option @if(isset($cliente)) @if($cliente->grupo_id == $g->id) selected @endif @endif value="{{$g->id}}"
																	@if(old('grupo_id') == $g->id)
																	selected
																	@endif>
																	{{$g->nome}}
																</option>
																@endforeach
															</select>
														</div>
													</div>

													<div class="form-group validated col-sm-8 col-lg-3">
														<label class="col-form-label">Assessor (Opcional)</label>
														<div class="">

															<select class="custom-select form-control" name="acessor_id">
																<option value="0">--</option>
																@foreach($acessores as $a)
																<option @if(isset($cliente)) @if($cliente->acessor_id == $a->id) selected @endif @endif value="{{$a->id}}"
																	@if(old('acessor_id') == $a->id)
																	selected
																	@endif>
																	{{$a->razao_social}}
																</option>
																@endforeach
															</select>
														</div>
													</div>
												</div>

												<div class="row">
													<div class="form-group validated col-sm-3 col-lg-3">
														<label class="col-form-label">Dados do Contador</label>
														<div class="col-12">
															<span class="switch switch-outline switch-info">
																<label>
																	<input value="true" @if(isset($cliente) && $cliente->contador_nome != "") checked @endif @if(old('info_contador')) checked @endif type="checkbox" name="info_contador" id="info_contador">
																	<span></span>
																</label>
															</span>
														</div>
													</div>

													<div class="form-group validated col-sm-5 col-lg-4 ct">
														<label class="col-form-label">Nome</label>
														<div class="">
															<input id="contador_nome" type="text" class="form-control @if($errors->has('contador_nome')) is-invalid @endif" name="contador_nome" value="{{{ isset($cliente) ? $cliente->contador_nome : old('contador_nome') }}}">
															@if($errors->has('contador_nome'))
															<div class="invalid-feedback">
																{{ $errors->first('contador_nome') }}
															</div>
															@endif
														</div>
													</div>

													<div class="form-group validated col-sm-4 col-lg-3 ct">
														<label class="col-form-label">Telefone</label>
														<div class="">
															<input id="contador_telefone" type="text" class="form-control @if($errors->has('contador_telefone')) is-invalid @endif telefone" name="contador_telefone" value="{{{ isset($cliente) ? $cliente->contador_telefone : old('contador_telefone') }}}">
															@if($errors->has('contador_telefone'))
															<div class="invalid-feedback">
																{{ $errors->first('contador_telefone') }}
															</div>
															@endif
														</div>
													</div>

													<div class="form-group validated col-sm-5 col-lg-4 ct">
														<label class="col-form-label">Email</label>
														<div class="">
															<input id="contador_email" type="email" class="form-control @if($errors->has('contador_email')) is-invalid @endif" name="contador_email" value="{{{ isset($cliente) ? $cliente->contador_email : old('contador_email') }}}">
															@if($errors->has('contador_email'))
															<div class="invalid-feedback">
																{{ $errors->first('contador_email') }}
															</div>
															@endif
														</div>
													</div>
												</div>

												<div class="row">
													<div class="form-group validated col-sm-8 col-lg-8 col-12">
														<label class="col-form-label">Observação</label>
														<div class="">
															<input id="observacao" type="text" class="form-control @if($errors->has('observacao')) is-invalid @endif" name="observacao" value="{{{ isset($cliente) ? $cliente->observacao : old('observacao') }}}">
															@if($errors->has('observacao'))
															<div class="invalid-feedback">
																{{ $errors->first('observacao') }}
															</div>
															@endif
														</div>
													</div>

													<div class="form-group validated col-sm-4 col-lg-4 col-12">
														<label class="col-form-label">Vendedor/Funcionário</label>
														<div class="">
															<select class="custom-select form-control" name="funcionario_id">
																<option value="0">--</option>
																@foreach($funcionarios as $f)
																<option @if(isset($cliente)) @if($cliente->funcionario_id == $f->id) selected @endif @endif value="{{$f->id}}"
																	@if(old('funcionario_id') == $f->id)
																	selected
																	@endif>
																	{{$f->nome}}
																</option>
																@endforeach
															</select>
															@if($errors->has('funcionario_id'))
															<div class="invalid-feedback">
																{{ $errors->first('funcionario_id') }}
															</div>
															@endif
														</div>
													</div>
												</div>

												<hr>
												<h5>Endereço de Cobrança (Opcional)</h5>
												<div class="row">
													<div class="form-group validated col-sm-8 col-lg-8">
														<label class="col-form-label">Rua</label>
														<div class="">
															<input id="rua_cobranca" type="text" class="form-control @if($errors->has('rua_cobranca')) is-invalid @endif" name="rua_cobranca" value="{{{ isset($cliente) ? $cliente->rua_cobranca : old('rua_cobranca') }}}">
															@if($errors->has('rua_cobranca'))
															<div class="invalid-feedback">
																{{ $errors->first('rua_cobranca') }}
															</div>
															@endif
														</div>
													</div>

													<div class="form-group validated col-sm-2 col-lg-2">
														<label class="col-form-label">Número</label>
														<div class="">
															<input id="numero_cobranca" type="text" class="form-control @if($errors->has('numero_cobranca')) is-invalid @endif" name="numero_cobranca" value="{{{ isset($cliente) ? $cliente->numero_cobranca : old('numero_cobranca') }}}">
															@if($errors->has('numero_cobranca'))
															<div class="invalid-feedback">
																{{ $errors->first('numero_cobranca') }}
															</div>
															@endif
														</div>
													</div>

												</div>
												<div class="row">
													<div class="form-group validated col-sm-8 col-lg-5">
														<label class="col-form-label">Bairro</label>
														<div class="">
															<input id="bairro_cobranca" type="text" class="form-control @if($errors->has('bairro_cobranca')) is-invalid @endif" name="bairro_cobranca" value="{{{ isset($cliente) ? $cliente->bairro_cobranca : old('bairro_cobranca') }}}">
															@if($errors->has('bairro_cobranca'))
															<div class="invalid-feedback">
																{{ $errors->first('bairro_cobranca') }}
															</div>
															@endif
														</div>
													</div>

													<div class="form-group validated col-sm-8 col-lg-3">
														<label class="col-form-label">CEP</label>
														<div class="">
															<input id="cep_cobranca" type="text" class="form-control @if($errors->has('cep_cobranca')) is-invalid @endif" name="cep_cobranca" value="{{{ isset($cliente) ? $cliente->cep_cobranca : old('cep_cobranca') }}}">
															@if($errors->has('cep_cobranca'))
															<div class="invalid-feedback">
																{{ $errors->first('cep_cobranca') }}
															</div>
															@endif
														</div>
													</div>
												</div>

												<div class="row">
													<div class="form-group validated col-lg-6 col-md-6 col-sm-10">
														<label class="col-form-label text-left col-lg-4 col-sm-12">Cidade</label>

														<select class="form-control select2" id="kt_select2_2" name="cidade_cobranca">
															<option value="-">--</option>
															@foreach($cidades as $c)
															<option value="{{$c->id}}" @isset($cliente) @if($c->id == $cliente->cidade_cobranca_id)selected
																@endif
																@endisset >
																{{$c->nome}} ({{$c->uf}})
															</option>
															@endforeach
														</select>
														@if($errors->has('cidade_cobranca_id'))
														<div class="invalid-feedback">
															{{ $errors->first('cidade_cobranca_id') }}
														</div>
														@endif
													</div>
												</div>

											</div>

										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
							<button type="button" id="salvar_novo_cliente" onclick="addNovoCliente()" class="btn btn-success">Salvar</button>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="modal-loading loading-class"></div>

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

			const is_troca = @if(isset($is_troca)) true @else false @endif;
			const is_preVenda = @if(isset($preVenda)) true @else false @endif;
		</script>

		<script type="text/javascript" src="/js/mousetrap.js"></script>

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
						$('#kt_datepicker_3, #kt_datepicker_3_validate').datepicker({
							rtl: KTUtil.isRTL(),
							todayBtn: "linked",
							clearBtn: false,
							format: 'dd/mm/yyyy',
							todayHighlight: false,
							templates: arrows
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

		{{-- para o modal de novo cliente --}}
		<script src="/js/pessoaFisicaOuJuridica.js" type="text/javascript"></script>
		<!-- <script src="/js/alert.js" type="text/javascript"></script> -->
		<script type="text/javascript" src="/js/frenteCaixa2.js"></script>
		@if($deliveryConfig && env("DELIVERY") == 1)
		<script type="text/javascript" src="/js/pedido_delivery.js"></script>
		<script type="text/javascript" src="/js/pedido_mesa.js"></script>
		@endif

		<script type="text/javascript" src="/js/main.js"></script>
	</body>


	</html>
