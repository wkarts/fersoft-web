
@extends('default.layout')
@section('content')

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<!--begin::Portlet-->

				<form method="post" action="/rep/saveEmpresa">

					<input type="hidden" name="id" value="{{{ isset($funcionario) ? $funcionario->id : 0 }}}">
					<input type="hidden" id="_token" value="{{ csrf_token() }}">
					<br>
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">Cadastrar Empresa</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="radio-inline">

										<label class="radio radio-outline radio-success">
											<input type="radio" name="tipo_pessoa" value="j" checked/>
											<span></span>
											Juridica
										</label>
										<label class="radio radio-outline radio-success">
											<input type="radio" name="tipo_pessoa" value="f" />
											<span></span>
											Fisica
										</label>
									</div>
									<div class="row">

										<div class="form-group validated col-sm-3 col-lg-3">
											<label class="col-form-label" id="lbl_cpf_cnpj">CNPJ</label>
											<div class="">
												<input type="text" id="cnpj" class="form-control @if($errors->has('cnpj')) is-invalid @endif" name="cnpj" value="{{ old('cnpj') }}">
												@if($errors->has('cpf_cnpj'))
												<div class="invalid-feedback">
													{{ $errors->first('cpf_cnpj') }}
												</div>
												@endif
											</div>
										</div>
										
										<div class="form-group validated col-lg-1 col-md-2 col-sm-6">
											<br><br>
											<a type="button" id="consulta" class="btn btn-success spinner-white spinner-right">
												<span>
													<i class="fa fa-search"></i>
												</span>
											</a>
										</div>
									</div>

									<div class="row">
										<div class="form-group validated col-sm-10 col-lg-4">
											<label class="col-form-label">Nome/Razão Social</label>
											<div class="">
												<input id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{ old('nome') }}">
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-4">
											<label class="col-form-label">Nome Fantasia</label>
											<div class="">
												<input id="nome_fantasia" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif" name="nome_fantasia" value="{{ old('nome_fantasia') }}">
												@if($errors->has('nome_fantasia'))
												<div class="invalid-feedback">
													{{ $errors->first('nome_fantasia') }}
												</div>
												@endif
											</div>
										</div>

									</div>

									<div class="row">

										<div class="form-group validated col-sm-6 col-lg-5">
											<label class="col-form-label" id="lbl_ie_rg">Rua</label>
											<div class="">
												<input type="text" id="rua" class="form-control @if($errors->has('rua')) is-invalid @endif" name="rua" value="{{ old('rua') }}">
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
												<input type="text" id="numero" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" value="{{ old('numero') }}">
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
												<input type="text" id="bairro" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" value="{{ old('bairro') }}">
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
												<input type="text" id="cidade" class="form-control @if($errors->has('cidade')) is-invalid @endif" name="cidade" value="{{ old('cidade') }}">
												@if($errors->has('cidade'))
												<div class="invalid-feedback">
													{{ $errors->first('cidade') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
											<label class="col-form-label">UF</label>
											<select class="custom-select form-control" id="uf2" name="uf">
												@foreach(App\Models\Cidade::estados() as $e)
												<option value="{{$e}}" @if(old('uf') == $e) selected @endif>
													{{$e}}
												</option>
												@endforeach
											</select>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Email</label>
											<div class="">
												<input type="text" id="email" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{ old('email') }}">
												@if($errors->has('email'))
												<div class="invalid-feedback">
													{{ $errors->first('email') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">CEP</label>
											<div class="">
												<input type="text" id="cep" class="form-control @if($errors->has('cep')) is-invalid @endif" name="cep" value="{{ old('cep') }}">
												@if($errors->has('cep'))
												<div class="invalid-feedback">
													{{ $errors->first('cep') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Telefone</label>
											<div class="">
												<input type="text" id="telefone" class="form-control @if($errors->has('telefone')) is-invalid @endif telefone" name="telefone" value="{{ old('telefone') }}">
												@if($errors->has('telefone'))
												<div class="invalid-feedback">
													{{ $errors->first('telefone') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-4">
											<label class="col-form-label">Representante legal</label>
											<div class="">
												<input type="text" id="representante_legal" class="form-control @if($errors->has('representante_legal')) is-invalid @endif" name="representante_legal" value="{{ old('representante_legal') }}">
												@if($errors->has('representante_legal'))
												<div class="invalid-feedback">
													{{ $errors->first('representante_legal') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label">CPF Representante legal</label>
											<div class="">
												<input type="text" id="cpf_representante_legal" class="form-control @if($errors->has('cpf_representante_legal')) is-invalid @endif cpfp" name="cpf_representante_legal" value="{{ old('cpf_representante_legal') }}">
												@if($errors->has('cpf_representante_legal'))
												<div class="invalid-feedback">
													{{ $errors->first('cpf_representante_legal') }}
												</div>
												@endif
											</div>
										</div>

									</div>

									<hr>

									<div class="row">
										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Login</label>
											<div class="">
												<input type="text" id="login" class="form-control @if($errors->has('login')) is-invalid @endif" name="login" value="{{ old('login') }}">
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
												<input type="password" id="senha" class="form-control @if($errors->has('senha')) is-invalid @endif" name="senha" value="{{old('senha')}}">
												@if($errors->has('senha'))
												<div class="invalid-feedback">
													{{ $errors->first('senha') }}
												</div>
												@endif
											</div>
										</div>


										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label" id="lbl_ie_rg">Nome usuário</label>
											<div class="">
												<input type="text" id="nome_usuario" class="form-control @if($errors->has('nome_usuario')) is-invalid @endif" name="nome_usuario" value="{{old('nome_usuario')}}">
												@if($errors->has('nome_usuario'))
												<div class="invalid-feedback">
													{{ $errors->first('nome_usuario') }}
												</div>
												@endif
											</div>
										</div>


									</div>
								</div>


								<div class="row">

									<div class="form-group validated col-sm-4 col-lg-4">
										<label class="col-form-label" id="lbl_ie_rg">Plano</label>
										<select required id="perfil-select" class="custom-select" name="plano_id">
											<option value="">--</option>
											@foreach($planos as $p)
											<option value="{{$p->id}}">
												{{$p->nome}} R$ {{ moeda($p->valor) }}
											</option>
											@endforeach
										</select>
									</div>

									<div class="form-group validated col-lg-6 col-md-5 col-sm-10">
										<label class="col-form-label text-left">Contador (opcional)</label>
										<div class="input-group">
											<select class="form-control select2" id="kt_select2_1" name="contador_id">
												<option value="">Selecione o contador</option>
												@foreach($contadores as $c)
												<option value="{{$c->id}}" @isset($empresa) @if($c->id == $empresa->cidade_id) selected @endif @endisset 
													@if(old('contador_id') == $c->id)
													selected
													@endif
													>
													{{$c->razao_social}} {{$c->cnpj}}
												</option>
												@endforeach
											</select>

											<button type="button" onclick="novoContador()" class="btn btn-info btn-sm">
												<i class="la la-plus-circle icon-add"></i>
											</button>
										</div>
										@if($errors->has('contador_id'))
										<div class="invalid-feedback">
											{{ $errors->first('contador_id') }}
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
						<a style="width: 100%" class="btn btn-danger" href="/funcionarios">
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


<div class="modal fade" id="modal-contador" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Novo Contador</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">

				<div class="row">
					<div class="col-xl-12">

						<div class="row">
							<div class="form-group col-sm-12 col-lg-12">
								<label>Pessoa:</label>
								<div class="radio-inline">
									<label class="radio radio-success">
										<input name="group1" type="radio" id="pessoaFisica"/>
										<span></span>
										FISICA
									</label>
									<label class="radio radio-success">
										<input name="group1" type="radio" id="pessoaJuridica"/>
										<span></span>
										JURIDICA
									</label>
								</div>
							</div>
						</div>
						<div class="row">

							<div class="form-group validated col-sm-3 col-lg-4">
								<label class="col-form-label lbl_cpf_cnpj">CPF*</label>
								<div class="">
									<input type="text" id="doc" class="form-control cpf_cnpj" name="">
								</div>
							</div>
							
							<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
								<br><br>
								<a type="button" id="btn-consulta-cadastro" onclick="consultaCadastro()" class="btn btn-success spinner-white spinner-right">
									<span>
										<i class="fa fa-search"></i>
									</span>
								</a>
							</div>

						</div>

						<div class="row">
							<div class="form-group validated col-sm-10 col-lg-6">
								<label class="col-form-label">Razao Social/Nome*</label>
								<div class="">
									<input id="razao_social" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-10 col-lg-6">
								<label class="col-form-label">Responsável*</label>
								<div class="">
									<input id="responsavel" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif">
								</div>
							</div>

							

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Usuário*</label>
								<div class="">
									<input id="login-contador" type="text" class="form-control">
								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Senha*</label>
								<div class="">
									<input id="senha-contador" type="password" class="form-control">
								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-4">
								<label class="col-form-label">Email*</label>
								<div class="">
									<input id="email-contador" type="email" class="form-control">
								</div>
							</div>

							<div class="form-group validated col-lg-4 col-md-6 col-sm-10">
								<label class="col-form-label">Cidade*</label><br>
								<select style="width: 100%" class="form-control select2 select2-custom" id="cidade_id">
									<option value="">Selecione</option>
									@foreach(App\Models\Cidade::all() as $c)
									<option value="{{$c->id}}">
										{{$c->nome}} ({{$c->uf}})
									</option>
									@endforeach
								</select>
							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Contato (whatsApp)*</label>
								<div class="">
									<input id="celular-contador" type="tel" class="form-control celular">
								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">PIX</label>
								<div class="">
									<input id="pix" type="text" class="form-control">
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
				<button type="button" onclick="salvarContador()" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
			</div>
		</div>
	</div>
</div>

@section('javascript')
<script type="text/javascript">
	$('[data-toggle="popover"]').popover()
	$("input[name='tipo_pessoa']").change(function(target){
		console.log(target.target.value)
		if(target.target.value == 'j'){
			$('#lbl_cpf_cnpj').html('CNPJ')
			$('#cnpj').mask('00.000.000/0000-00')
			$('#consulta').removeClass('disabled')
			$('#consulta').removeAttr('disabled')

		}else{
			$('#lbl_cpf_cnpj').html('CPF')
			$('#cnpj').mask('000.000.000-00')
			$('#consulta').addClass('disabled')
			$('#consulta').attr('disabled', true)

		}	
	});

	$(function () {
		tipoRepresentante()
	})

	$('#tipo_representante').change(() => {
		tipoRepresentante()
	})

	function tipoRepresentante(){
		if($('#tipo_representante').is(':checked')){
			$('.comissao').css('display', 'block')
		}else{
			$('.comissao').css('display', 'none')
		}
	}

	function novoContador(){
		$('#modal-contador').modal('show')
	}

	$('#pessoaFisica').click(function () {
		$('.lbl_cpf_cnpj').html('CPF*');
	})

	$('#pessoaJuridica').click(function () {
		$('.lbl_cpf_cnpj').html('CNPJ*');
	});

	function consultaCadastro(){
		let cpf_cnpj = $('#doc').val();
		cpf_cnpj = cpf_cnpj.replace(/[^0-9]/g,'')
		if(cpf_cnpj.length == 14){

			$('#btn-consulta-cadastro').addClass('spinner')
			$.get('https://publica.cnpj.ws/cnpj/' + cpf_cnpj)
			.done((data) => {
				$('#btn-consulta-cadastro').removeClass('spinner')

				if (data!= null) {
					let ie = ''
					if (data.estabelecimento.inscricoes_estaduais.length > 0) {
						ie = data.estabelecimento.inscricoes_estaduais[0].inscricao_estadual
					}

					$('#ie_rg').val(ie)
					$('#razao_social').val(data.razao_social)
					$('#nome_fantasia').val(data.estabelecimento.nome_fantasia)
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

	function salvarContador(){
		let doc = $('#doc').val()
		let razao_social = $('#razao_social').val()
		let responsavel = $('#responsavel').val()
		let login = $('#login-contador').val()
		let senha = $('#senha-contador').val()
		let email = $('#email-contador').val()
		let cidade_id = $('#cidade_id').val()
		let celular = $('#celular-contador').val()
		let pix = $('#pix').val()

		if(!doc || !razao_social || !responsavel || !login || !senha || !email || !cidade_id || !celular){
			swal("Atenção", "Informe todos os campos obrigatórios para cadastrar", "warning")
		}else{
			let data = {
				doc: doc,
				razao_social: razao_social,
				responsavel: responsavel,
				login: login,
				senha: senha,
				email: email,
				cidade_id: cidade_id,
				celular: celular,
				pix: pix
			}
			console.log(data)
			$.post(path + 'rep/novo-contador',
			{
				data: data,
				_token: $('#_token').val()
			})
			.done((res) => {
				console.log(res)

				$('#kt_select2_1').append('<option value="'+res.id+'">'+ res.razao_social+'</option>')
				$('#kt_select2_1').val(res.id).change()
				swal("Sucesso", "Contador cadastrado!", "success")
				.then(() => {
					$('#modal-contador').modal('hide')

				})
			}).fail((err) => {
				console.log(err)
				try{
					swal("Erro", err.responseJSON, "error")
				}catch{
					swal("Erro", "Algo deu errado", "error")
				}
			})
		}
	}

</script>

@endsection
@endsection