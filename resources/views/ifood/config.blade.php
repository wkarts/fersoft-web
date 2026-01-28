@extends('default.layout')
@section('content')

<style type="text/css">
	.img-template img{
		width: 300px;
		border: 1px solid #999;
		border-radius: 10px;
	}

	.img-template-active img{
		width: 300px;
		border: 3px solid green;
		border-radius: 10px;
	}

	.template:hover{
		cursor: pointer;
	}

	#btn_token:hover{
		cursor: pointer;
	}
</style>
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/ifood/configSave">
					<input type="hidden" name="id" value="{{{ isset($item->id) ? $item->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{{ isset($item) ? "Editar": "Cadastrar" }}} Configuração iFood</h3>
						</div>
					</div>
					@csrf
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<div class="row">
										<div class="form-group validated col-sm-5 col-lg-5 col-12">
											<label class="col-form-label">Client ID</label>
											<div class="">
												<input id="clientId" type="text" class="form-control @if($errors->has('clientId')) is-invalid @endif" name="clientId" value="{{{ isset($item) ? $item->clientId : old('clientId') }}}">
												@if($errors->has('clientId'))
												<div class="invalid-feedback">
													{{ $errors->first('clientId') }}
												</div>
												@endif
												<p class="text-danger">Exemplo e5ba1fdd-cf33-4a3b-b5f8-058eb7d4241f</p>
											</div>
										</div>

										<div class="form-group validated col-sm-7 col-lg-7 col-12">
											<label class="col-form-label">Client secret</label>
											<div class="">
												<input id="clientSecret" type="text" class="form-control @if($errors->has('clientSecret')) is-invalid @endif" name="clientSecret" value="{{{ isset($item) ? $item->clientSecret : old('clientSecret') }}}">
												@if($errors->has('clientSecret'))
												<div class="invalid-feedback">
													{{ $errors->first('clientSecret') }}
												</div>
												@endif
												<p class="text-danger">Exemplo jdrlin992ydkhaocp6my08gt4v8cx3fe0n3s700nit0kn91czi394zj3yn4mnx037ta3b86f5huxawt2sevueir73dxlls8yqq9</p>
											</div>
										</div>

										<div class="form-group validated col-sm-7 col-lg-7 col-12">
											<label class="col-form-label">ID da Loja</label>
											<div class="">
												<input id="merchantId" type="text" class="form-control @if($errors->has('merchantId')) is-invalid @endif" name="merchantId" value="{{{ isset($item) ? $item->merchantId : old('merchantId') }}}">
												@if($errors->has('merchantId'))
												<div class="invalid-feedback">
													{{ $errors->first('merchantId') }}
												</div>
												@endif
												<p class="text-danger">Exemplo 3fa85f64-5717-4562-b3fc-2c963f66afa6</p>
											</div>
										</div>

										@if(isset($item) && $item->userCode != "")
										<div class="form-group validated col-sm-5 col-lg-3 col-12">
											<label class="col-form-label">AuthorizationCode</label>
											<div class="">
												<input id="authorizationCode" type="text" class="form-control @if($errors->has('authorizationCode')) is-invalid @endif" name="authorizationCode" value="{{{ isset($item) ? $item->authorizationCode : old('authorizationCode') }}}">
												@if($errors->has('authorizationCode'))
												<div class="invalid-feedback">
													{{ $errors->first('authorizationCode') }}
												</div>
												@endif

											</div>
										</div>
										@endif

										
									</div>

									@if($item != null)
									@if($item->userCode != "")
									<div class="row">
										<div class="form-group validated col-12">
											<span>userCode: <strong>{{ $item->userCode }}</strong></span><br>
											<span>authorizationCodeVerifier: <strong>{{ $item->authorizationCodeVerifier }}</strong></span><br>
											<span>verificationUrlComplete: <a href="{{ $item->verificationUrlComplete }}" target="_blank">{{ $item->verificationUrlComplete }}</a></span>
											<br>

											@if($item->authorizationCode != "")
											@if($item->accessToken == "")
											<a href="/ifood/getToken" class="btn btn-success">
												Gerar Novo Token
											</a>
											@else
											<a href="/ifood/getToken" class="btn btn-info">
												Atualizar Token
											</a>
											@endif
											@endif

											@if($item->accessToken != "")

											<h6 class="mt-2">accessToken: <strong>{{ $item->accessToken }}</strong></h6>
											@endif

										</div>
									</div>
									@endif

									<div class="row">
										<div class="form-group validated col-12">
											
											<a href="/ifood/userCode" class="btn btn-warning">
												Gerar Novo Código de Usuário
											</a>
											
										</div>
									</div>
									@endif


									
								</div>
							</div>
						</div>

					</div>
					<div class="card-footer">
						<div class="row">
							<div class="col-xl-2">

							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/nuvemshop/config">
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

@section('javascript')
<script type="text/javascript">

</script>
@endsection
@endsection