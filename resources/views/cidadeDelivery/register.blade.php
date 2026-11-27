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
<div class="ui-admin ui-commerce ui-commerce-cidadeDelivery">

<div class="d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="ui-admin-panel ui-commerce-panel gutter-b">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{{ isset($cidade) ? '/cidadeDelivery/update': '/cidadeDelivery/save' }}}">

					<input type="hidden" name="id" value="{{{ isset($cidade->id) ? $cidade->id : 0 }}}">

					<div class="ui-admin-panel ui-commerce-panel gutter-b">
						<div class="ui-admin-panel-head ui-commerce-panel-head">

							<h3 class="ui-commerce-title">{{isset($cidade) ? 'Editar' : 'Nova'}} Cidade</h3>
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
												<input type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($cidade) ? $cidade->nome : old('nome') }}}">
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">CEP</label>
											<div class="">
												<input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif" name="cep" value="{{{ isset($cidade) ? $cidade->cep : old('cep') }}}">
											</div>
											@if($errors->has('cep'))
											<div class="invalid-feedback">
												{{ $errors->first('cep') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">UF</label>
											<select name="uf" class="custom-select @if($errors->has('uf')) is-invalid @endif">
												<option value="">--</option>
												@foreach(App\Models\Cidade::estados() as $e)
												<option value="{{$e}}">{{$e}}</option>
												@endforeach
											</select>
											@if($errors->has('uf'))
											<div class="invalid-feedback">
												{{ $errors->first('uf') }}
											</div>
											@endif
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