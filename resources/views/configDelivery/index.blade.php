@extends('default.layout')
@section('content')
<style type="text/css">
	#map{
		width: 100%;
		height: 450px;
		background: #999;
	}

	.input-group-text:hover{
		cursor: pointer;
	}
</style>
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<!--begin::Portlet-->
				<form method="post" action="/configDelivery/save" enctype="multipart/form-data">
					<input type="hidden" name="id" value="{{{ isset($config->id) ? $config->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">{{{ isset($config) ? "Editar": "Cadastrar" }}} Configuração de Delivery</h3>
						</div>
					</div>
					@csrf

					@isset($config)
					<button data-toggle="modal" data-target="#modal-map" type="button" class="btn btn-primary">
						<i class="la la-map"></i> Informar localização
					</button>

					<a class="btn btn-info" href="/configDelivery/galeria">
						<i class="la la-photo"></i> Galeria de imagens da loja
					</a>

					@if($config->latitude != "")
					<br><br>
					<h5>Latitude: <strong class="lat">{{$config->latitude}}</strong></h5>
					<h5>Longitude: <strong class="lng">{{$config->longitude}}</strong></h5>
					@endif
					@endif
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">

										<div class="form-group validated col-sm-6 col-lg-3 col-10">
											<label class="col-form-label">Nome do delivery</label>
											<div class="">
												<input id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($config) ? $config->nome : old('nome') }}}">
											</div>

											@if($errors->has('nome'))
											<div class="invalid-feedback">
												{{ $errors->first('nome') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-9 col-10">
											<label class="col-form-label">Descrição</label>
											<div class="">
												<input id="descricao" type="text" class="form-control @if($errors->has('descricao')) is-invalid @endif" name="descricao" value="{{{ isset($config) ? $config->descricao : old('descricao') }}}">
											</div>
											@if($errors->has('descricao'))
											<div class="invalid-feedback">
												{{ $errors->first('descricao') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-12 col-lg-6 col-12">
											<label class="col-form-label">Rua</label>
											<div class="">
												<input id="rua" type="text" class="form-control @if($errors->has('rua')) is-invalid @endif" name="rua" value="{{{ isset($config) ? $config->rua : old('rua') }}}">
												@if($errors->has('rua'))
												<div class="invalid-feedback">
													{{ $errors->first('rua') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-12 col-lg-2 col-12">
											<label class="col-form-label">Número</label>
											<div class="">
												<input id="numero" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" value="{{{ isset($config) ? $config->numero : old('numero') }}}">
												@if($errors->has('numero'))
												<div class="invalid-feedback">
													{{ $errors->first('numero') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-12 col-lg-4 col-12">
											<label class="col-form-label">Bairro</label>
											<div class="">
												<input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" value="{{{ isset($config) ? $config->bairro : old('bairro') }}}">
												@if($errors->has('bairro'))
												<div class="invalid-feedback">
													{{ $errors->first('bairro') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-12 col-lg-2 col-12">
											<label class="col-form-label">CEP</label>
											<div class="">
												<input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif" name="cep" value="{{{ isset($config) ? $config->cep : old('cep') }}}">
												@if($errors->has('cep'))
												<div class="invalid-feedback">
													{{ $errors->first('cep') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3 col-10">
											<label class="col-form-label">Cidade</label>
											<div class="">
												<select name="cidade_id" class="custom-select @if($errors->has('cidade_id')) is-invalid @endif">
													<option value="">--</option>
													@foreach($cidades as $c)
													<option 
													@isset($config)
													@if($c->id == $config->cidade_id)
													selected
													@endif
													@else
													@if(old('cidade_id') == $c->id)
													selected
													@endif
													@endif
													value="{{$c->id}}">{{$c->nome}} ({{$c->uf}})</option>
													@endforeach
												</select>
											</div>
											@if($errors->has('cidade_id'))
											<div class="invalid-feedback">
												{{ $errors->first('cidade_id') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-2 col-12">
											<label class="col-form-label">Celular</label>
											<div class="">
												<input id="telefone" type="text" class="form-control @if($errors->has('telefone')) is-invalid @endif" name="telefone" value="{{{ isset($config) ? $config->telefone : old('telefone') }}}">
												@if($errors->has('telefone'))
												<div class="invalid-feedback">
													{{ $errors->first('telefone') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3 col-6">
											<label class="col-form-label">Tempo Medio de Entrega (Min)</label>
											<div class="">
												<input data-mask="000" id="tempo_medio_entrega" type="text" class="form-control @if($errors->has('tempo_medio_entrega')) is-invalid @endif" name="tempo_medio_entrega" value="{{{ isset($config) ? $config->tempo_medio_entrega : old('tempo_medio_entrega') }}}">
												@if($errors->has('tempo_medio_entrega'))
												<div class="invalid-feedback">
													{{ $errors->first('tempo_medio_entrega') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3 col-6">
											<label class="col-form-label">Valor de Entrega Padrão</label>
											<div class="">
												<input id="valor_entrega" type="text" class="form-control @if($errors->has('valor_entrega')) is-invalid @endif" name="valor_entrega" value="{{{ isset($config) ? $config->valor_entrega : old('valor_entrega') }}}">
												@if($errors->has('valor_entrega'))
												<div class="invalid-feedback">
													{{ $errors->first('valor_entrega') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2 col-6">
											<label class="col-form-label">Pedido mínimo</label>
											<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Se definido como zero, não haverá valor de pedido minímo!"><i class="la la-info"></i></button>
											<div class="">
												<input id="pedido_minimo" type="text" class="form-control @if($errors->has('pedido_minimo')) is-invalid @endif money" name="pedido_minimo" value="{{{ isset($config) ? $config->pedido_minimo : old('pedido_minimo') }}}">
												@if($errors->has('pedido_minimo'))
												<div class="invalid-feedback">
													{{ $errors->first('pedido_minimo') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-4 col-lg-2 col-6">
											<label class="col-form-label">Máximo de adicionais</label>
											<div class="">
												<input id="maximo_adicionais" type="text" class="form-control @if($errors->has('maximo_adicionais')) is-invalid @endif" name="maximo_adicionais" value="{{{ isset($config) ? $config->maximo_adicionais : old('maximo_adicionais') }}}" data-mask="00">
												@if($errors->has('maximo_adicionais'))
												<div class="invalid-feedback">
													{{ $errors->first('maximo_adicionais') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-4 col-lg-3 col-6">
											<label class="col-form-label">Máximo de adicionais pizza</label>
											<div class="">
												<input id="maximo_adicionais_pizza" type="text" class="form-control @if($errors->has('maximo_adicionais_pizza')) is-invalid @endif" name="maximo_adicionais_pizza" value="{{{ isset($config) ? $config->maximo_adicionais_pizza : old('maximo_adicionais_pizza') }}}" data-mask="00">
												@if($errors->has('maximo_adicionais_pizza'))
												<div class="invalid-feedback">
													{{ $errors->first('maximo_adicionais_pizza') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3 col-6">
											<label class="col-form-label">Máximo sabores de pizza</label>
											<div class="">
												<input id="maximo_sabores_pizza" type="text" class="form-control @if($errors->has('maximo_sabores_pizza')) is-invalid @endif" name="maximo_sabores_pizza" value="{{{ isset($config) ? $config->maximo_sabores_pizza : old('maximo_sabores_pizza') }}}" data-mask="0">
												@if($errors->has('maximo_sabores_pizza'))
												<div class="invalid-feedback">
													{{ $errors->first('maximo_sabores_pizza') }}
												</div>
												@endif
											</div>
										</div>


										<div class="form-group validated col-sm-4 col-lg-3 col-6">
											<label class="col-form-label">Tipo divisão pizza</label>
											<div class="">
												<select class="form-control" name="tipo_divisao_pizza">
													<option @isset($config) @if($config->tipo_divisao_pizza == 1) selected @endif @endif value="1">Divide valor</option>
													<option @isset($config) @if($config->tipo_divisao_pizza == 0) selected @endif @endif value="0">Maior valor</option>
												</select>
												@if($errors->has('tipo_divisao_pizza'))
												<div class="invalid-feedback">
													{{ $errors->first('tipo_divisao_pizza') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3 col-10">
											<label class="col-form-label">Tempo para cancelamento HH:mm</label>
											<div class="">
												<input data-mask="00:00" data-mask-reverse="true" id="tempo_maximo_cancelamento" type="text" class="form-control @if($errors->has('tempo_maximo_cancelamento')) is-invalid @endif" name="tempo_maximo_cancelamento" value="{{{ isset($config) ? $config->tempo_maximo_cancelamento : old('tempo_maximo_cancelamento') }}}">
												@if($errors->has('tempo_maximo_cancelamento'))
												<div class="invalid-feedback">
													{{ $errors->first('tempo_maximo_cancelamento') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3 col-10">
											<label class="col-form-label">Tipo de entrega</label>
											<div class="">
												<select name="tipo_entrega" class="custom-select @if($errors->has('tipo_entrega')) is-invalid @endif">
													<option value="">--</option>
													<option @isset($config) @if($config->tipo_entrega == 'balcao_delivery') selected @endif @endif value="balcao_delivery">Balcão e delivery</option>
													<option @isset($config) @if($config->tipo_entrega == 'balcao') selected @endif @endif value="balcao">Somente balcão</option>
													<option @isset($config) @if($config->tipo_entrega == 'delivery') selected @endif @endif value="delivery">Somente delivery</option>
												</select>
											</div>
											@if($errors->has('tipo_entrega'))
											<div class="invalid-feedback">
												{{ $errors->first('tipo_entrega') }}
											</div>
											@endif
										</div>

										<div class="col-sm-3 col-lg-2 mt-4">
											<label>Notificação novo pedido</label>

											<div class="switch switch-outline switch-primary">
												<label class="">
													<input @if(isset($config->notificacao_novo_pedido) && $config->notificacao_novo_pedido) checked @endisset value="true" name="notificacao_novo_pedido" class="red-text" type="checkbox">
													<span class="lever"></span>
												</label>
											</div>
										</div>

										<div class="col-sm-3 col-lg-2 mt-4">
											<label>Autenticação cadastro SMS</label>

											<div class="switch switch-outline switch-dark">
												<label class="">
													<input @if(isset($config->autenticacao_sms) && $config->autenticacao_sms) checked @endisset value="true" name="autenticacao_sms" class="red-text" type="checkbox">
													<span class="lever"></span>
												</label>
											</div>
										</div>

										<div class="col-sm-3 col-lg-2 mt-4">
											<label>Confirmação pedido cliente</label>

											<div class="switch switch-outline switch-info">
												<label class="">
													<input @if(isset($config->confirmacao_pedido_cliente) && $config->confirmacao_pedido_cliente) checked @endisset value="true" name="confirmacao_pedido_cliente" class="red-text" type="checkbox">
													<span class="lever"></span>
												</label>
											</div>
										</div>
										

										<div class="form-group validated col-sm-6 col-lg-4 col-12">
											<label class="col-form-label">Mercado pago public key</label>
											<div class="">
												<input id="mercadopago_public_key" type="text" class="form-control @if($errors->has('mercadopago_public_key')) is-invalid @endif" name="mercadopago_public_key" value="{{{ isset($config) ? $config->mercadopago_public_key : old('mercadopago_public_key') }}}">
												@if($errors->has('mercadopago_public_key'))
												<div class="invalid-feedback">
													{{ $errors->first('mercadopago_public_key') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-6 col-12">
											<label class="col-form-label">Mercado pago access token</label>
											<div class="">
												<input id="mercadopago_access_token" type="text" class="form-control @if($errors->has('mercadopago_access_token')) is-invalid @endif" name="mercadopago_access_token" value="{{{ isset($config) ? $config->mercadopago_access_token : old('mercadopago_access_token') }}}">
												@if($errors->has('mercadopago_access_token'))
												<div class="invalid-feedback">
													{{ $errors->first('mercadopago_access_token') }}
												</div>
												@endif
											</div>
										</div>

										<!-- <div class="form-group validated col-sm-4 col-lg-2 col-6">
											<label class="col-form-label">Latitude</label>
											<div class="">
												<input id="latitude" type="text" class="form-control @if($errors->has('latitude')) is-invalid @endif" name="latitude" value="{{{ isset($config) ? $config->latitude : old('latitude') }}}">
												@if($errors->has('latitude'))
												<div class="invalid-feedback">
													{{ $errors->first('latitude') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2 col-6">
											<label class="col-form-label">Longitude</label>
											<div class="">
												<input id="longitude" type="text" class="form-control @if($errors->has('longitude')) is-invalid @endif" name="longitude" value="{{{ isset($config) ? $config->longitude : old('longitude') }}}">
												@if($errors->has('longitude'))
												<div class="invalid-feedback">
													{{ $errors->first('longitude') }}
												</div>
												@endif
											</div>
										</div> -->

										<!-- <div class="form-group validated col-sm-4 col-lg-4 col-6">
											<label class="col-form-label">One Signal App ID</label>
											<div class="">
												<input id="one_signal_app_id" type="text" class="form-control @if($errors->has('one_signal_app_id')) is-invalid @endif" name="one_signal_app_id" value="{{{ isset($config) ? $config->one_signal_app_id : old('one_signal_app_id') }}}">
												@if($errors->has('one_signal_app_id'))
												<div class="invalid-feedback">
													{{ $errors->first('one_signal_app_id') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-4 col-lg-4 col-6">
											<label class="col-form-label">One Signal Key</label>
											<div class="">
												<input id="one_signal_key" type="text" class="form-control @if($errors->has('one_signal_key')) is-invalid @endif" name="one_signal_key" value="{{{ isset($config) ? $config->one_signal_key : old('one_signal_key') }}}">
												@if($errors->has('one_signal_key'))
												<div class="invalid-feedback">
													{{ $errors->first('one_signal_key') }}
												</div>
												@endif
											</div>
										</div> -->
									</div>

									<div class="row">
										<div class="form-group validated col-sm-12 col-lg-12 col-12">
											<label class="col-form-label">Politica de privacidade</label>
											<div class="">
												<textarea class="form-control" name="politica_privacidade" placeholder="Politica de privacidade" rows="3">{{{ isset($config->politica_privacidade) ? $config->politica_privacidade : old('politica_privacidade') }}}</textarea>
												@if($errors->has('politica_privacidade'))
												<div class="invalid-feedback">
													{{ $errors->first('politica_privacidade') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-5 col-lg-5 col-6">
											<label class="col-form-label">Api token</label>
											<div class="input-group">
												
												<input readonly id="api_token" type="text" class="form-control @if($errors->has('api_token')) is-invalid @endif" name="api_token" value="{{{ isset($config) ? $config->api_token : old('api_token') }}}">
												<div class="input-group-prepend">
													<span class="input-group-text" id="btn_token">
														<li class="la la-refresh"></li>
													</span>
													<span class="input-group-text bg-danger text-white" id="btn_clear_token">
														<li class="la la-close"></li>
													</span>
												</div>
												@if($errors->has('api_token'))
												<div class="invalid-feedback">
													{{ $errors->first('api_token') }}
												</div>
												@endif

											</div>
										</div>
									</div>


									<div class="row">
										<div class="col-8">
											<label class="col-form-label">
												Tipos de pagamento a serem mostrados
											</label>
											<div class="" style="display: grid;grid-template-columns: 1fr 1fr 1fr;">
												@foreach(App\Models\DeliveryConfig::tiposPagamento() as $key => $t)
												<label>
													<input  type="checkbox" name="tipos_pagamento[]" value="{{$key}}" @if($config != null) @if(sizeof($config->tipos_pagamento) > 0 && in_array($key, $config->tipos_pagamento)) checked="true" @endif @endif>
													{{$t}}
												</label>
												@endforeach
											</div>
										</div>
										<div class="form-group validated col-sm-4 col-lg-4 col-12">
											<label class="col-xl-12 col-lg-12 col-form-label text-left">Logo 60x60</label>
											<div class="col-lg-10 col-xl-6">

												<div class="image-input image-input-outline" id="kt_image_1">
													@isset($config)
													<div class="image-input-wrapper" style="background-image: url(/delivery/logos/{{$config->logo}})"></div>
													@else
													<div class="image-input-wrapper" style="background-image: url(/images/logo.png)"></div>
													@endif
													<label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="change" data-toggle="tooltip" title="" data-original-title="Change avatar">
														<i class="fa fa-pencil icon-sm text-muted"></i>
														<input type="file" name="file" accept=".png">
														<input type="hidden" name="profile_avatar_remove">
													</label>
													<span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="cancel" data-toggle="tooltip" title="" data-original-title="Cancel avatar">
														<i class="fa fa-close icon-xs text-muted"></i>
													</span>
												</div>

												<span class="form-text text-muted">.png</span>
												@if($errors->has('file'))
												<div class="invalid-feedback">
													{{ $errors->first('file') }}
												</div>
												@endif
											</div>
										</div>
									</div>

								</div>
							</div>
						</div>
					</div>
					<div class="card-footer">
						<div class="row">
							<div class="col-xl-2">

							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/clientes">
									<i class="la la-close"></i>
									<span class="">Cancelar</span>
								</a>
							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<button style="width: 100%" type="submit" class="btn btn-success">
									<i class="la la-check"></i>
									<span class="">Salvar</span>
								</button>
							</div>

						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-map" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<form method="post" action="/configDelivery/saveCoords" id="form-coords">
		@csrf
		<div class="modal-dialog modal-xl" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Deslize o pino até sua localização!</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						x
					</button>
				</div>
				<div class="modal-body">
					<input type="hidden" name="lat" id="lat">
					<input type="hidden" name="lng" id="lng">
					<div id="map"></div>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="submit" id="btn-inut-2" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Pronto</button>
				</div>
			</div>
		</div>
	</form>
</div>

@endsection

@section('javascript')
<script src="https://maps.googleapis.com/maps/api/js?key={{env('API_KEY_MAPS')}}"
async defer></script>
<script type="text/javascript">

	$('[data-toggle="popover"]').popover()

	$(function(){
		let lng = $('.lng').html()
		let lat = $('.lat').html()
		if(lng && lat){
			initMap(lat, lng);
		}else{
			getCurrentLocation((crd) => {
				if(crd){
					initMap(crd.latitude, crd.longitude);
				}else{
					swal("Atenção!", 'Não foi possivel recuperar sua localização, ative e recarregue a pagina!', "warning")
				}
			})
		}
	})

	function getCurrentLocation(call){
		var options = {
			enableHighAccuracy: true,
			timeout: 5000,
			maximumAge: 0
		};

		function success(pos) {
			var crd = pos.coords;
			call(crd);
		};

		function error(err) {
			console.warn('ERROR(' + err.code + '): ' + err.message);
			call(false)
		};

		navigator.geolocation.getCurrentPosition(success, error, options);
	}

	function initMap(lat, lng){

		$('#lat').val(lat)
		$('#lng').val(lng)
		const position = new google.maps.LatLng(lat, lng);

		var map = new google.maps.Map(document.getElementById('map'), {
			zoom: 16,
			center: position,
			disableDefaultUI: false
		});	


		const marker = new google.maps.Marker({
			position: position,
			map: map,
			animation: google.maps.Animation.BOUNCE,
			draggable: true
		})

		// getEnderecoByCoords(lat, lng, (res) => {
		// 	if(res == false){

		// 	}else{
		// 		$('#rua').val(res.rua)
		// 		$('#numero').val(res.numero)
		// 		validaCamposNovoEndereco();
		// 	}
		// })

		google.maps.event.addListener(marker, 'dragend', (event) => {
			var myLatLng = event.latLng;
			var lat = myLatLng.lat();
			var lng = myLatLng.lng();

			$('#lat').val(lat)
			$('#lng').val(lng)
		})

	}

	$('#btn_clear_token').click(() => {
		swal({
			title: "Atenção",
			text: "Deseja desetivar a API?",
			icon: "warning",
			buttons: true,
			dangerMode: true,
		}).then((confirmed) => {
			if (confirmed) {
				$('#api_token').val('')
			}
		});
	});

	$('#btn_token').click(() => {
		let token = generate_token(25);
		swal({
			title: "Atenção",
			text: "Esse token é o responsavel pela comunicação com o ecommerce, tenha atenção!!",
			icon: "warning",
			buttons: true,
			dangerMode: true,
		}).then((confirmed) => {
			if (confirmed) {
				$('#api_token').val(token)
			}
		});
	})

	function generate_token(length){
		var a = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890".split("");
		var b = [];  
		for (var i=0; i<length; i++) {
			var j = (Math.random() * (a.length-1)).toFixed(0);
			b[i] = a[j];
		}
		return b.join("");
	}
</script>
@endsection