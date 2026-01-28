@extends('default.layout')
@section('content')

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<!--begin::Portlet-->

				<form method="post" action="/representantes/update">

					<input type="hidden" name="id" value="{{$representante->id}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">Dados do Representante</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<h3>Empresa: <strong class="text-success">{{ $empresa->nome }}</strong></h3>

									<div class="row">
										<div class="form-group validated col-lg-6 col-12">
											<label class="col-form-label">Nome</label>
											<div class="">
												<input id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{$representante->nome}}">
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-6 col-lg-4">
											<label class="col-form-label" id="lbl_ie_rg">Rua</label>
											<div class="">
												<input type="text" id="rua" class="form-control @if($errors->has('rua')) is-invalid @endif" name="rua" value="{{$representante->rua}}">
												@if($errors->has('rua'))
												<div class="invalid-feedback">
													{{ $errors->first('rua') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label" id="lbl_ie_rg">Nº</label>
											<div class="">
												<input type="text" id="numero" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" value="{{$representante->numero}}">
												@if($errors->has('numero'))
												<div class="invalid-feedback">
													{{ $errors->first('numero') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Bairro</label>
											<div class="">
												<input type="text" id="bairro" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" value="{{$representante->bairro}}">
												@if($errors->has('bairro'))
												<div class="invalid-feedback">
													{{ $errors->first('bairro') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Cidade</label>
											<div class="">
												<input type="text" id="cidade" class="form-control @if($errors->has('cidade')) is-invalid @endif" name="cidade" value="{{$representante->cidade}}">
												@if($errors->has('cidade'))
												<div class="invalid-feedback">
													{{ $errors->first('cidade') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">
												
												@if(strlen($representante->cpf_cnpj) == 14)
												CPF
												@else
												CNPJ
												@endif
											</label>

											<div class="">
												<input type="text" @if(strlen($representante->cnpj) == 14) id="cpf_cnpj" @else d="cpf_cnpj" @endif class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif cpf_cnpj" name="cpf_cnpj" value="{{$representante->cpf_cnpj}}">
												@if($errors->has('cpf_cnpj'))
												<div class="invalid-feedback">
													{{ $errors->first('cpf_cnpj') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Email</label>
											<div class="">
												<input type="text" id="email" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{$representante->email}}">
												@if($errors->has('email'))
												<div class="invalid-feedback">
													{{ $errors->first('email') }}
												</div>
												@endif
											</div>
										</div>
										
										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Telefone</label>
											<div class="">
												<input type="text" id="telefone" class="form-control @if($errors->has('telefone')) is-invalid @endif" name="telefone" value="{{$representante->telefone}}">
												@if($errors->has('telefone'))
												<div class="invalid-feedback">
													{{ $errors->first('telefone') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label text-left col-lg-12 col-sm-12">Status</label>
											<div class="col-6">
												<span class="switch switch-outline switch-success">
													<label>
														<input value="true" @if($representante->status) checked @endisset type="checkbox" name="status" id="status">
														<span></span>
													</label>
												</span>
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label" id="lbl_ie_rg">Comissão %</label>
											<div class="">
												<input type="text" id="comissao" class="form-control @if($errors->has('comissao')) is-invalid @endif money comissao" name="comissao" value="{{ $representante->comissao }}">
												@if($errors->has('comissao'))
												<div class="invalid-feedback">
													{{ $errors->first('comissao') }}
												</div>
												@endif
											</div>
										</div>

										<div class="col-2">
											<label class="col-form-label">Acesso a XML</label>

											<span class="switch switch-outline switch-primary">
												<label>
													<input id="acesso_xml" 
													@if($representante->acesso_xml) checked @endif
													name="acesso_xml" type="checkbox" >
													<span></span>
												</label>
											</span>
										</div>

										<div class="col-lg-3 col-6">
											<label class="col-form-label">Exibir mensagem de cobrança login</label>

											<span class="switch switch-outline switch-dark">
												<label>
													<input id="mensagem_cobranca_login" 
													@if($representante->mensagem_cobranca_login) checked @endif
													name="mensagem_cobranca_login" type="checkbox" >
													<span></span>
												</label>
											</span>
										</div>

										<div class="col-3">
											<label class="col-form-label">Bloquear/desbloquear empresa</label>

											<span class="switch switch-outline switch-danger">
												<label>
													<input id="bloquear_empresa" 
													@if($representante->bloquear_empresa) checked @endif
													name="bloquear_empresa" type="checkbox" >
													<span></span>
												</label>
											</span>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label">Limite de cadastro de empresas</label>
											<div class="">
												<input type="text" id="limite_cadastros" class="form-control @if($errors->has('limite_cadastros')) is-invalid @endif" name="limite_cadastros" value="{{ $representante->limite_cadastros }}">
												@if($errors->has('limite_cadastros'))
												<div class="invalid-feedback">
													{{ $errors->first('limite_cadastros') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label">Senha de acesso padrão</label>
											<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Se definida o representante poderá acessar qualquer usuário vinculado a ele com está senha."><i class="la la-info"></i></button>
											<div class="">
												<input type="text" id="senha_master" class="form-control @if($errors->has('senha_master')) is-invalid @endif" name="senha_master" value="{{ $representante->senha_master }}">
												@if($errors->has('senha_master'))
												<div class="invalid-feedback">
													{{ $errors->first('senha_master') }}
												</div>
												@endif
											</div>
										</div>

										<div class="row">
											<div class="form-group validated col-sm-12">

												<label class="col-3 col-form-label">Permissão de Acesso:</label>

												<input type="hidden" id="menus" value="{{json_encode($menu)}}" name="">
												@foreach($menu as $m)
												<div class="col-12 col-form-label">
													<span>
														<label class="checkbox checkbox-info">
															<input id="todos_{{$m['titulo']}}" onclick="marcarTudo('{{$m['titulo']}}')" type="checkbox" >
															<span></span><strong class="text-info" style="margin-left: 5px; font-size: 16px;">{{($m['titulo'])}} </strong>
														</label>
													</span>
													<div class="checkbox-inline" style="margin-top: 10px;">
														@foreach($m['subs'] as $s)

														@if($s['nome'] != 'NFS-e')

														@php
														$link = str_replace('/', '', $s['rota']);
														$link = str_replace('.', '_', $link);
														$link = str_replace(':', '_', $link);

														@endphp
														<!-- <label class="checkbox checkbox-info check-sub">
															<input id="sub_{{str_replace('/', 	'', $s['rota'])}}" @if(in_array($s['rota'], $permissoesAtivas)) checked @endif type="checkbox" name="{{$s['rota']}}">
															<span></span>{{$s['nome']}}
														</label> -->

														<label class="checkbox checkbox-info check-sub">
															<input id="sub_{{$link}}" @if(\App\Models\Empresa::validaLink($s['rota'], $permissoesAtivas)) checked @endif type="checkbox" name="{{$s['rota']}}">
															<span></span>{{$s['nome']}}
														</label>
														@endif
														@endforeach
													</div>

												</div>
												@endforeach
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

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<div class="card card-custom gutter-b example example-compact">

				</div>
				<div class="row">

					<div class="col-xl-12">

						<div class="row">

							<div class="col-sm-4 col-lg-4 col-md-4 col-12">

							</div>


						</div>
						<div class="row">
							<div class="form-group validated col-sm-6 col-lg-6">
								<div class="">

									<a target="_blank" href="/representantes/alterarSenha/{{$representante->id}}" class="btn btn-danger">
										<i class="la la-key"></i> Alterar Senha
									</a>
								</div>
							</div>

							<div class="col-sm-6 col-lg-6">
								<h3 class="text-success">Data de cadastro: {{ \Carbon\Carbon::parse($representante->created_at)->format('d/m/Y H:i:s')}}</h3>
							</div>
						</div>

						<br>

					</div>
				</div>
			</div>
		</div>
	</div>

	@endsection
	@section('javascript')
	<script type="text/javascript">
		$('[data-toggle="popover"]').popover()
	</script>
	@endsection

