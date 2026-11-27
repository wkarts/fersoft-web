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
<div class="ui-admin ui-commerce ui-commerce-categoriaDelivery">

<div class="ui-admin-panel ui-commerce-panel gutter-b">


	<div class="ui-admin-panel-body ui-commerce-panel-body">
		<div class="">

			<form method="post" action="/deliveryCategoria/saveAditional">
				@csrf
				<input type="hidden" id="categoria" name="categoria" value="{{$categoria->id}}">
				<div class="row align-items-center">
					<div class="form-group validated col-sm-6 col-lg-6 col-10">
						<label class="col-form-label" id="">Cliente</label><br>
						<select class="form-control select2" style="width: 100%" id="kt_select2_3" name="adicional">
							@foreach($adicionais as $a)
							<option value="{{$a->id}}">{{$a->nome()}} - R$ {{$a->valor}}</option>
							@endforeach
						</select>
					</div>
					<div class="col-lg-1 col-md-4 col-sm-6 col-2">
						<button href="#!" style="margin-top: 10px;" class="btn btn-light-success px-6 font-weight-bold">
							<i class="la la-plus"></i>
						</button>

					</div>
				</div>
			</form>

		</div>
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<br>
			<h3>Adicionais para categoria <strong class="text-info">{{$categoria->nome}}</strong></h3>
			<label>Numero de registros: {{count($categoria->adicionais)}}</label>					

			<div class="row">

				@foreach($categoria->adicionais as $c)


				<div class="col-sm-12 col-lg-6 col-md-6 col-xl-4">
					<div class="ui-admin-panel ui-commerce-panel gutter-b">
						<div class="ui-admin-panel-head ui-commerce-panel-head">

							<h3 class="ui-commerce-title" style="font-size: 12px;">{{ $c->complemento->id }} - {{ $c->complemento->nome() }}
							</h3>
							<div class="ui-commerce-tools">


								<a class="btn btn-icon btn-circle btn-sm btn-light-danger mr-1" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/deliveryCategoria/removeAditional/{{ $c->id }}" }else{return false} })' href="#!">
									<i class="la la-trash"></i>				
								</a>
								

							</div>
						</div>

					</div>

				</div>

				@endforeach

			</div>
		</div>
	</div>
</div>


</div>
@endsection	