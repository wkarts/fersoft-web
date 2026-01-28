
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
				<!--begin::Portlet-->

				<form method="post" @isset($item) action="{{ route('rep-parceiro.update', [$item->id]) }}" @else action="{{ route('rep-parceiro.store') }}" @endif>
					@csrf
					@isset($item)
					@method('put')
					@endif
					<br>
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">@isset($item) Editando @else Cadastrando @endif Contador</h3>
						</div>
					</div>

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<div class="row">

										<div class="form-group validated col-sm-3 col-lg-3">
											<label class="col-form-label" id="lbl_cpf_cnpj">CNPJ</label>
											<div class="">
												<input required type="text" id="cnpj" class="form-control @if($errors->has('cnpj')) is-invalid @endif" name="cnpj" value="{{ isset($item) ? $item->cnpj : '' }}">
												@if($errors->has('cpf_cnpj'))
												<div class="invalid-feedback">
													{{ $errors->first('cpf_cnpj') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-3">
											<label class="col-form-label" id="lbl_cpf_cnpj">IE</label>
											<div class="">
												<input type="text" id="ie" class="form-control @if($errors->has('ie')) is-invalid @endif" name="ie" value="{{ isset($item) ? $item->ie : '' }}">
												@if($errors->has('ie'))
												<div class="invalid-feedback">
													{{ $errors->first('ie') }}
												</div>
												@endif
											</div>
										</div>
										
										<div class="form-group validated col-sm-10 col-lg-4">
											<label class="col-form-label">Nome/Razão Social</label>
											<div class="">
												<input required id="razao_social" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="razao_social" value="{{ isset($item) ? $item->razao_social : '' }}">
												@if($errors->has('razao_social'))
												<div class="invalid-feedback">
													{{ $errors->first('razao_social') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-4">
											<label class="col-form-label">Nome Fantasia</label>
											<div class="">
												<input required id="nome_fantasia" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif" name="nome_fantasia" value="{{ isset($item) ? $item->nome_fantasia : '' }}">
												@if($errors->has('nome_fantasia'))
												<div class="invalid-feedback">
													{{ $errors->first('nome_fantasia') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-12 col-lg-3">
											<label class="col-form-label">Nome responsável</label>
											<div class="">
												<input required id="representante_legal" type="text" class="form-control @if($errors->has('representante_legal')) is-invalid @endif" name="representante_legal" value="{{{ isset($item) ? $item->empresa->representante_legal : old('representante_legal') }}}">
												@if($errors->has('representante_legal'))
												<div class="invalid-feedback">
													{{ $errors->first('representante_legal') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2">
											<label class="col-form-label" id="lbl_ie_rg">CEP</label>
											<div class="">
												<input type="text" id="cep" class="form-control @if($errors->has('cep')) is-invalid @endif" name="cep" value="{{ isset($item) ? $item->cep : '' }}">
												@if($errors->has('cep'))
												<div class="invalid-feedback">
													{{ $errors->first('cep') }}
												</div>
												@endif
											</div>
										</div>


										<div class="form-group validated col-sm-6 col-lg-5">
											<label class="col-form-label" id="lbl_ie_rg">Rua</label>
											<div class="">
												<input type="text" id="logradouro" class="form-control @if($errors->has('logradouro')) is-invalid @endif" name="logradouro" value="{{ isset($item) ? $item->logradouro : '' }}">
												@if($errors->has('logradouro'))
												<div class="invalid-feedback">
													{{ $errors->first('logradouro') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label" id="lbl_ie_rg">Nº</label>
											<div class="">
												<input required type="text" id="numero" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" value="{{ isset($item) ? $item->numero : '' }}">
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
												<input required type="text" id="bairro" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" value="{{ isset($item) ? $item->bairro : '' }}">
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
												<select id="cidade_id" required name="cidade_id" class="form-control select2-custom">
													<option value="">Selecione</option>
													@foreach($cidades as $c)
													<option @isset($item) @if($item->cidade_id == $c->id) selected @endif @endif value="{{ $c->id }}">{{ $c->info }}</option>
													@endforeach
												</select>
												@if($errors->has('cidade_id'))
												<div class="invalid-feedback">
													{{ $errors->first('cidade_id') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Email</label>
											<div class="">
												<input required type="text" id="email" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{ isset($item) ? $item->email : '' }}">
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
												<input required type="text" id="fone" class="form-control @if($errors->has('fone')) is-invalid @endif telefone" name="fone" value="{{ isset($item) ? $item->fone : '' }}">
												@if($errors->has('fone'))
												<div class="invalid-feedback">
													{{ $errors->first('fone') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Chave Pix</label>
											<div class="">
												<input type="text" id="chave_pix" class="form-control @if($errors->has('chave_pix')) is-invalid @endif" name="chave_pix" value="{{ isset($item) ? $item->chave_pix : '' }}">
												@if($errors->has('chave_pix'))
												<div class="invalid-feedback">
													{{ $errors->first('chave_pix') }}
												</div>
												@endif
											</div>
										</div>
										@if(!isset($item))
										<div class="col-12" style="border-top: 1px solid #999">
											<h4 class="mt-3">Credenciais de Acesso</h4>
										</div>
										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Login</label>
											<div class="">
												<input required type="text" id="login" class="form-control @if($errors->has('login')) is-invalid @endif" name="login">
												@if($errors->has('login'))
												<div class="invalid-feedback">
													{{ $errors->first('login') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Senha</label>
											<div class="">
												<input required type="password" id="senha" class="form-control @if($errors->has('senha')) is-invalid @endif" name="senha">
												@if($errors->has('senha'))
												<div class="invalid-feedback">
													{{ $errors->first('senha') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Nome do usuário</label>
											<div class="">
												<input required type="text" id="nome_usuario" class="form-control @if($errors->has('nome_usuario')) is-invalid @endif" name="nome_usuario">
												@if($errors->has('nome_usuario'))
												<div class="invalid-feedback">
													{{ $errors->first('nome_usuario') }}
												</div>
												@endif
											</div>
										</div>
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
						<a style="width: 100%" class="btn btn-danger" href="{{ route('rep-parceiro.index') }}">
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
<div class="modal-loading loading-class"></div>

@section('javascript')
<script type="text/javascript">

	$('#cnpj').blur(() => {
		consultaCadastro()
	})
	
	function consultaCadastro(){
		let cnpj = $('#cnpj').val();
		cnpj = cnpj.replace(/[^0-9]/g,'')
		if(cnpj.length == 14){

			$('.modal-loading').modal('show')
			$.get('https://publica.cnpj.ws/cnpj/' + cnpj)
			.done((data) => {
				$('.modal-loading').modal('hide')
				if (data!= null) {
					let ie = ''
					if (data.estabelecimento.inscricoes_estaduais.length > 0) {
						ie = data.estabelecimento.inscricoes_estaduais[0].inscricao_estadual
					}

					$('#ie').val(ie)
					$('#razao_social').val(data.razao_social)
					$('#nome_fantasia').val(data.estabelecimento.nome_fantasia)
					$("#logradouro").val(data.estabelecimento.tipo_logradouro + " " + data.estabelecimento.logradouro)
					$('#numero').val(data.estabelecimento.numero)
					$("#bairro").val(data.estabelecimento.bairro);
					let cep = data.estabelecimento.cep.replace(/[^\d]+/g, '');
					$('#cep').val(cep.substring(0, 5) + '-' + cep.substring(5, 9))
					$('#email').val(data.estabelecimento.email)
					$('#fone').val(data.estabelecimento.telefone1)

					findCidadeCodigo(data.estabelecimento.cidade.ibge_id)

				}
			})
			.fail((err) => {
				$('.modal-loading').modal('hide')
				$('#btn-consulta-cadastro').removeClass('spinner')
				console.log(err)
			})
		}

	}
	function findCidadeCodigo(codigo_ibge){

		$.get(path + "cidades/cidadePorCodigoIbge/" + codigo_ibge)
		.done((res) => {
			console.log(res)
			$('#cidade_id').val(res.id).change();
		})
		.fail((err) => {
			console.log(err)
		})

	}

</script>

@endsection
@endsection