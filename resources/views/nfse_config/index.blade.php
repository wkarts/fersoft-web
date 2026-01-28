@extends('default.layout', ['title' => 'Configuração da NFSe'])
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
				<form method="post" action="{{ $item != null ? route('nfse-config.update', [$item->id]) : route('nfse-config.store') }}" enctype="multipart/form-data">
					@csrf
					@if($item != null)
					@method('put')
					@endif

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{{ $item != null ? "Editar": "Cadastrar" }}} Configuração NFSe</h3>
						</div>
					</div>

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<div class="row">

										<div class="form-group validated col-sm-6 col-lg-2 col-12">
											<label class="col-form-label">Documento</label>
											<input required id="documento" type="text" class="form-control @if($errors->has('documento')) is-invalid @endif cpf_cnpj" name="documento" value="{{{ isset($item) ? $item->documento : old('documento') }}}">
											@if($errors->has('documento'))
											<div class="invalid-feedback">
												{{ $errors->first('documento') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-lg-4 col-12">
											<label class="col-form-label">Nome</label>
											<input required id="nome" type="text" class="form-control @if($errors->has('client_id')) is-invalid @endif" name="nome" value="{{{ isset($item) ? $item->nome : old('nome') }}}">
											@if($errors->has('nome'))
											<div class="invalid-feedback">
												{{ $errors->first('nome') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-4 col-12">
											<label class="col-form-label">Razão social</label>
											<input required id="razao_social" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif" name="razao_social" value="{{{ isset($item) ? $item->razao_social : old('razao_social') }}}">
											@if($errors->has('razao_social'))
											<div class="invalid-feedback">
												{{ $errors->first('razao_social') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-2 col-12">
											<label class="col-form-label">I.E</label>
											<input id="ie" type="text" class="form-control @if($errors->has('ie')) is-invalid @endif" name="ie" value="{{{ isset($item) ? $item->ie : old('ie') }}}">
											@if($errors->has('ie'))
											<div class="invalid-feedback">
												{{ $errors->first('ie') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-2 col-12">
											<label class="col-form-label">I.M</label>
											<input type="text" class="form-control @if($errors->has('im')) is-invalid @endif" name="im" value="{{{ isset($item) ? $item->im : old('im') }}}">
											@if($errors->has('im'))
											<div class="invalid-feedback">
												{{ $errors->first('im') }}
											</div>
											@endif
										</div>
										<div class="form-group validated col-sm-6 col-lg-2 col-12">
											<label class="col-form-label">CNAE</label>
											<input type="text" class="form-control @if($errors->has('cnae')) is-invalid @endif" name="cnae" value="{{{ isset($item) ? $item->cnae : old('cnae') }}}">
											@if($errors->has('cnae'))
											<div class="invalid-feedback">
												{{ $errors->first('cnae') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-2 col-12">
											<label class="col-form-label">Telefone</label>
											<input required type="tel" class="form-control @if($errors->has('telefone')) is-invalid @endif telefone" id="telefone" name="telefone" value="{{{ isset($item) ? $item->telefone : old('telefone') }}}">
											@if($errors->has('telefone'))
											<div class="invalid-feedback">
												{{ $errors->first('telefone') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-4 col-12">
											<label class="col-form-label">Rua</label>
											<input required type="text" class="form-control @if($errors->has('rua')) is-invalid @endif" name="rua" id="rua" value="{{{ isset($item) ? $item->rua : old('rua') }}}">
											@if($errors->has('rua'))
											<div class="invalid-feedback">
												{{ $errors->first('rua') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-2 col-12">
											<label class="col-form-label">Número</label>
											<input required type="tel" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" id="numero" value="{{{ isset($item) ? $item->numero : old('numero') }}}">
											@if($errors->has('numero'))
											<div class="invalid-feedback">
												{{ $errors->first('numero') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-3 col-12">
											<label class="col-form-label">Bairro</label>
											<input required type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" id="bairro" value="{{{ isset($item) ? $item->bairro : old('bairro') }}}">
											@if($errors->has('bairro'))
											<div class="invalid-feedback">
												{{ $errors->first('bairro') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-3 col-12">
											<label class="col-form-label">Complemento</label>
											<input type="text" class="form-control @if($errors->has('complemento')) is-invalid @endif" name="complemento" value="{{{ isset($item) ? $item->complemento : old('complemento') }}}">
										</div>

										<div class="form-group validated col-sm-6 col-lg-2 col-12">
											<label class="col-form-label">CEP</label>
											<input required type="tel" class="form-control @if($errors->has('cep')) is-invalid @endif cep" name="cep" id="cep" value="{{{ isset($item) ? $item->cep : old('cep') }}}">
											@if($errors->has('cep'))
											<div class="invalid-feedback">
												{{ $errors->first('cep') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-3 col-12">
											<label class="col-form-label">Email</label>
											<input required id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{{ isset($item) ? $item->email : old('email') }}}">
											@if($errors->has('email'))
											<div class="invalid-feedback">
												{{ $errors->first('email') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-lg-4 col-md-5 col-sm-10">
											<label class="col-form-label text-left">Cidade</label>
											<select required class="form-control select2 @if($errors->has('cidade_id')) is-invalid @endif" id="kt_select2_1" name="cidade_id">
												<option value="">Selecione a cidade</option>
												@foreach($cidades as $c)
												<option value="{{$c->id}}" @isset($item) @if($c->id == $item->cidade_id) selected @endif @endisset 
													@if(old('cidade_id') == $c->id)
													selected
													@endif
													>
													{{$c->nome}} ({{$c->uf}})
												</option>
												@endforeach
											</select>
											@if($errors->has('cidade_id'))
											<div class="invalid-feedback">
												{{ $errors->first('cidade_id') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-3 col-12">
											<label class="col-form-label">Login prefeitura</label>
											<input id="login_prefeitura" type="text" class="form-control @if($errors->has('login_prefeitura')) is-invalid @endif" name="login_prefeitura" value="{{{ isset($item) ? $item->login_prefeitura : old('login_prefeitura') }}}">
											@if($errors->has('login_prefeitura'))
											<div class="invalid-feedback">
												{{ $errors->first('login_prefeitura') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-3 col-12">
											<label class="col-form-label">Senha prefeitura</label>
											<input id="senha_prefeitura" type="text" class="form-control @if($errors->has('senha_prefeitura')) is-invalid @endif" name="senha_prefeitura" value="{{{ isset($item) ? $item->senha_prefeitura : old('senha_prefeitura') }}}">
											@if($errors->has('senha_prefeitura'))
											<div class="invalid-feedback">
												{{ $errors->first('senha_prefeitura') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-6 col-lg-2 col-12">
											<label class="col-form-label">Regime</label>
											<select class="custom-select" name="regime">
												<option value="simples">Simples</option>
												<option value="normal">Normal</option>
											</select>
										</div>


										<div class="form-group validated col-sm-4 col-lg-4 col-6">
											<label class="col-xl-12 col-lg-12 col-form-label text-left">Logo</label>
											<div class="col-lg-10 col-xl-6">

												<div class="image-input image-input-outline" id="kt_image_1">
													<div class="image-input-wrapper" @if(isset($item) && $item->logo != null)style="background-image: url(/logos/{{$item->logo}})" @else style="background-image: url(/imgs/logo.png)" @endif ></div>
													<label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="change" data-toggle="tooltip" title="" data-original-title="Change avatar">
														<i class="fa fa-pencil icon-sm text-muted"></i>
														<input type="file" name="file" accept=".jpg,.png">
														<input type="hidden" name="profile_avatar_remove">
													</label>
													<span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="cancel" data-toggle="tooltip" title="" data-original-title="Cancel avatar">
														<i class="fa fa-close icon-xs text-muted"></i>
													</span>
												</div>

												<span class="form-text text-muted">.jpg</span>
												@if($errors->has('logo'))
												<div class="invalid-feedback">
													{{ $errors->first('logo') }}
												</div>
												@endif

												<!-- @if($item != null && $item->logo != null)
												<a href="{{ route('nfse-config.remove-logo') }}">remover logo</a>
												@endif -->

											</div>

										</div>

										<div class="form-group validated col-sm-6 col-lg-12 col-12">
											<label class="col-form-label">Token do emitente</label>
											<input @if($item == null) readonly @endif class="form-control" type="text" name="token" value="{{ $item != null ? $item->token : '' }}">
										</div>

										@if($item != null && $item->token != null)
										<div class="col-lg-3 col-12 mb-2">
											<a href="{{ route('nfse-config.certificado') }}" class="btn btn-danger btn-sm">
												<i class="la la-file"></i>
												Upload de certificado
											</a>
										</div>
										@endif

										<!-- <div class="col-lg-6 col-12 mb-2"></div>
										<div class="col-lg-3 col-12 mb-2">
											<a href="{{ route('nfse-config.new-token') }}" class="btn btn-dark btn-sm float-right">
												<i class="la la-key"></i>
												Gerar token de emissão
											</a>
										</div> -->

										@if($tokenNfse == null)
										<h5 class="text-danger col-12 mt-2">Nenhum token configurado para NFSe</h5>
										@endif
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
								<a style="width: 100%" class="btn btn-danger" href="{{ route('nfse-config.index') }}">
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
	$('#documento').blur(() => {
		let documento = $('#documento').val();
		documento = documento.replace(/[^0-9]/g,'')

		if(documento.length == 14){
			$.get('https://publica.cnpj.ws/cnpj/' + documento)
			.done((data) => {
				console.log(data)
				if (data!= null) {
					let ie = ''
					if (data.estabelecimento.inscricoes_estaduais.length > 0) {
						ie = data.estabelecimento.inscricoes_estaduais[0].inscricao_estadual
					}

					$('#ie').val(ie)
					$('#razao_social').val(data.razao_social)
					$('#nome').val(data.estabelecimento.nome_fantasia)
					$("#rua").val(data.estabelecimento.tipo_logradouro + " " + data.estabelecimento.logradouro)
					$('#numero').val(data.estabelecimento.numero)
					$("#bairro").val(data.estabelecimento.bairro);
					let cep = data.estabelecimento.cep.replace(/[^\d]+/g, '');
					$('#cep').val(cep.substring(0, 5) + '-' + cep.substring(5, 9))
					$('#email').val(data.estabelecimento.email)
					$('#telefone').val(data.estabelecimento.telefone1)

					findCidadeCodigo(data.estabelecimento.cidade.ibge_id)
				}
			})
			.fail((err) => {
				console.log(err)
			})
		}

	})

	function findCidadeCodigo(codigo_ibge){

		$.get(path + "cidades/cidadePorCodigoIbge/" + codigo_ibge)
		.done((res) => {
			console.log(res)
			$('#kt_select2_1').val(res.id).change();
		})
		.fail((err) => {
			console.log(err)
		})
	}
</script>
@endsection
@endsection