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
<div class="ui-admin ui-commerce ui-commerce-tamanhoPizza">

<div class="d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="ui-admin-panel ui-commerce-panel gutter-b">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>

				<form method="post" action="{{{ isset($tamanho) ? '/tamanhosPizza/update': '/tamanhosPizza/save' }}}">
					<input type="hidden" name="id" value="{{{ isset($tamanho->id) ? $tamanho->id : 0 }}}">
					<div class="ui-admin-panel ui-commerce-panel gutter-b">
						<div class="ui-admin-panel-head ui-commerce-panel-head">

							<h3 class="ui-commerce-title">{{isset($tamanho) ? 'Editar' : 'Novo'}} Tamanho de Pizza</h3>
						</div>

					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="ui-commerce-section">
								<div class="ui-commerce-section-body">

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-6">
											<label class="col-form-label">Nome</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($tamanho) ? $tamanho->nome : old('nome') }}}">
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>
									
										<div class="form-group validated col-sm-4 col-lg-2 col-6">
											<label class="col-form-label">Pedaços</label>
											<div class="">
												<input data-mask="00" type="text" class="form-control @if($errors->has('pedacos')) is-invalid @endif" name="pedacos" value="{{{ isset($tamanho) ? $tamanho->pedacos : old('pedacos') }}}">
												@if($errors->has('pedacos'))
												<div class="invalid-feedback">
													{{ $errors->first('pedacos') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-4 col-lg-2 col-6">
											<label class="col-form-label">Maximo de sabores</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('maximo_sabores')) is-invalid @endif" name="maximo_sabores" value="{{{ isset($tamanho) ? $tamanho->maximo_sabores : old('maximo_sabores') }}}">
												@if($errors->has('maximo_sabores'))
												<div class="invalid-feedback">
													{{ $errors->first('maximo_sabores') }}
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
								<a style="width: 100%" class="btn btn-danger" href="/tamanhosPizza">
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