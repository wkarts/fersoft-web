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
				<form method="post" action="/contadores/{{{ isset($escritorio) ? 'update' : 'save' }}}">

					<input type="hidden" name="id" value="{{{ isset($escritorio) ? $escritorio->id : 0 }}}">

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{{ isset($escritorio) ? "Editar": "Cadastrar" }}} Contador</h3>
						</div>
					</div>
					@csrf
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-3 col-9">
											<label class="col-form-label">CPF/CNPJ</label>
											<div class="">
												<input required id="cnpj" type="text" class="form-control @if($errors->has('cnpj')) is-invalid @endif cpf_cnpj" name="cnpj" value="{{{ isset($escritorio) ? $escritorio->cnpj : old('cnpj') }}}">
												@if($errors->has('cnpj'))
												<div class="invalid-feedback">
													{{ $errors->first('cnpj') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-lg-1 col-md-2 col-sm-4 col-3">
											<br><br>
											<a style="display: none" type="button" id="consulta" class="btn btn-success spinner-white spinner-right">
												<span>
													<i class="fa fa-search"></i>
												</span>
											</a>
										</div>
										<div class="form-group validated col-sm-12 col-lg-6">
											<label class="col-form-label">Razao Social</label>
											<div class="">
												<input required id="razao_social" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif" name="razao_social" value="{{{ isset($escritorio) ? $escritorio->razao_social : old('razao_social') }}}">
												@if($errors->has('razao_social'))
												<div class="invalid-feedback">
													{{ $errors->first('razao_social') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-12 col-lg-5">
											<label class="col-form-label">Nome Fantasia</label>
											<div class="">
												<input required id="nome_fantasia" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif" name="nome_fantasia" value="{{{ isset($escritorio) ? $escritorio->nome_fantasia : old('nome_fantasia') }}}">
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
												<input required id="representante_legal" type="text" class="form-control @if($errors->has('representante_legal')) is-invalid @endif" name="representante_legal" value="{{{ isset($escritorio) ? $escritorio->empresa->representante_legal : old('representante_legal') }}}">
												@if($errors->has('representante_legal'))
												<div class="invalid-feedback">
													{{ $errors->first('representante_legal') }}
												</div>
												@endif
											</div>
										</div>
										
										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Inscrição Estadual</label>
											<div class="">
												<input id="ie" type="text" class="form-control @if($errors->has('ie')) is-invalid @endif" name="ie" value="{{{ isset($escritorio) ? $escritorio->ie : old('ie') }}}">
												@if($errors->has('ie'))
												<div class="invalid-feedback">
													{{ $errors->first('ie') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">% Comissão</label>
											<div class="">
												<input required id="percentual_comissao" type="text" class="form-control @if($errors->has('percentual_comissao')) is-invalid @endif perc" name="percentual_comissao" value="{{{ isset($escritorio) ? $escritorio->percentual_comissao : old('percentual_comissao') }}}">
												@if($errors->has('percentual_comissao'))
												<div class="invalid-feedback">
													{{ $errors->first('percentual_comissao') }}
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
												<input required id="logradouro" type="text" class="form-control @if($errors->has('logradouro')) is-invalid @endif" name="logradouro" value="{{{ isset($escritorio) ? $escritorio->logradouro : old('logradouro') }}}">
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
												<input required id="numero" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" value="{{{ isset($escritorio) ? $escritorio->numero : old('numero') }}}">
												@if($errors->has('numero'))
												<div class="invalid-feedback">
													{{ $errors->first('numero') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-5 col-lg-3">
											<label class="col-form-label">Bairro</label>
											<div class="">
												<input required id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" value="{{{ isset($escritorio) ? $escritorio->bairro : old('bairro') }}}">
												@if($errors->has('bairro'))
												<div class="invalid-feedback">
													{{ $errors->first('bairro') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-5 col-lg-2">
											<label class="col-form-label">CEP</label>
											<div class="">
												<input required id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif" name="cep" value="{{{ isset($escritorio) ? $escritorio->cep : old('cep') }}}">
												@if($errors->has('cep'))
												<div class="invalid-feedback">
													{{ $errors->first('cep') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-4 col-md-5 col-sm-10">
											<label class="col-form-label text-left">Cidade</label>
											<select required class="form-control select2" id="kt_select2_1" name="cidade_id">
												@foreach($cidades as $c)
												<option value="{{$c->id}}" @isset($escritorio) @if($c->id == $escritorio->cidade_id) selected @endif @endisset 
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

										<div class="form-group validated col-sm-5 col-lg-3">
											<label class="col-form-label">Telefone</label>
											<div class="">
												<input required id="telefone" type="text" class="form-control @if($errors->has('fone')) is-invalid @endif" name="fone" value="{{{ isset($escritorio) ? $escritorio->fone : old('fone') }}}">
												@if($errors->has('fone'))
												<div class="invalid-feedback">
													{{ $errors->first('fone') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-5 col-lg-3">
											<label class="col-form-label">Email</label>
											<div class="">
												<input required id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{{ isset($escritorio) ? $escritorio->email : old('email') }}}">
												@if($errors->has('email'))
												<div class="invalid-feedback">
													{{ $errors->first('email') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label text-left col-lg-12 col-sm-12">Dados bancários</label>
											<div class="col-6">
												<span class="switch switch-outline switch-danger">
													<label>
														<input value="true" @if(isset($escritorio) && $escritorio->dados_bancarios) checked @endif type="checkbox" name="dados_bancarios" id="dados_bancarios">
														<span></span>
													</label>
												</span>
											</div>
										</div>

										<div class="form-group validated col-sm-5 col-lg-2 div-banc d-none">
											<label class="col-form-label">Agencia</label>
											<div class="">
												<input id="telefone" type="text" class="form-control @if($errors->has('agencia')) is-invalid @endif" name="agencia" value="{{{ isset($escritorio) ? $escritorio->agencia : old('agencia') }}}">
												@if($errors->has('agencia'))
												<div class="invalid-feedback">
													{{ $errors->first('agencia') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-5 col-lg-2 div-banc d-none">
											<label class="col-form-label">Conta</label>
											<div class="">
												<input id="telefone" type="text" class="form-control @if($errors->has('conta')) is-invalid @endif" name="conta" value="{{{ isset($escritorio) ? $escritorio->conta : old('conta') }}}">
												@if($errors->has('conta'))
												<div class="invalid-feedback">
													{{ $errors->first('conta') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-5 col-lg-2 div-banc d-none">
											<label class="col-form-label">Banco</label>
											<div class="">
												<input id="telefone" type="text" class="form-control @if($errors->has('banco')) is-invalid @endif" name="banco" value="{{{ isset($escritorio) ? $escritorio->banco : old('banco') }}}">
												@if($errors->has('banco'))
												<div class="invalid-feedback">
													{{ $errors->first('banco') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-5 col-lg-4 div-banc d-none">
											<label class="col-form-label">Chave PIX</label>
											<div class="">
												<input id="telefone" type="text" class="form-control @if($errors->has('chave_pix')) is-invalid @endif" name="chave_pix" value="{{{ isset($escritorio) ? $escritorio->chave_pix : old('chave_pix') }}}">
												@if($errors->has('chave_pix'))
												<div class="invalid-feedback">
													{{ $errors->first('chave_pix') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label text-left col-lg-12 col-sm-12">Contador parceiro</label>
											<div class="col-6">
												<span class="switch switch-outline switch-success">
													<label>
														<input value="true" @if(isset($escritorio) && $escritorio->contador_parceiro) checked @endif type="checkbox" name="contador_parceiro" id="contador_parceiro">
														<span></span>
													</label>
												</span>
											</div>
										</div>

										<div class="form-group validated col-12 col-lg-4">
											<label class="col-form-label">Representante</label>
											<div class="">
												<select name="representante_id" class="select2-custom custom-select">
													<option value="">selecione</option>
													@foreach($representantes as $r)
													<option @if(isset($escritorio) && $escritorio->representante_id) @if($escritorio->representante_id == $r->usuario->id) selected @endif @endif value="{{ $r->id }}">{{ $r->nome }} {{ $r->cpf_cnpj }}</option>
													@endforeach
												</select>
												
											</div>
										</div>

										@if(!isset($escritorio))
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
					<div class="card-footer">

						<div class="row">
							<div class="col-xl-2">

							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/contadores">
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
	$('#consulta').click(() => {
		let cnpj = $('#cnpj').val();
		cnpj = cnpj.replace(/[^0-9]/g,'')
		$('.modal-loading').modal('show')

		if(cnpj.length == 14){
			$('#consulta').addClass('spinner');

			$.ajax({

				url: 'https://www.receitaws.com.br/v1/cnpj/'+cnpj, 
				type: 'GET', 
				crossDomain: true, 
				dataType: 'jsonp', 
				success: function(data) 
				{ 
					$('#consulta').removeClass('spinner');
					$('.modal-loading').modal('hide')
					if(data.status == "ERROR"){
						swal(data.message, "", "error")
					}else{
						$('#razao_social').val(data.nome)
						$('#nome_fantasia').val(data.fantasia)
						$('#logradouro').val(data.logradouro)
						$('#numero').val(data.numero)
						$('#bairro').val(data.bairro)
						$('#email').val(data.email)
						$('#telefone').val(data.telefone.replace("(", "").replace(")", ""))
						let cep = data.cep;
						$('#cep').val(cep.replace(".", ""))
						$('#email').val(data.email)

						findNomeCidade(data.municipio, (res) => {
							let jsCidade = JSON.parse(res);
							if (jsCidade) {
								console.log(jsCidade.id + " - " + jsCidade.nome)
								$('#kt_select2_1').val(jsCidade.id).change();

							}
						})

					}

				}, 
				error: function(e) {
					$('.modal-loading').modal('hide')
					$('#consulta').removeClass('spinner');
					console.log(e)
					swal("Alerta", "Nenhum retorno encontrado para este CNPJ, informe manualmente por gentileza", "warning")

				},
			});
		}else{
			swal("Alerta", "Informe corretamente o CNPJ", "warning")
		}
	})

	function findNomeCidade(nomeCidade, call) {
		$.get(path + 'cidades/findNome/' + nomeCidade)
		.done((success) => {
			call(success)
		})
		.fail((err) => {
			call(err)
		})
	}

	$('#cnpj').keyup(() =>{
		isCnpj()
	})

	function isCnpj(){
		let cnpj = $('#cnpj').val()
		if(cnpj.length == 18){
			$('#consulta').css('display', 'block')
		}else{
			$('#consulta').css('display', 'none')
		}
	}

	$(function () {
		divDadosBancarios()
		isCnpj()
	})

	$('#dados_bancarios').click(() => {
		divDadosBancarios()
	})

	function divDadosBancarios(){
		if($('#dados_bancarios').is(':checked')){
			$('.div-banc').removeClass('d-none')
		}else{
			$('.div-banc').addClass('d-none')
		}
	}
</script>
@endsection