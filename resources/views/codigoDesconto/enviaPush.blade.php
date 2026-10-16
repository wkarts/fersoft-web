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
<div class="ui-admin ui-commerce ui-commerce-codigoDesconto">

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">

	<div class="container">
		<div class="ui-admin-panel ui-commerce-panel gutter-b">
			<div class="col-lg-12">
				<!--begin::Portlet-->

				<form class="col s12" method="post" action="/codigoDesconto/push">
					@csrf
					<input type="hidden" value="{{$cupom->id}}" name="cupom_id">
					<div class="ui-admin-panel ui-commerce-panel gutter-b">
						<div class="ui-admin-panel-head ui-commerce-panel-head">

							<h3 class="ui-commerce-title">Envio de Notificação Push App Delivery</h3>
						</div>

					</div>
					@if($cupom->push)
					<h4 class="text-danger">PUSH JÁ ENVIADO</h4>
					@endif

					<h6>Cupom: <strong class="text-info">{{$cupom->id}}</strong></h6>
					<h6>Código: <strong class="text-info">{{$cupom->codigo}}</strong></h6>
					<h6>Cliente: 
						@if($cupom->cliente_id != null)
						<strong class="text-info">{{$cupom->cliente->nome}}</strong>
						@else
						<strong class="text-info">Envio para todos os clientes</strong>
						@endif
					</h6>


					<div class="row">
						<div class="col-xl-2"></div>
						<div class="col-xl-8">
							<div class="ui-commerce-section">
								<div class="ui-commerce-section-body">
									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-6 col-12">
											<label class="col-form-label">Titulo</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('codigo')) is-invalid @endif" id="titulo" name="titulo" value="Oferta especial" name="titulo">
												@if($errors->has('titulo'))
												<div class="invalid-feedback">
													{{ $errors->first('titulo') }}
												</div>
												@endif
											</div>
										</div>
									</div>
									<div class="row">
										<div class="form-group validated col-sm-10 col-lg-10">
											<label class="col-form-label">Mensagem</label>
											<div class="">
												<textarea class="form-control" name="mensagem" placeholder="Descrição" rows="3">Utilize {{$cupom->codigo}} para desconto de @if($cupom->tipo == 'valor')R$@endif{{number_format($cupom->valor, 2)}}@if($cupom->tipo == 'percentual')%@endif, especial para você@if($cupom->cliente_id != null) {{$cupom->cliente->nome}}@endif!</textarea>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="ui-admin-panel-foot ui-commerce-panel-foot">

								<div class="row">
									<div class="col-xl-2">

									</div>
									<div class="col-lg-3 col-sm-6 col-md-4">
										<a style="width: 100%" class="btn btn-danger" href="/codigoDesconto">
											<i class="la la-close"></i>
											<span class="">Cancelar</span>
										</a>
									</div>
									<div class="col-lg-3 col-sm-6 col-md-4">
										<button style="width: 100%" type="submit" class="btn btn-success">
											<i class="la la-send"></i>
											<span class="">Enviar</span>
										</button>
									</div>

								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

</div>
@endsection	