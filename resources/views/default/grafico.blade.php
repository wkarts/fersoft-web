@extends('default.layout')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/fer-modules.css') }}?v={{ filemtime(public_path('css/fer-modules.css')) }}">
@endpush
@section('content')
<header class="fer-page__heading"><h1>Dashboard</h1></header>
<style type="text/css">
	.dash-conta:hover{
		cursor: pointer;
	}
</style>
<div class="fer-page fer-dashboard-legacy card card-custom gutter-b">
	<div class="card-body">
        <!--
		@if(empresaComFilial() && sizeof(getLocaisUsarioLogado()) > 1)
		<div class="row">
			{!! __view_locais_select_home() !!}
			<div class="form-group col-12 col-lg-2">
				<br><br>
				<button id="set-location" class="btn btn-light-primary">Definir como padrão</button>
			</div>
		</div>
		@endif
        -->
		<div class="row" style="margin-bottom: 7px;">
			<div class="col-12">
				<div class="fer-periods d-flex align-items-center">

					<a id="hoje" class="btn active btn-clean btn-sm font-weight-bold font-size-base mr-1">Hoje</a>
					<a id="seteDias" href="#" class="btn btn-clean btn-sm font-weight-bold font-size-base mr-1">Semana</a>
					<a id="trintaDias" href="#" class="btn btn-clean btn-sm font-weight-bold font-size-base mr-1">Mês</a>
					<a id="sessentaDias" href="#" class="btn btn-clean btn-sm font-weight-bold font-size-base mr-1">60 Dias</a>
					<a id="clickTour" class="btn btn-clean btn-light-success btn-sm font-weight-bold font-size-base mr-1">Tour</a>
				</div>
			</div>

		</div>

		<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
			<div class="col-sm-6 col-lg-4 col-md-4 col-xl-2">

				<div class="col fer-kpi px-6 py-8 rounded-xl mb-7">
					<span class="fer-metric-icon"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/></svg></span>
					<a href="/clientes" class="fer-kpi__link font-weight-bold font-size-h6 mt-2">Clientes: <strong>{{$totalDeClientes}}</strong></a>
				</div>
			</div>

			<div class="col-sm-6 col-lg-4 col-md-4 col-xl-2">

				<div class="col fer-kpi px-6 py-8 rounded-xl mb-7">
					<span class="fer-metric-icon"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2 9 5v10l-9 5-9-5V7zM3 7l9 5 9-5M12 12v10"/></svg></span>
					<a href="/produtos" class="fer-kpi__link font-weight-bold font-size-h6 mt-2">Produtos: <strong id="tot-produtos"></strong></a>
				</div>
			</div>

			<div class="col-sm-6 col-lg-4 col-md-4 col-xl-2">

				<div class="col fer-kpi px-6 py-8 rounded-xl mb-7">
					<span class="fer-metric-icon"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M7 16l4-4 3 2 5-7"/></svg></span>
					<a class="fer-kpi__link font-weight-bold mt-2">Vendas: <strong id="tot-vendas"></strong></a>
				</div>
			</div>

			<div class="col-sm-6 col-lg-4 col-md-4 col-xl-2">
				<div class="col fer-kpi px-6 py-8 rounded-xl mb-7">
					<span class="fer-metric-icon"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l4-2 4 2 4-2 4 2V2l-4 2-4-2-4 2z"/><path d="M8 9h8M8 13h8"/></svg></span>
					<a href="/pedidosDelivery" class="fer-kpi__link font-weight-bold font-size-h6 mt-2">Pedidos: <strong id="tot-pedidos"></strong></a>
				</div>
			</div>

			<div class="col-sm-6 col-lg-4 col-md-4 col-xl-2 dash-conta" onclick="contasReceber()">

				<div class="col fer-kpi px-6 py-8 rounded-xl mb-7">
					<span class="fer-metric-icon"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7 14 5-5 5 5"/></svg></span>
					<a class="fer-kpi__link font-weight-bold font-size-h6 mt-2"><span style="font-size: 10px;">Contas a receber: <strong id="tot-contas-receber"></strong></span></a>
				</div>
			</div>

			<div class="col-sm-6 col-lg-4 col-md-4 col-xl-2 dash-conta" onclick="contasPagar()">

				<div class="col fer-kpi px-6 py-8 rounded-xl mb-7">
					<span class="fer-metric-icon"><svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2 10 19H2zM12 9v5M12 18h.01"/></svg></span>
					<a class="fer-kpi__link font-weight-bold font-size-h6 mt-2"><span style="font-size: 11px;">Contas a pagar: <strong id="tot-contas-pagar"></strong></span></a>
				</div>
			</div>

		</div>

		<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__bounce">

			<div class="col-lg-6 col-12 @if(!in_array('contas_pagar', $graficosDash)) d-none @endif">
				<div class="fer-panel card card-custom gutter-b example example-compact">
					<div class="card-header">
						<h3 class="card-title">Contas a pagar</h3>
					</div>
					<div class="card-body">
						<canvas id="grafico-contas-pagar" ></canvas>
					</div>
				</div>
			</div>

			<div class="col-lg-6 col-12 @if(!in_array('contas_receber', $graficosDash)) d-none @endif">
				<div class="fer-panel card card-custom gutter-b example example-compact">
					<div class="card-header">
						<h3 class="card-title">Contas a receber</h3>
					</div>
					<div class="card-body">
						<canvas id="grafico-contas-receber" ></canvas>

					</div>
				</div>
			</div>

			<div class="col-lg-6 col-12 @if(!in_array('vendas_pdv', $graficosDash)) d-none @endif">
				<div class="fer-panel card card-custom gutter-b example example-compact">
					<div class="card-header">
						<h3 class="card-title">Vendas PDV</h3>
					</div>
					<div class="card-body">
						<canvas id="grafico-vendas-pdv"></canvas>

					</div>
				</div>
			</div>

			<div class="col-lg-6 col-12 @if(!in_array('vendas', $graficosDash)) d-none @endif">
				<div class="fer-panel card card-custom gutter-b example example-compact">
					<div class="card-header">
						<h3 class="card-title">Vendas Pedido</h3>
					</div>
					<div class="card-body">
						<canvas id="grafico-vendas-pedido"></canvas>

					</div>
				</div>
			</div>

			<div class="col-lg-6 col-12 @if(!in_array('orcamentos', $graficosDash)) d-none @endif">
				<div class="fer-panel card card-custom gutter-b example example-compact">
					<div class="card-header">
						<h3 class="card-title">Orçamentos</h3>
					</div>
					<div class="card-body">
						<canvas id="grafico-orcamentos"></canvas>

					</div>
				</div>
			</div>

			<div class="col-lg-6 col-12 @if(!in_array('produtos', $graficosDash)) d-none @endif">
				<div class="fer-panel card card-custom gutter-b example example-compact">
					<div class="card-header">
						<h3 class="card-title">Produtos Cadastrados</h3>
					</div>
					<div class="card-body">
						<canvas id="grafico-produtos"></canvas>

					</div>
				</div>
			</div>


			<div class="col-lg-6 col-12 @if(!in_array('nfe', $graficosDash)) d-none @endif">
				<div class="fer-panel card card-custom gutter-b example example-compact">
					<div class="card-header">
						<h3 class="card-title">Documentos emitidos NFe</h3>
					</div>
					<div class="card-body">
						<canvas id="grafico-nfe"></canvas>

					</div>
				</div>
			</div>

			<div class="col-lg-6 col-12 @if(!in_array('nfce', $graficosDash)) d-none @endif">
				<div class="fer-panel card card-custom gutter-b example example-compact">
					<div class="card-header">
						<h3 class="card-title">Documentos emitidos NFCe</h3>
					</div>
					<div class="card-body">
						<canvas id="grafico-nfce"></canvas>

					</div>
				</div>
			</div>

			<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
				<div class="fer-panel card card-custom gutter-b example example-compact">
					<div class="card-header">
						<h3 class="card-title">Faturamento de vendas</h3>
					</div>
					<div class="card-body">
						<div class="row align-items-center">
							<div class="form-group col-lg-3 col-md-4 col-sm-6">

								<div class="radio-inline" style="margin-top: 30px;">
									<label class="radio radio-outline radio-success">
										<input type="radio" id="test1" name="group1" onchange="alteraModeloGrafico('line')" checked>
										<span></span>Linha
									</label>

									<label class="radio radio-outline radio-success">
										<input type="radio" id="test2" name="group1" onchange="alteraModeloGrafico('pie')" >
										<span></span>Pizza
									</label>

									<label class="radio radio-outline radio-success">
										<input type="radio" id="test3" name="group1" onchange="alteraModeloGrafico('bar')" >
										<span></span>Barra
									</label>


								</div>
							</div>

							<div class="form-group col-lg-3 col-md-4 col-sm-6">
								<label class="col-form-label">Data Inicial</label>
								<div class="">
									<div class="input-group date">
										<input type="text" name="data_inicial" class="form-control date-input data_inicial" value="{{$dataInicial}}" id="kt_datepicker_3" />
										<div class="input-group-append">
											<span class="input-group-text">
												<i class="la la-calendar"></i>
											</span>
										</div>
									</div>
								</div>
							</div>

							<div class="form-group col-lg-3 col-md-4 col-sm-6">
								<label class="col-form-label">Data Final</label>
								<div class="">
									<div class="input-group date">
										<input type="text" name="data_final" class="form-control date-input data_final" value="{{$dataFinal}}" id="kt_datepicker_3" />
										<div class="input-group-append">
											<span class="input-group-text">
												<i class="la la-calendar"></i>
											</span>
										</div>
									</div>
								</div>
							</div>
							<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
								<button onclick="filtrar()" style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Filtrar</button>
							</div>
						</div>


						<div class="row" id="novo-faturamento">
							<canvas id="grafico-faturamento" ></canvas>

						</div>

					</div>
				</div>
			</div>

			<!-- grafico de produtos -->

			<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
				<div class="fer-panel card card-custom gutter-b example example-compact">
					<div class="card-header">
						<h3 class="card-title">Produtos mais vendidos</h3>
					</div>
					<div class="card-body">
						<div class="row align-items-center">
							<div class="form-group col-lg-3 col-md-4 col-sm-6">

								<div class="radio-inline" style="margin-top: 30px;">

									<label class="radio radio-outline radio-success">
										<input type="radio" id="test3" name="group1" onchange="alteraModeloGrafico2('bar')" checked>
										<span></span>Barra
									</label>
									<!-- <label class="radio radio-outline radio-success">
										<input type="radio" id="test1" name="group1" onchange="alteraModeloGrafico2('line')">
										<span></span>Linha
									</label>
 -->
									<label class="radio radio-outline radio-success">
										<input type="radio" id="test2" name="group1" onchange="alteraModeloGrafico2('pie')" >
										<span></span>Pizza
									</label>

								</div>
							</div>

							<div class="form-group col-lg-3 col-md-4 col-sm-6">
								<label class="col-form-label">Data Inicial</label>
								<div class="">
									<div class="input-group date">
										<input type="text" name="data_inicial" class="form-control date-input data_inicial2" value="{{$dataInicial2}}" id="kt_datepicker_3" />
										<div class="input-group-append">
											<span class="input-group-text">
												<i class="la la-calendar"></i>
											</span>
										</div>
									</div>
								</div>
							</div>

							<div class="form-group col-lg-3 col-md-4 col-sm-6">
								<label class="col-form-label">Data Final</label>
								<div class="">
									<div class="input-group date">
										<input type="text" name="data_final" class="form-control date-input data_final2" value="{{$dataFinal2}}" id="kt_datepicker_3" />
										<div class="input-group-append">
											<span class="input-group-text">
												<i class="la la-calendar"></i>
											</span>
										</div>
									</div>
								</div>
							</div>
							<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
								<button onclick="filtrarProdutos()" style="margin-top: 15px;" class="btn btn-light-primary px-6 font-weight-bold">Filtrar</button>
							</div>
						</div>


						<div class="row" id="novo-produtos">

						</div>

					</div>
				</div>
			</div>
		</div>

	</div>
</div>

@endsection
