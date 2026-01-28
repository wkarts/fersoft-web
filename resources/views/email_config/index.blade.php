@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>

				<form method="post" action="/configEmail/save" enctype="multipart/form-data">

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{{ isset($config) ? "Editar": "Cadastrar" }}} Email</h3>
						</div>
					</div>
					@csrf

					<div class="wizard wizard-3" id="kt_wizard_v3" data-wizard-state="between" data-wizard-clickable="true">

						<div class="row">
							<div class="col-xl-12">
								<div class="kt-section kt-section--first">
									<div class="kt-section__body">

										@if(env("SERVIDOR_WEB_TYPE") == "0")
										<p class="text-danger">*Atenção realize os teste de envio de email com a aplicação em servidor</p>
										@endif

										@if($empresa->usar_email_proprio)
										<p class="text-info">Atualmente sua configuração esta marcada para utilizar o email configurado nesta tela</p>

										@else
										<p class="text-info">Atualmente sua configuração esta marcada para utilizar o email configurado em nosso servidor {{env("MAIL_USERNAME")}}</p>
										@endif


										<div class="row">
											<div class="form-group validated col-sm-12 col-lg-3">
												<label class="col-form-label">Nome</label>
												<div class="">
													<input id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($config) ? $config->nome : old('nome') }}}">
													@if($errors->has('nome'))
													<div class="invalid-feedback">
														{{ $errors->first('nome') }}
													</div>
													@endif
												</div>
											</div>

											<div class="form-group validated col-sm-12 col-lg-3">
												<label class="col-form-label">Host</label>
												<div class="">
													<input id="host" type="text" class="form-control @if($errors->has('host')) is-invalid @endif" name="host" value="{{{ isset($config) ? $config->host : old('host') }}}">
													@if($errors->has('host'))
													<div class="invalid-feedback">
														{{ $errors->first('host') }}
													</div>
													@endif
												</div>
											</div>

											<div class="form-group validated col-sm-12 col-lg-3">
												<label class="col-form-label">Email</label>
												<div class="">
													<input id="email" type="email" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{{ isset($config) ? $config->email : old('email') }}}">
													@if($errors->has('email'))
													<div class="invalid-feedback">
														{{ $errors->first('email') }}
													</div>
													@endif
												</div>
											</div>

											<div class="form-group validated col-lg-3 col-md-3 col-sm-12">
												<label class="col-form-label">Senha</label>
												<div class="">
													<input id="senha" type="text" class="form-control @if($errors->has('senha')) is-invalid @endif" name="senha" value="{{{ isset($config) ? $config->senha : old('senha') }}}">
													@if($errors->has('senha'))
													<div class="invalid-feedback">
														{{ $errors->first('senha') }}
													</div>
													@endif
												</div>
											</div>

											<div class="form-group validated col-lg-2 col-md-3 col-sm-12">
												<label class="col-form-label">Porta</label>
												<div class="">
													<input id="porta" type="text" class="form-control @if($errors->has('porta')) is-invalid @endif" name="porta" value="{{{ isset($config) ? $config->porta : old('porta') }}}">
													@if($errors->has('porta'))
													<div class="invalid-feedback">
														{{ $errors->first('porta') }}
													</div>
													@endif
												</div>
											</div>


											<div class="form-group validated col-lg-2 col-md-3 col-sm-12">
												<label class="col-form-label">Cripitografia</label>
												<div class="">
													<select class="custom-select" name="cripitografia">
														<option @isset($config) @if($config->cripitografia == 'tls') selected @endif @endif value="tls">TLS</option>
														<option @isset($config) @if($config->cripitografia == 'ssl') selected @endif @endif value="ssl">SSL</option>
													</select>
												</div>
											</div>

											<div class="form-group validated col-sm-6 col-lg-2">
												<label class="col-form-label text-left col-lg-12 col-sm-12">Autenticação SMTP</label>
												<div class="col-6">
													<span class="switch switch-outline switch-primary">
														<label>
															<input value="true" @if(isset($config) && $config->smtp_auth) checked @endif type="checkbox" name="smtp_auth" id="smtp_auth">
															<span></span>
														</label>
													</span>
												</div>
											</div>

											<div class="form-group validated col-sm-6 col-lg-3">
												<label class="col-form-label text-left col-lg-12 col-sm-12">SMTP Debug</label>
												<div class="col-6">
													<span class="switch switch-outline switch-primary">
														<label>
															<input value="true" @if(isset($config) && $config->smtp_debug) checked @endif type="checkbox" name="smtp_debug" id="smtp_debug">
															<span></span>
														</label>
													</span>
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
					</div>
				</form>

				<form class="col-12" method="get" action="/configEmail/teste">
					<div class="row">

						<div class="form-group validated col-sm-12 col-lg-2">
							<br>
							<button type="button" id="test-email" class="btn btn-info mt-5">
								<i class="la la-envelope"></i> Testar email
							</button>
						</div>

						<div class="form-group validated col-sm-12 col-lg-4 mail d-none">
							<label class="col-form-label">Email de envio teste</label>
							<div class="">
								<input required name="email" type="email" class="form-control">
							</div>
						</div>

						<div class="form-group validated col-sm-12 col-lg-2 mail d-none">
							<br>
							<button type="submit" class="btn btn-success mt-5">
								<i class="la la-send"></i> Enviar
							</button>
						</div>

					</div>
				</form>
			</div>
		</div>
	</div>
</div>
@section('javascript')
<script type="text/javascript">
	$('[data-toggle="popover"]').popover()

	$('#test-email').click(() => {
		$('.mail').removeClass('d-none')
	})
</script>

@endsection
@endsection
