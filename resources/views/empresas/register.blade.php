@extends('default.layout')
@section('content')

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<!--begin::Portlet-->

				<form method="post" action="/empresas/save">

					<input type="hidden" name="id" value="{{{ isset($funcionario) ? $funcionario->id : 0 }}}">
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

										<div class="form-group validated col-sm-3 col-lg-4">
											<label class="col-form-label" id="lbl_cpf_cnpj">CNPJ</label>
											<div class="">
												<input type="text" id="cnpj" class="form-control @if($errors->has('cnpj')) is-invalid @endif" name="cnpj" value="{{ old('cnpj') }}">
												@if($errors->has('cnpj'))
												<div class="invalid-feedback">
													{{ $errors->first('cnpj') }}
												</div>
												@endif
											</div>
										</div>
										
										<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
											<br><br>
											<a type="button" id="consulta" class="btn btn-success spinner-white spinner-right">
												<span>
													<i class="fa fa-search"></i>
												</span>
											</a>
										</div>

									</div>
									<div class="row">
										<div class="form-group validated col-sm-4 col-lg-4">
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

										<div class="form-group validated col-sm-6 col-lg-6">
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
											<label class="col-form-label" id="">Nº</label>
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
												<input type="text" id="telefone" class="form-control @if($errors->has('telefone')) is-invalid @endif" name="telefone" value="{{ old('telefone') }}">
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

											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3">
											<label class="col-form-label">CPF Representante legal</label>
											<div class="">
												<input type="text" id="cpf_representante_legal" class="form-control @if($errors->has('cpf_representante_legal')) is-invalid @endif cpfp" name="cpf_representante_legal" value="{{ old('cpf_representante_legal') }}">

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
											</div>
											@if($errors->has('contador_id'))
											<div class="invalid-feedback">
												{{ $errors->first('contador_id') }}
											</div>
											@endif
										</div>

										<!-- <div class="form-group validated col-sm-6 col-lg-6">
											<label class="col-form-label" id="lbl_ie_rg">Informação do contador</label>
											<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Campo opcional de informação do contador nome, contato, email, etc..."><i class="la la-info"></i></button>
											<div class="">
												<input type="text" id="info_contador" class="form-control @if($errors->has('info_contador')) is-invalid @endif" name="info_contador" value="{{old('info_contador')}}">
												@if($errors->has('info_contador'))
												<div class="invalid-feedback">
													{{ $errors->first('info_contador') }}
												</div>
												@endif
											</div>
										</div>
									-->
									<div class="form-group validated col-sm-6 col-lg-4">
										<label class="col-form-label text-left col-lg-6 col-sm-9">Tipo representante</label>
										<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Se selecionado a empresa será listada no cadastro de representantes"><i class="la la-info"></i></button>
										<div class="col-6">
											<span class="switch switch-outline switch-primary">
												<label>
													<input id="tipo_representante" @if(old('tipo_representante')) checked @endif
													name="tipo_representante" type="checkbox" >
													<span></span>
												</label>
											</span>
										</div>
									</div>

									<div class="comissao col-12" style="display: none;">
										<div class="row">

											<div class="col-2">
												<label class="col-form-label">Acesso a XML</label>

												<span class="switch switch-outline switch-primary">
													<label>
														<input id="acesso_xml" @if(old('acesso_xml')) checked @endif
														name="acesso_xml" type="checkbox" >
														<span></span>
													</label>
												</span>
											</div>

											<div class="col-3">
												<label class="col-form-label">Bloquear/desbloquear empresa</label>

												<span class="switch switch-outline switch-danger">
													<label>
														<input id="bloquear_empresa" @if(old('bloquear_empresa')) checked @endif
														name="bloquear_empresa" type="checkbox" >
														<span></span>
													</label>
												</span>
											</div>

											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label">Comissão %</label>
												<div class="">
													<input type="text" id="comissao" class="form-control @if($errors->has('comissao')) is-invalid @endif money" name="comissao" value="{{old('comissao')}}">
													@if($errors->has('comissao'))
													<div class="invalid-feedback">
														{{ $errors->first('comissao') }}
													</div>
													@endif
												</div>
											</div>

											<div class="form-group validated col-sm-4 col-lg-3">
												<label class="col-form-label">Limite de cadastro de empresas</label>
												<div class="">
													<input type="text" id="limite_cadastros" class="form-control @if($errors->has('limite_cadastros')) is-invalid @endif" name="limite_cadastros" value="{{old('limite_cadastros')}}">
													@if($errors->has('limite_cadastros'))
													<div class="invalid-feedback">
														{{ $errors->first('limite_cadastros') }}
													</div>
													@endif
												</div>
											</div>
										</div>

									</div>
								</div>

								<div class="row">
									<div class="form-group validated">
										<label class="col-3 col-form-label">Permissão de Acesso:</label>

										@if(sizeof($perfis) > 0)
										<div class="form-group validated col-sm-4 col-lg-4">
											<label class="col-form-label" id="lbl_ie_rg">Perfil</label>
											<div class="">
												<select id="perfil-select" class="custom-select" name="perfil_id">
													<option value="0">--</option>
													@foreach($perfis as $p)
													<option value="{{$p}}">
														{{$p->nome}}
													</option>
													@endforeach
												</select>
											</div>
										</div>
										@endif

										<input type="hidden" id="menus" value="{{json_encode($menu)}}" name="">
										@foreach($menu as $m)

										<div class="col-12 col-form-label">
											<span>
												<label class="checkbox checkbox-info">
													<input id="todos_{{str_replace(' ', '_', $m['titulo'])}}" onclick="marcarTudo('{{$m['titulo']}}')" type="checkbox" >
													<span></span><strong class="text-info" style="margin-left: 5px; font-size: 16px;">{{$m['titulo']}} </strong>
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
												<label class="checkbox checkbox-info check-sub">
													<input id="sub_{{$link}}" type="checkbox" name="{{$s['rota']}}">
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

							<div class="form-group validated col-sm-3 col-lg-4">
								<label class="col-form-label" id="lbl_cpf_cnpj">CPF/CNPJ</label>
								<div class="">
									<input type="text" id="cpf_cnpj" class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif cpf_cnpj" name="cpf_cnpj">

								</div>
							</div>
							<div class="form-group validated col-lg-1 col-md-2 col-sm-4 col-3">
								<br><br>
								<a style="display: none" type="button" id="btn-consulta" class="btn btn-success spinner-white spinner-right">
									<span>
										<i class="fa fa-search"></i>
									</span>
								</a>
							</div>

						</div>

						<div class="row">
							<div class="form-group validated col-sm-10 col-lg-6">
								<label class="col-form-label">Razao Social/Nome</label>
								<div class="">
									<input id="razao_social2" type="text" class="form-control">

								</div>
							</div>

							<div class="form-group validated col-sm-10 col-lg-6">
								<label class="col-form-label">Nome Fantasia</label>
								<div class="">
									<input id="nome_fantasia2" type="text" class="form-control">
								</div>
							</div>

							<div class="form-group validated col-sm-3 col-lg-3">
								<label class="col-form-label" id="lbl_ie_rg">IE/RG</label>
								<div class="">
									<input type="text" id="ie_rg" class="form-control">
								</div>
							</div>

							<div class="form-group validated col-sm-3 col-lg-3">
								<label class="col-form-label" for="percentual_comissao">% Comissão</label>
								<div class="">
									<input type="text" id="percentual_comissao" class="form-control perc">
								</div>
							</div>
							

						</div>
						<hr>
						<h5>Endereço</h5>
						<div class="row">
							<div class="form-group validated col-sm-8 col-lg-6">
								<label class="col-form-label">Rua</label>
								<div class="">
									<input id="rua2" type="text" class="form-control">

								</div>
							</div>

							<div class="form-group validated col-sm-2 col-lg-2">
								<label class="col-form-label">Número</label>
								<div class="">
									<input id="numero2" type="text" class="form-control">
								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-4">
								<label class="col-form-label">Bairro</label>
								<div class="">
									<input id="bairro2" type="text" class="form-control">

								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-2">
								<label class="col-form-label">CEP</label>
								<div class="">
									<input id="cep2" type="text" class="form-control">
								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Email</label>
								<div class="">
									<input id="email2" type="text" class="form-control">
								</div>
							</div>


							<div class="form-group validated col-lg-4 col-md-6 col-sm-10">
								<label class="col-form-label">Cidade</label><br>
								<select style="width: 100%" class="form-control select2" id="kt_select2_4">
									@foreach(App\Models\Cidade::all() as $c)
									<option value="{{$c->id}}">
										{{$c->nome}} ({{$c->uf}})
									</option>
									@endforeach
								</select>
							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Telefone</label>
								<div class="">
									<input id="telefone2" type="text" class="form-control fone">
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
									<input id="agencia" type="text" class="form-control" name="agencia">
									
								</div>
							</div>

							<div class="form-group validated col-sm-5 col-lg-2 div-banc d-none">
								<label class="col-form-label">Conta</label>
								<div class="">
									<input id="conta" type="text" class="form-control" name="conta">
									
								</div>
							</div>

							<div class="form-group validated col-sm-5 col-lg-2 div-banc d-none">
								<label class="col-form-label">Banco</label>
								<div class="">
									<input id="banco" type="text" class="form-control" name="banco">
									
								</div>
							</div>

							<div class="form-group validated col-sm-5 col-lg-4 div-banc d-none">
								<label class="col-form-label">Chave PIX</label>
								<div class="">
									<input id="chave_pix" type="text" class="form-control" name="chave_pix">

								</div>
							</div>

							<div class="form-group validated col-sm-6 col-lg-2">
								<label class="col-form-label text-left col-lg-12 col-sm-12">Contador parceiro</label>
								<div class="col-6">
									<span class="switch switch-outline switch-success">
										<label>
											<input value="true" type="checkbox" name="contador_parceiro" id="contador_parceiro">
											<span></span>
										</label>
									</span>
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

