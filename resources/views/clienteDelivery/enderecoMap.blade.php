@extends('default.layout')
@section('css')
@unless(config('ui_theme.active', false))
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-commerce.css') }}?v={{ filemtime(public_path('css/ui-commerce.css')) }}">
@endunless
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-commerce.css') }}?v={{ filemtime(public_path('css/ui-commerce.css')) }}">
@endpush
@section('content')
<div class="ui-admin ui-commerce ui-commerce-clienteDelivery">
<style type="text/css">
#map{
	width: 100%;
	height: 600px;
	background: #999;
	margin-bottom: 50px;
}
</style>

<div class="ui-admin-panel ui-commerce-panel gutter-b">
	<div class="ui-admin-panel-body ui-commerce-panel-body">

		<div class="ui-admin-panel ui-commerce-panel gutter-b">


			<div class="ui-admin-panel-body ui-commerce-panel-body">
				<div class="row">
					<div class="col-sm-6 col-lg-6 col-md-6 col-xl-6 col-12">
						<h5>Latitude Local: <strong>{{$config->latitude}}</strong></h5>
						<h5>Longitude Local: <strong>{{$config->longitude}}</strong></h5>

						<input type="hidden" value="{{$config->latitude}}" id="lat_local">
						<input type="hidden" value="{{$config->longitude}}" id="lng_local">
					</div>
					<div class="col-sm-6 col-lg-6 col-md-6 col-xl-6 col-12">
						<h5>Latitude Cliente: <strong>{{$endereco->latitude}}</strong></h5>
						<h5>Longitude Cliente: <strong>{{$endereco->longitude}}</strong></h5>
						<input type="hidden" value="{{$endereco->latitude}}" id="lat_cliente">
						<input type="hidden" value="{{$endereco->longitude}}" id="lng_cliente">
					</div>

					<div class="col-sm-6 col-lg-6 col-md-6 col-xl-6 col-12">
						<h4>Distância: <strong class="cyan-text" id="distancia"></strong></h4>
						<h4>Duração percurso: <strong class="cyan-text" id="duracao"></strong></h4>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div id="map"></div>
		</div>
	</div>
</div>

</div>
@endsection