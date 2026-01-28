@extends('default.layout')

@section('css')
<style type="text/css">
	body.loading .modal-loading {
		display: block;
	}

	.modal-loading {
		display: none;
		position: fixed;
		z-index: 10000;
		top: 0;
		left: 0;
		height: 100%;
		width: 100%;
		background: rgba(255, 255, 255, 0.8)
		url("/loading.gif") 50% 50% no-repeat;
	}

</style>
@endsection
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/config/save" enctype="multipart/form-data">

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">Configuração do sistema</h3>
						</div>
					</div>
					@csrf

					<!-- <a target="_blank" class="btn btn-danger btn-lg btn-bk" href="/backup">
						<i class="la la-save"></i>
						Backup banco de dados e código
					</a> -->
					<a target="_blank" class="btn btn-warning btn-lg btn-bk" href="/backupSql">
						<i class="la la-save"></i>
						Backup banco de dados
					</a>

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-6 col-lg-3">
											<label class="col-form-label">Cor padrão</label>
											<div class="">
												<input type="color" class="form-control @if($errors->has('nome')) is-invalid @endif" name="cor" value="{{{ isset($item) ? $item->cor : old('cor') }}}">
												@if($errors->has('cor'))
												<div class="invalid-feedback">
													{{ $errors->first('cor') }}
												</div>
												@endif
											</div>
											@if($item != null)
											<a href="/config/remove-cor">remover cor</a>
											@endif
										</div>

										
									</div>
									<div class="row">

										<div class="form-group validated col-sm-4 col-lg-4 col-6 use-api @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
											<label class="col-xl-12 col-lg-12 col-form-label text-left">Logo 1</label>
											<div class="col-lg-10 col-xl-6">

												<div class="image-input image-input-outline" id="kt_image_2">
													<div class="image-input-wrapper"
													style="background-image: url(/imgs/slym.png); width: 300px" ></div>
													<label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="change" data-toggle="tooltip" title="" data-original-title="Change avatar">
														<i class="fa fa-pencil icon-sm text-muted"></i>
														<input type="file" name="logo1" accept=".png">
														<input type="hidden" name="profile_avatar_remove">
													</label>
													<span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="cancel" data-toggle="tooltip" title="" data-original-title="Cancel avatar">
														<i class="fa fa-close icon-xs text-muted"></i>
													</span>
												</div>


												<span class="form-text text-muted">.png</span>
												@if($errors->has('logo1'))
												<div class="invalid-feedback">
													{{ $errors->first('logo1') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-4 col-6 use-api @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
											<label class="col-xl-12 col-lg-12 col-form-label text-left">Logo 2</label>
											<div class="col-lg-10 col-xl-6">

												<div class="image-input image-input-outline" id="kt_image_3">
													<div class="image-input-wrapper"
													style="background-image: url(/imgs/slym2.png); width: 400px; height: 300px" ></div>
													<label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="change" data-toggle="tooltip" title="" data-original-title="Change avatar">
														<i class="fa fa-pencil icon-sm text-muted"></i>
														<input type="file" name="logo2" accept=".png">
														<input type="hidden" name="profile_avatar_remove">
													</label>
													<span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="cancel" data-toggle="tooltip" title="" data-original-title="Cancel avatar">
														<i class="fa fa-close icon-xs text-muted"></i>
													</span>
												</div>


												<span class="form-text text-muted">.png</span>
												@if($errors->has('logo2'))
												<div class="invalid-feedback">
													{{ $errors->first('logo2') }}
												</div>
												@endif
											</div>
										</div>
									</div>

									<hr>
									<div class="row mb-5">
										<div class="col-12">
											<h4>Configuração para plano indeterminado</h4>

											<div class="row">
												<div class="col-lg-2">
													<label>Início da mensagem</label>
													<input value="{{{ isset($item) ? $item->inicio_mensagem_plano : old('inicio_mensagem_plano') }}}" name="inicio_mensagem_plano" data-mask="00" class="form-control">
												</div>
												<div class="col-lg-2">
													<label>Fim da mensagem</label>
													<input value="{{{ isset($item) ? $item->fim_mensagem_plano : old('fim_mensagem_plano') }}}" name="fim_mensagem_plano" data-mask="00" class="form-control">
												</div>
												<div class="col-lg-12">
													<label>Mensagem</label>
													<input value="{{{ isset($item) ? $item->mensagem_plano_indeterminado : old('mensagem_plano_indeterminado') }}}" type="text" name="mensagem_plano_indeterminado" class="form-control">
												</div>
											</div>
										</div>
									</div>

									<hr>
									<div class="row mb-5">
										<div class="col-12">
											<h4>Configuração para contrato</h4>

											<div class="row">
												<div class="col-lg-2">
													<label>Valor base</label>
													<input value="{{{ isset($item) ? moeda($item->valor_base_contrato) : old('valor_base_contrato') }}}" name="valor_base_contrato" class="form-control money">
												</div>
												
											</div>
										</div>
									</div>

									<hr>
									<div class="row mb-5">
										<div class="col-12">
											<h4>Configuração calculo dos correios</h4>

											<div class="row">
												<div class="col-lg-3">
													<label>Usuário</label>
													<input value="{{{ isset($item) ? $item->usuario_correios : old('usuario_correios') }}}" name="usuario_correios" class="form-control">
												</div>

												<div class="col-lg-5">
													<label>Código de accesso</label>
													<input value="{{{ isset($item) ? $item->codigo_acesso_correios : old('codigo_acesso_correios') }}}" name="codigo_acesso_correios" class="form-control">
												</div>
												
												<div class="col-lg-3">
													<label>Cartão postagem</label>
													<input value="{{{ isset($item) ? $item->cartao_postagem_correios : old('cartao_postagem_correios') }}}" name="cartao_postagem_correios" class="form-control">
												</div>
											</div>
										</div>
									</div>

									<hr>
									<div class="row mb-5">
										<div class="col-12">
											<h4>Configuração NFSe</h4>

											<div class="row">
												<div class="col-lg-12">
													<label>Token integra notas</label>
													<input value="{{{ isset($item) ? $item->token_integra_notas : old('token_integra_notas') }}}" name="token_integra_notas" class="form-control">
												</div>

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
								<a style="width: 100%" class="btn btn-danger" href="/config">
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

<div class="modal-loading loading-class"></div>

@endsection

@section('javascript')
<script type="text/javascript">
	$('.btn-bkp').click(() => {
		$body = $("body");
		$body.addClass("loading");

	})
</script>
@endsection