@endsection

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

	$('#btn-consulta').click(() => {
		let cnpj = $('#cpf_cnpj').val();

		cnpj = cnpj.replace(/[^0-9]/g,'')

		if(cnpj.length == 14){
			$('#btn-consulta').addClass('spinner');

			$.ajax({

				url: 'https://www.receitaws.com.br/v1/cnpj/'+cnpj, 
				type: 'GET', 
				crossDomain: true, 
				dataType: 'jsonp', 
				success: function(data) 
				{ 
					$('#btn-consulta').removeClass('spinner');

					if(data.status == "ERROR"){
						swal(data.message, "", "error")
					}else{
						$('#razao_social2').val(data.nome)
						$('#nome_fantasia2').val(data.fantasia)
						$('#rua2').val(data.logradouro)
						$('#numero2').val(data.numero)
						$('#bairro2').val(data.bairro)
						$('#email2').val(data.email)
						$('#telefone2').val(data.telefone.replace("(", "").replace(")", ""))
						let cep = data.cep;
						console.log(cep)
						$('#cep2').val(cep.replace(".", ""))
						$('#email2').val(data.email)

						findNomeCidade(data.municipio, (res) => {
							let jsCidade = JSON.parse(res);
							console.log(jsCidade)
							if (jsCidade) {
								console.log(jsCidade.id + " - " + jsCidade.nome)
								$('#kt_select2_4').val(jsCidade.id).change();

							}
						})

					}

				}, 
				error: function(e) { 
					$('#btn-consulta').removeClass('spinner');
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

	$('#cpf_cnpj').keyup(() =>{
		isCnpj()
	})

	function isCnpj(){
		let cnpj = $('#cpf_cnpj').val()
		if(cnpj.length == 18){
			$('#btn-consulta').css('display', 'block')
		}else{
			$('#btn-consulta').css('display', 'none')
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

	function salvarContador(){
		validaContador((msg) => {

			if(!msg){

				let js = {
					razao_social: $('#razao_social2').val(),
					nome_fantasia: $('#nome_fantasia2').val() ? $('#nome_fantasia2').val() : '',
					logradouro: $('#rua2').val() ? $('#rua2').val() : '',
					numero: $('#numero2').val() ? $('#numero2').val() : '',
					cnpj: $('#cpf_cnpj').val() ? $('#cpf_cnpj').val() : '',
					ie: $('#ie_rg').val() ? $('#ie_rg').val() : '',
					percentual_comissao: $('#percentual_comissao').val() ? $('#percentual_comissao').val() : '',
					bairro: $('#bairro2').val() ? $('#bairro2').val() : '',
					cep: $('#cep2').val() ? $('#cep2').val() : '',
					cidade_id: $('#kt_select2_4').val() ? $('#kt_select2_4').val() : NULL,
					fone: $('#telefone2').val() ? $('#telefone2').val() : '',
					email: $('#email2').val() ? $('#email2').val() : '',

					contador_parceiro: $('#contador_parceiro').is(':checked') ? 1 : 0,
					agencia: $('#agencia').val() ? $('#agencia').val() : '',
					conta: $('#conta').val() ? $('#conta').val() : '',
					banco: $('#banco').val() ? $('#banco').val() : '',
					chave_pix: $('#chave_pix').val() ? $('#chave_pix').val() : '',
				}

				$.post(path + 'contadores/quickSave',
				{
					_token: '{{ csrf_token() }}',
					data: js
				})
				.done((res) =>{
					console.log(res)
					$('#kt_select2_1').append('<option value="'+res.id+'">'+ 
						res.razao_social+'</option>').change();
					swal("Sucesso", "Contador adicionado!!", 'success')
					.then(() => {
						$('#modal-contador').modal('hide')
						$('#kt_select2_1').val(res.id).change();

					})
				})
				.fail((err) => {
					console.log(err)
					swal("Alerta", err.responseJSON, "warning")
				})
			}else{

				swal("Erro de formulário", msg, "error")
			}
		})

	}

	function validaContador(call){
		let arrayValid = [
		'cpf_cnpj', 'nome_fantasia2', 'razao_social2', 'percentual_comissao',
		'rua2', 'numero2', 'bairro2', 'cep2', 'email2', 'kt_select2_4', 'telefone2'
		]
		let msg = ""
		arrayValid.map((x) => {
			if(!$('#'+x).val()){
				msg = "Todos os campos são obrigatóriros!"
			}
		})
		setTimeout(() => {
			call(msg)
		},100)
	}
</script>

@endsection
