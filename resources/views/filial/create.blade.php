@extends('default.layout')
@section('content')

<style type="text/css">
	.btn-file {
		position: relative;
		overflow: hidden;
	}

	.btn-file input[type=file] {
		position: absolute;
		top: 0;
		right: 0;
		min-width: 100%;
		min-height: 100%;
		font-size: 100px;
		text-align: right;
		filter: alpha(opacity=0);
		opacity: 0;
		outline: none;
		background: white;
		cursor: inherit;
		display: block;
	}
</style>
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{{ isset($config) ? route('filial.update', [$config->id]) : route('filial.store') }}}" enctype="multipart/form-data">
					@isset($config)
					@method('put')
					@endif

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{isset($config) ? 'Editar' : 'Nova'}} Localização</h3>
						</div>
					</div>

					@if($infoCertificado != null)
					<h6>Serial Certificado: <strong class="green-text">{{$infoCertificado['serial']}}</strong></h6>
					<h6>Inicio: <strong class="green-text">{{$infoCertificado['inicio']}}</strong></h6>
					<h6>Expiração: <strong class="green-text">{{$infoCertificado['expiracao']}}</strong></h6>
					<h6>IDCTX: <strong class="green-text">{{$infoCertificado['id']}}</strong></h6>
					@endif
					
					@csrf
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">CNPJ</label>
											<div class="">
												<input autofocus id="cnpj" type="text" class="form-control @if($errors->has('cnpj')) is-invalid @endif" name="cnpj" value="{{{ isset($config) ? $config->cnpj : old('cnpj') }}}">
												@if($errors->has('cnpj'))
												<div class="invalid-feedback">
													{{ $errors->first('cnpj') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-4 col-md-4 col-sm-4">
											<br><br>
											<a type="button" id="btn-consulta-cadastro" onclick="consultaCNPJ()" class="btn btn-success spinner-white spinner-right">
												<span>
													<i class="fa fa-search"></i>
												</span>
											</a>
										</div>

									</div>

									<div class="row">
										<div class="form-group validated col-12 col-lg-4">
											<label class="col-form-label">Descrição</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('descricao')) is-invalid @endif" name="descricao" value="{{{ isset($config) ? $config->descricao : old('descricao') }}}">
												@if($errors->has('descricao'))
												<div class="invalid-feedback">
													{{ $errors->first('descricao') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-12 col-lg-6">
											<label class="col-form-label">Razao Social</label>
											<div class="">
												<input id="razao_social" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif" name="razao_social" value="{{{ isset($config) ? $config->razao_social : old('razao_social') }}}">
												@if($errors->has('razao_social'))
												<div class="invalid-feedback">
													{{ $errors->first('razao_social') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-12 col-lg-6">
											<label class="col-form-label">Nome Fantasia</label>
											<div class="">
												<input id="nome_fantasia" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif" name="nome_fantasia" value="{{{ isset($config) ? $config->nome_fantasia : old('nome_fantasia') }}}">
												@if($errors->has('nome_fantasia'))
												<div class="invalid-feedback">
													{{ $errors->first('nome_fantasia') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label">Inscrição Estadual</label>
											<div class="">
												<input id="ie" type="text" class="form-control @if($errors->has('ie')) is-invalid @endif" name="ie" value="{{{ isset($config) ? $config->ie : old('ie') }}}">
												@if($errors->has('ie'))
												<div class="invalid-feedback">
													{{ $errors->first('ie') }}
												</div>
												@endif
											</div>
										</div>

										
										<div class="form-group validated col-12 col-lg-2">
											<label class="col-form-label">Status</label>
											<div class="">
												<select class="form-control @if($errors->has('status')) is-invalid @endif" name="status">
													<option value="1" @isset($config) @if($config->status == 1) selected @endif @endif>Ativo</option>
													<option value="0" @isset($config) @if($config->status == 0) selected @endif @endif>Desativado</option>
												</select>
												@if($errors->has('ativo'))
												<div class="invalid-feedback">
													{{ $errors->first('ativo') }}
												</div>
												@endif
											</div>
										</div>
									</div>

									<hr>
									<h5>Endereço</h5>
									<div class="row">

										<div class="form-group validated col-sm-10 col-lg-6">
											<label class="col-form-label">Rua</label>
											<div class="">
												<input id="logradouro" type="text" class="form-control @if($errors->has('logradouro')) is-invalid @endif" name="logradouro" value="{{{ isset($config) ? $config->logradouro : old('logradouro') }}}">
												@if($errors->has('logradouro'))
												<div class="invalid-feedback">
													{{ $errors->first('logradouro') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-2 col-lg-2">
											<label class="col-form-label">Nº</label>
											<div class="">
												<input id="numero" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" value="{{{ isset($config) ? $config->numero : old('numero') }}}">
												@if($errors->has('numero'))
												<div class="invalid-feedback">
													{{ $errors->first('numero') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-8 col-lg-4">
											<label class="col-form-label">Complemento</label>
											<div class="">
												<input id="complemento" type="text" class="form-control @if($errors->has('complemento')) is-invalid @endif" name="complemento" value="{{{ isset($config) ? $config->complemento : old('complemento') }}}">
												@if($errors->has('complemento'))
												<div class="invalid-feedback">
													{{ $errors->first('complemento') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-4">
											<label class="col-form-label">Bairro</label>
											<div class="">
												<input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" value="{{{ isset($config) ? $config->bairro : old('bairro') }}}">
												@if($errors->has('bairro'))
												<div class="invalid-feedback">
													{{ $errors->first('bairro') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-3">
											<label class="col-form-label">CEP</label>
											<div class="">
												<input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif" name="cep" value="{{{ isset($config) ? $config->cep : old('cep') }}}">
												@if($errors->has('cep'))
												<div class="invalid-feedback">
													{{ $errors->first('cep') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-5 col-md-5 col-sm-10">
											<label class="col-form-label text-left col-12 col-sm-12">Cidade</label>
											<select class="form-control select2" id="kt_select2_1" name="cidade">
												@foreach($cidades as $c)
												<option value="{{$c->id}}" @isset($config) @if($c->codigo == $config->codMun) selected @endif @endisset 
													@if(old('cidade') == $c->id)
													selected
													@endif
													>
													{{$c->nome}} ({{$c->uf}})
												</option>
												@endforeach
											</select>
											@if($errors->has('cidade'))
											<div class="invalid-feedback">
												{{ $errors->first('cidade') }}
											</div>
											@endif
										</div>


										<div class="form-group validated col-lg-4 col-md-4 col-sm-10">
											<label class="col-form-label">Telefone</label>
											<div class="">
												<input id="telefone" type="text" class="form-control @if($errors->has('fone')) is-invalid @endif" name="fone" value="{{{ isset($config) ? $config->fone : old('fone') }}}">
												@if($errors->has('fone'))
												<div class="invalid-feedback">
													{{ $errors->first('fone') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-4 col-md-4 col-sm-10">
											<label class="col-form-label">Email</label>
											<div class="">
												<input id="email" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{{ isset($config) ? $config->email : old('email') }}}">
												@if($errors->has('email'))
												<div class="invalid-feedback">
													{{ $errors->first('email') }}
												</div>
												@endif
											</div>
										</div>
									</div>

									<hr>

									<div class="row">
										<div class="form-group validated col-lg-4 col-md-12 col-sm-12">
											<label class="col-form-label">
												Natureza de Operação Padrão Frente de Caixa
											</label>

											<select class="custom-select form-control" name="nat_op_padrao">
												@foreach($naturezas as $n)
												<option value="{{$n->id}}"
													@isset($config)
													@if($n->id == $config->nat_op_padrao)
													selected
													@endif
													@endisset
													>{{$n->natureza}}
												</option>
												@endforeach
											</select>

										</div>

										<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
											<label class="col-form-label">
												Ambiente
											</label>

											<select @if(env("APP_ENV") == "demo") disabled @endif class="custom-select form-control" name="ambiente">
												<option @if(isset($config)) @if($config->ambiente == 2) selected @endif @endif value="2">2 - Homologação</option>
												<option @if(isset($config)) @if($config->ambiente == 1) selected @endif @endif value="1">1 - Produção</option>
											</select>

										</div>

										<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
											<label class="col-form-label">Nº Série NFe</label>
											<div class="">
												<input id="numero_serie_nfe" type="text" class="form-control @if($errors->has('numero_serie_nfe')) is-invalid @endif" name="numero_serie_nfe" value="{{{ isset($config) ? $config->numero_serie_nfe : old('numero_serie_nfe') }}}">
												@if($errors->has('numero_serie_nfe'))
												<div class="invalid-feedback">
													{{ $errors->first('numero_serie_nfe') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
											<label class="col-form-label">Nº Série NFCe</label>
											<div class="">
												<input id="numero_serie_nfce" type="text" class="form-control @if($errors->has('numero_serie_nfce')) is-invalid @endif" name="numero_serie_nfce" value="{{{ isset($config) ? $config->numero_serie_nfce : old('numero_serie_nfce') }}}">
												@if($errors->has('numero_serie_nfce'))
												<div class="invalid-feedback">
													{{ $errors->first('numero_serie_nfce') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
											<label class="col-form-label">Nº Série CTe</label>
											<div class="">
												<input id="numero_serie_cte" type="text" class="form-control @if($errors->has('numero_serie_cte')) is-invalid @endif" name="numero_serie_cte" value="{{{ isset($config) ? $config->numero_serie_cte : old('numero_serie_cte') }}}">
												@if($errors->has('numero_serie_cte'))
												<div class="invalid-feedback">
													{{ $errors->first('numero_serie_cte') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-lg-2 col-md-4 col-sm-10">
											<label class="col-form-label">Nº Série MDFe</label>
											<div class="">
												<input id="numero_serie_mdfe" type="text" class="form-control @if($errors->has('numero_serie_mdfe')) is-invalid @endif" name="numero_serie_mdfe" value="{{{ isset($config) ? $config->numero_serie_mdfe : old('numero_serie_mdfe') }}}">
												@if($errors->has('numero_serie_mdfe'))
												<div class="invalid-feedback">
													{{ $errors->first('numero_serie_mdfe') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
											<label class="col-form-label">Ultimo Nº NFe</label>
											<div class="">
												<input id="ultimo_numero_nfe" type="text" class="form-control @if($errors->has('ultimo_numero_nfe')) is-invalid @endif" name="ultimo_numero_nfe" value="{{{ isset($config) ? $config->ultimo_numero_nfe : old('ultimo_numero_nfe') }}}">
												@if($errors->has('ultimo_numero_nfe'))
												<div class="invalid-feedback">
													{{ $errors->first('ultimo_numero_nfe') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
											<label class="col-form-label">Ultimo Nº NFCe</label>
											<div class="">
												<input id="ultimo_numero_nfce" type="text" class="form-control @if($errors->has('ultimo_numero_nfce')) is-invalid @endif" name="ultimo_numero_nfce" value="{{{ isset($config) ? $config->ultimo_numero_nfce : old('ultimo_numero_nfce') }}}">
												@if($errors->has('ultimo_numero_nfce'))
												<div class="invalid-feedback">
													{{ $errors->first('ultimo_numero_nfce') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
											<label class="col-form-label">Ultimo Nº CTe</label>
											<div class="">
												<input id="ultimo_numero_cte" type="text" class="form-control @if($errors->has('ultimo_numero_cte')) is-invalid @endif" name="ultimo_numero_cte" value="{{{ isset($config) ? $config->ultimo_numero_cte : old('ultimo_numero_cte') }}}">
												@if($errors->has('ultimo_numero_cte'))
												<div class="invalid-feedback">
													{{ $errors->first('ultimo_numero_cte') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
											<label class="col-form-label">Ultimo Nº MDFe</label>
											<div class="">
												<input id="ultimo_numero_mdfe" type="text" class="form-control @if($errors->has('ultimo_numero_mdfe')) is-invalid @endif" name="ultimo_numero_mdfe" value="{{{ isset($config) ? $config->ultimo_numero_mdfe : old('ultimo_numero_mdfe') }}}">
												@if($errors->has('ultimo_numero_mdfe'))
												<div class="invalid-feedback">
													{{ $errors->first('ultimo_numero_mdfe') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-2 col-md-3 col-sm-3">
											<label class="col-form-label">CSCID</label>
											<div class="">
												<input id="csc_id" type="text" class="form-control @if($errors->has('csc_id')) is-invalid @endif" name="csc_id" value="{{{ isset($config) ? $config->csc_id : old('csc_id') }}}">
												@if($errors->has('csc_id'))
												<div class="invalid-feedback">
													{{ $errors->first('csc_id') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-3 col-md-6 col-sm-6">
											<label class="col-form-label">CSC</label>
											<div class="">
												<input id="csc" type="text" class="form-control @if($errors->has('csc')) is-invalid @endif" name="csc" value="{{{ isset($config) ? $config->csc : old('csc') }}}">
												@if($errors->has('csc'))
												<div class="invalid-feedback">
													{{ $errors->first('csc') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-3 col-md-4 col-sm-4">
											<label class="col-form-label">Inscrição municipal (opcional)</label>
											<div class="">
												<input id="inscricao_municipal" type="text" class="form-control @if($errors->has('inscricao_municipal')) is-invalid @endif im" name="inscricao_municipal" value="{{{ isset($config) ? $config->inscricao_municipal : old('inscricao_municipal') }}}">
												@if($errors->has('inscricao_municipal'))
												<div class="invalid-feedback">
													{{ $errors->first('inscricao_municipal') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-3 col-md-5 col-sm-5">
											<label class="col-form-label">CNPJ Autorizado (opcional)</label>
											<div class="">
												<input data-mask="00.000.000/0000-00" id="aut_xml" type="text" class="form-control @if($errors->has('aut_xml')) is-invalid @endif cnpj" name="aut_xml" value="{{{ isset($config) ? $config->aut_xml : old('aut_xml') }}}">
												@if($errors->has('aut_xml'))
												<div class="invalid-feedback">
													{{ $errors->first('aut_xml') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-4">
											<label class="col-form-label">Arquivo (opcional)</label>
											<div class="">
												<span style="width: 100%" class="btn btn-primary btn-file">
													Procurar arquivo<input accept=".bin,.pfx,.p12" name="certificado" type="file">
												</span>
												<label class="text-info" id="filename"></label>
												
											</div>
										</div>
										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label">Senha certificado (opcional)</label>
											<div class="">
												<input value="{{{ isset($config) ? $config->senha_certificado : old('senha_certificado') }}}" id="senha_certificado" type="text" class="form-control @if($errors->has('senha_certificado')) is-invalid @endif" name="senha_certificado" >
												
											</div>
										</div>
									</div>
									
									<div class="row">
										<div class="form-group validated col-sm-4 col-lg-4 col-6">
											<label class="col-xl-12 col-lg-12 col-form-label text-left">Logo</label>
											<div class="col-lg-10 col-xl-6">

												<div class="image-input image-input-outline" id="kt_image_1">
													<div class="image-input-wrapper" @if(isset($config) && $config->logo != '')style="background-image: url(/logos/{{$config->logo}})" @else style="background-image: url(/imgs/logo.png)" @endif ></div>
													<label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="change" data-toggle="tooltip" title="" data-original-title="Change avatar">
														<i class="fa fa-pencil icon-sm text-muted"></i>
														<input type="file" name="file" accept=".jpg">
														<input type="hidden" name="profile_avatar_remove">
													</label>
													<span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="cancel" data-toggle="tooltip" title="" data-original-title="Cancel avatar">
														<i class="fa fa-close icon-xs text-muted"></i>
													</span>
												</div>

												<span class="form-text text-muted">.jpg</span>
												@if($errors->has('file'))
												<div class="invalid-feedback">
													{{ $errors->first('file') }}
												</div>
												@endif

												@if(isset($config))
												<a href="{{ route('filial.remove-logo', [$config->id]) }}">remover logo</a>
												@endif

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
								<a style="width: 100%" class="btn btn-danger" href="{{ route('filial.index') }}">
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

@endsection