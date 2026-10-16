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
<div class="ui-admin ui-commerce ui-commerce-bairros_loja">

<div class="d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="ui-admin-panel ui-commerce-panel gutter-b">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{{ isset($bairro) ? '/bairrosDeliveryLoja/update': '/bairrosDeliveryLoja/save' }}}">

					<input type="hidden" name="id" value="{{{ isset($bairro->id) ? $bairro->id : 0 }}}">

					<div class="ui-admin-panel ui-commerce-panel gutter-b">
						<div class="ui-admin-panel-head ui-commerce-panel-head">

							<h3 class="ui-commerce-title">{{isset($bairro) ? 'Editar' : 'Novo'}} Bairro</h3>
						</div>

					</div>
					@csrf

					<div class="row">

						<div class="col-xl-12">
							<div class="ui-commerce-section">
								<div class="ui-commerce-section-body">

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-4">
											<label class="col-form-label">Nome</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($bairro) ? $bairro->nome : old('nome') }}}">
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label">Valor de Entrega</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('valor_entrega')) is-invalid @endif money" name="valor_entrega" value="{{{ isset($bairro) ? moeda($bairro->valor_entrega) : old('valor_entrega') }}}">
												@if($errors->has('valor_entrega'))
												<div class="invalid-feedback">
													{{ $errors->first('valor_entrega') }}
												</div>
												@endif
											</div>
										</div>

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
								<a style="width: 100%" class="btn btn-danger" href="/bairrosDelivery">
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
</div>
@endsection