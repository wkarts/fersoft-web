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

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<div class="row">

				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-6 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
					<div class="accordion" id="accordionExample1">

						<div class="card gutter-b example example-compact">
							<div class="card-header">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne1a">
									<h3 class="card-title">Relatório de Empresas<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne1a" class="collapse" data-parent="#accordionExample1">
								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorioSuper/empresas">
											<div class="row">
												

												<div class="form-group validated col-lg-12 col-md-5 col-sm-10">

													<label class="col-form-label text-left">Empresa</label>
													<select class="form-control select2" style="width: 100%;" id="kt_select2_5" name="empresa">
														<option value="null">Selecione a empresa</option>
														@foreach($empresas as $e)
														<option value="{{$e->id}}">{{$e->id}} - {{$e->nome}}/{{$e->nome_fantasia}} ({{$e->cnpj}})</option>
														@endforeach
													</select>

												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Status</label>
													<div class="">
														<select name="status" class="custom-select">
															<option @isset($status) @if($status == 'TODOS') selected @endif @endisset value="TODOS">TODOS</option>
															<option @isset($status) @if($status == 1) selected @endif @endisset value="1">ATIVO</option>
															<option @isset($status) @if($status == 2) selected @endif @endisset value="2">PENDENTE</option>
															<option @isset($status) @if($status == 0) selected @endif @endisset value="0">DESATIVADO</option>
														</select>
													</div>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label text-left">Plano</label>

													<select class="form-control select2" style="width: 100%" id="kt_select2_8" name="plano">
														<option value="null">Selecione o plano</option>
														@foreach($planos as $p)
														<option value="{{$p->id}}">{{$p->nome}}</option>
														@endforeach
													</select>
												</div>

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
					<div class="accordion" id="accordionExample2">

						<div class="card gutter-b example example-compact">
							<div class="card-header">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne2">
									<h3 class="card-title">Certificados à Vencer<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne2" class="collapse" data-parent="#accordionExample2">
								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorioSuper/certificados">
											<div class="row">
												<div class="form-group col-lg-6">
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

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Status</label>
													<div class="">
														<select name="status" class="custom-select">
															<option @isset($status) @if($status == 'TODOS') selected @endif @endisset value="TODOS">TODOS</option>
															<option @isset($status) @if($status == 1) selected @endif @endisset value="1">VENCIDOS</option>
															<option @isset($status) @if($status == 2) selected @endif @endisset value="2">Á VENCER</option>
														</select>
													</div>
												</div>

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Status da empresa</label>
													<div class="">
														<select name="status_empresa" class="custom-select">
															<option @isset($status) @if($status == 'TODOS') selected @endif @endisset value="TODOS">TODOS</option>
															<option @isset($status) @if($status == 1) selected @endif @endisset value="1">ATIVO</option>
															<option @isset($status) @if($status == 2) selected @endif @endisset value="2">INATIVO</option>
														</select>
													</div>
												</div>

												<div class="form-group col-lg-6">
													<label class="col-form-label">CPF/CNPJ</label>
													<div class="">
														<div class="input-group">
															<input type="text" name="cpf_cnpj" class="form-control cpf_cnpj" value=""/>
														</div>
													</div>
												</div>

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
					<div class="accordion" id="accordionExample3">

						<div class="card gutter-b example example-compact">
							<div class="card-header">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne3">
									<h3 class="card-title">Extrato do Cliente<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne3" class="collapse" data-parent="#accordionExample3">
								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorioSuper/extrtoCliente">
											<div class="row">
												<div class="form-group validated col-lg-12 col-md-5 col-sm-10">

													<label class="col-form-label text-left">Empresa</label>
													<select class="form-control select2" style="width: 100%;" id="kt_select2_4" name="empresa">
														<option value="null">Selecione a empresa</option>
														@foreach($empresas as $e)
														<option value="{{$e->id}}">{{$e->id}} - {{$e->nome}}/{{$e->nome_fantasia}} ({{$e->cnpj}})</option>
														@endforeach
													</select>

												</div>

												<div class="form-group col-lg-6">
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

												<div class="form-group col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Status</label>
													<div class="">
														<select name="status" class="custom-select">
															<option @isset($status) @if($status == 'TODOS') selected @endif @endisset value="TODOS">TODOS</option>
															<option @isset($status) @if($status == 1) selected @endif @endisset value="1">ATIVO</option>
															<option @isset($status) @if($status == 0) selected @endif @endisset value="0">DESATIVADO</option>
														</select>
													</div>
												</div>

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
					<div class="accordion" id="accordionExample3">

						<div class="card gutter-b example example-compact">
							<div class="card-header">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne4">
									<h3 class="card-title">Empresas por Contador<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne4" class="collapse" data-parent="#accordionExample3">
								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorioSuper/empresasContador">
											<div class="row">
												<div class="form-group validated col-lg-12 col-md-5 col-sm-10">

													<label class="col-form-label text-left">Contador</label>
													<select class="form-control select2" style="width: 100%;" id="kt_select2_1" name="contador_id">
														<option value="null">Selecione o contador</option>
														@foreach($contadores as $c)
														<option value="{{$c->id}}">{{$c->id}} - {{$c->razao_social}} ({{$e->cnpj}})</option>
														@endforeach
													</select>

												</div>


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
					<div class="accordion" id="accordionExample5">

						<div class="card gutter-b example example-compact">
							<div class="card-header">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne5">
									<h3 class="card-title">Histórico de Acessos<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne5" class="collapse" data-parent="#accordionExample5">
								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorioSuper/historicoAcessos">
											<div class="row">
												<div class="form-group col-lg-6">
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
					<div class="accordion" id="accordionExample6">

						<div class="card gutter-b example example-compact">
							<div class="card-header">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne6">
									<h3 class="card-title">Histórico de LOG<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne6" class="collapse" data-parent="#accordionExample6">
								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorioSuper/log">
											<div class="row">
												<div class="form-group col-lg-6">
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

												<div class="form-group validated col-lg-12 col-md-5 col-sm-10">

													<label class="col-form-label text-left">Empresa</label>
													<select class="form-control select2" style="width: 100%;" id="kt_select2_2" name="empresa">
														<option value="null">Selecione a empresa</option>
														@foreach($empresas as $e)
														<option value="{{$e->id}}">{{$e->id}} - {{$e->nome}}/{{$e->nome_fantasia}} ({{$e->cnpj}})</option>
														@endforeach
													</select>

												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Ordem</label>

													<select class="custom-select form-control" id="" name="ordem">
														<option value="desc">Data atual</option>
														<option value="asc">Data antiga</option>
													</select>
												</div>

												<div class="form-group validated col-lg-6 col-md-6 col-sm-6">
													<label class="col-form-label">Usário logado</label>

													<select class="custom-select form-control" id="" name="user_logado">
														<option value="">Todos</option>
														<option value="super">Super</option>
														@foreach($representantes as $r)
														<option value="{{$r->id}}">
															{{$r->id}} - {{$r->nome}}
														</option>
														@endforeach
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
					<div class="accordion" id="accordionExample2">

						<div class="card gutter-b example example-compact">
							<div class="card-header">
								<div class="card-title collapsed" data-toggle="collapse" data-target="#collapseOne7">
									<h3 class="card-title">Planos à Vencer<i class="la la-angle-double-down"></i>
									</h3>
								</div>
							</div>

							<div id="collapseOne7" class="collapse" data-parent="#accordionExample2">
								<div class="card-content">
									<div class="col-xl-12">
										<form target="_blank" method="get" action="/relatorioSuper/planosVencer">
											<div class="row">
												<div class="form-group col-lg-6">
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
</script>
@endsection	

@endsection	