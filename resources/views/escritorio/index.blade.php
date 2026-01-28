@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/escritorio/save">
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
											<label class="col-form-label">CNPJ</label>
											<div class="">
												<input id="cnpj" type="text" class="form-control @if($errors->has('cnpj')) is-invalid @endif cpf_cnpj" name="cnpj" value="{{{ isset($escritorio) ? $escritorio->cnpj : old('cnpj') }}}">
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
												<input id="razao_social" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif" name="razao_social" value="{{{ isset($escritorio) ? $escritorio->razao_social : old('razao_social') }}}">
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
												<input id="nome_fantasia" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif" name="nome_fantasia" value="{{{ isset($escritorio) ? $escritorio->nome_fantasia : old('nome_fantasia') }}}">
												@if($errors->has('nome_fantasia'))
												<div class="invalid-feedback">
													{{ $errors->first('nome_fantasia') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
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
									</div>

									<hr>
									<h5>Endereço</h5>

									<div class="row">

										<div class="form-group validated col-sm-10 col-lg-6">
											<label class="col-form-label">Rua</label>
											<div class="">
												<input id="logradouro" type="text" class="form-control @if($errors->has('logradouro')) is-invalid @endif" name="logradouro" value="{{{ isset($escritorio) ? $escritorio->logradouro : old('logradouro') }}}">
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
												<input id="numero" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif" name="numero" value="{{{ isset($escritorio) ? $escritorio->numero : old('numero') }}}">
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
												<input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif" name="bairro" value="{{{ isset($escritorio) ? $escritorio->bairro : old('bairro') }}}">
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
												<input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif" name="cep" value="{{{ isset($escritorio) ? $escritorio->cep : old('cep') }}}">
												@if($errors->has('cep'))
												<div class="invalid-feedback">
													{{ $errors->first('cep') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-lg-4 col-md-5 col-sm-10">
											<label class="col-form-label text-left">Cidade</label>
											<select class="form-control select2" id="kt_select2_1" name="cidade_id">
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
												<input type="tel" class="form-control @if($errors->has('fone')) is-invalid @endif" name="fone" value="{{{ isset($escritorio) ? $escritorio->fone : old('fone') }}}" data-mask="(00) 0000-0000">
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
												<input id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif" name="email" value="{{{ isset($escritorio) ? $escritorio->email : old('email') }}}">
												@if($errors->has('email'))
												<div class="invalid-feedback">
													{{ $errors->first('email') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-5 col-lg-2">
											<label class="col-form-label">CRC</label>
											<div class="">
												<input id="crc" type="text" class="form-control @if($errors->has('crc')) is-invalid @endif" name="crc" value="{{{ isset($escritorio) ? $escritorio->crc : old('crc') }}}">
												@if($errors->has('crc'))
												<div class="invalid-feedback">
													{{ $errors->first('crc') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-5 col-lg-3">
											<label class="col-form-label">CPF</label>
											<div class="">
												<input id="cpf" type="text" class="form-control @if($errors->has('cpf')) is-invalid @endif" name="cpf" value="{{{ isset($escritorio) ? $escritorio->cpf : old('cpf') }}}">
												@if($errors->has('cpf'))
												<div class="invalid-feedback">
													{{ $errors->first('cpf') }}
												</div>
												@endif
											</div>
										</div>

										@if($apiSieg)
										<div class="form-group validated col-sm-5 col-lg-3">
											<label class="col-form-label">Token Sieg</label>
											<div class="">
												<input id="token_sieg" type="text" class="form-control @if($errors->has('token_sieg')) is-invalid @endif" name="token_sieg" value="{{{ isset($escritorio) ? $escritorio->token_sieg : old('token_sieg') }}}">
												@if($errors->has('token_sieg'))
												<div class="invalid-feedback">
													{{ $errors->first('token_sieg') }}
												</div>
												@endif
											</div>
										</div>
										@else
										<input id="token_sieg" type="hidden" class="form-control" name="token_sieg" value="">
										@endif

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label text-left col-lg-12 col-sm-12">Envio Xml automático</label>
											<div class="col-6">
												<span class="switch switch-outline switch-info">
													<label>
														<input value="true" @if(isset($escritorio) && $escritorio->envio_automatico_xml_contador) checked @endif type="checkbox" name="envio_automatico_xml_contador" id="envio_automatico_xml_contador">
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
								<a style="width: 100%" class="btn btn-danger" href="/escritorio">
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
@section('javascript')
<script type="text/javascript">
	$('#consulta').click(() => {
		let cnpj = $('#cnpj').val();
		cnpj = cnpj.replace(/[^0-9]/g,'')


		if(cnpj.length == 14){
			$('#consulta').addClass('spinner');

			$.get('https://publica.cnpj.ws/cnpj/' + cnpj)
			.done((data) => {
				$('#consulta').removeClass('spinner')
				console.log(data)
				if (data != null) {
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
					$('#telefone').val(data.estabelecimento.telefone1)

					findCidadeCodigo(data.estabelecimento.cidade.ibge_id)

				}
			})
			.fail((err) => {
				$('#consulta').removeClass('spinner')
				console.log(err)
			})

			
		}else{
			swal("Alerta", "Informe corretamente o CNPJ", "warning")
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
		isCnpj()
	})

	$('#envio_automatico_xml_contador').click(() => {
		if($('#envio_automatico_xml_contador').is(':checked')){
			swal("Atenção", "Ao marcar esta opção certifique-se que as configurações de Email estão corretas!", "warning")

		}
	})
</script>
@endsection