@extends('default.layout')
@section('css')
@unless(config('ui_theme.active', false))
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
@endunless
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
@endpush
@section('content')
<div class="ui-admin ui-admin-empresas">
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="ui-admin-panel gutter-b ">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<!--begin::Portlet-->

				<form method="post" action="/empresas/alterarSenha">

					<input type="hidden" name="id" value="{{$empresa->id}}">
					<div class="ui-admin-panel gutter-b ">
						<div class="ui-admin-panel-head">

							<h3 class="card-title">Alterar senha dos usuários da empresa: <strong style="margin-left: 2px;">{{$empresa->nome}}</strong></h3>
						</div>

					</div>
					@csrf

					<div class="row">
						<div class="col-xl-2"></div>
						<div class="col-xl-8">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

										<div class="form-group validated col-12">
									<div class="row">

											<h4 class="ml-3 col-12">Usuários:</h4>
											@foreach($empresa->usuarios as $key => $u)
											<div class="col-sm-4 col-lg-4 col-md-4 col-12">

												<div class="ui-admin-panel gutter-b">
													<div class="ui-admin-panel-head">
														<h3 class="card-title">
															{{$u->nome}}
														</h3>
													</div>
													<div class="ui-admin-panel-body" style="height: 150px;">

														<h4>Login: <strong class="text-info">{{$u->login}}</strong></h4>
														<h4>ADM: 
															@if($u->adm)
															<span class="label label-xl label-inline label-light-success">SIM</span>
															@else
															<span class="label label-xl label-inline label-light-danger">NÃO</span>
															@endif
														</h4>

														<h4>Ativo: 
															@if($u->ativo)
															<span class="label label-xl label-inline label-light-success">SIM</span>
															@else
															<span class="label label-xl label-inline label-light-danger">NÃO</span>
															@endif
														</h4>

													</div>
												</div>
											</div>
											@endforeach


										</div>
									</div>
									
									<div class="row">
										<div class="form-group validated col-sm-5 col-lg-5">
											<label class="col-form-label">Nova senha</label>
											<div class="">
												<input required id="nome" type="password" class="form-control @if($errors->has('nome')) is-invalid @endif" name="senha" value="">
												
											</div>
										</div>
									</div>

								</div>

							</div>
						</div>
					</div>

					<div class="ui-admin-panel-foot">

						<div class="row">
							<div class="col-xl-2">

							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/empresas">
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