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
<div class="d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="ui-admin-panel ui-commerce-panel gutter-b">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>

				<form method="post" action="/clientesDelivery/update" enctype="multipart/form-data">
					<input type="hidden" name="id" value="{{$cliente->id}}">
					<div class="ui-admin-panel ui-commerce-panel gutter-b">
						<div class="ui-admin-panel-head ui-commerce-panel-head">

							<h3 class="ui-commerce-title">Editar cliente delivery</h3>
						</div>

					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="ui-commerce-section">
								<div class="ui-commerce-section-body">

									<div class="row">
										<div class="form-group validated col-sm-4 col-lg-2">
											<label class="col-form-label">Nome</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{$cliente->nome}}">
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-4 col-lg-2">
											<label class="col-form-label">Sobre nome</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('sobre_nome')) is-invalid @endif" name="sobre_nome" value="{{$cliente->sobre_nome}}">
												@if($errors->has('sobre_nome'))
												<div class="invalid-feedback">
													{{ $errors->first('sobre_nome') }}
												</div>
												@endif
											</div>
										</div>
									
										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label">Email</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{$cliente->email}}">
												@if($errors->has('email'))
												<div class="invalid-feedback">
													{{ $errors->first('email') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label">Celular</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('celular')) is-invalid @endif celular" name="celular" value="{{$cliente->celular}}">
												@if($errors->has('celular'))
												<div class="invalid-feedback">
													{{ $errors->first('celular') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label">CPF</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('cpf')) is-invalid @endif" name="cpf" value="{{$cliente->cpf}}">
												@if($errors->has('cpf'))
												<div class="invalid-feedback">
													{{ $errors->first('cpf') }}
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
								<a style="width: 100%" class="btn btn-danger" href="/clientesDelivery">
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