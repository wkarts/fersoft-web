@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<input type="hidden" id="_token" value="{{csrf_token()}}" name="">
				<form method="post" action="{{{ isset($ordem) ? '/ordemServico/update': '/ordemServico/save' }}}">
					<input type="hidden" name="id" value="{{{ isset($ordem->id) ? $ordem->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">{{{ isset($ordem) ? "Editar": "Adicionar" }}} Ordem de Serviço</h3>
						</div>

					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-md-8 col-12">
											<label class="col-form-label" id="lbl_cpf_cnpj">Cliente</label>
											<div class="input-group">
												<select class="form-control select2 cliente @if($errors->has('cliente')) is-invalid @endif" id="kt_select2_1" name="cliente">
													<option value="">Selecione</option>
													@foreach($clientes as $c)
													<option @if(old('cliente') == $c->id) selected @endif value="{{$c->id}}">{{$c->razao_social}} ({{$c->cpf_cnpj}})</option>
													@endforeach
												</select>

												<button type="button" onclick="novoCliente()" class="btn btn-info btn-sm">
													<i class="la la-plus-circle icon-add"></i>
												</button>

												@if($errors->has('cliente'))
												<div class="invalid-feedback">
													{{ $errors->first('cliente') }}
												</div>
												@endif
											</div>
										</div>

										{!! __view_locais_select() !!}

										<div class="form-group validated col-sm-12 col-lg-12">
											<label class="col-form-label">Descrição</label>
											<div class="">
												<textarea class="form-control @if($errors->has('descricao')) is-invalid @endif" name="descricao" rows="3">{{{ isset($os->descricao) ? $os->descricao : old('descricao') }}}</textarea>
												@if($errors->has('descricao'))
												<div class="invalid-feedback">
													{{ $errors->first('descricao') }}
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
								<a style="width: 100%" class="btn btn-danger" href="/ordemServico">
									<i class="la la-close"></i>
									<span class="">Cancelar</span>
								</a>
							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<button style="width: 100%" id="salvar-cotacao" type="submit" class="btn btn-success">
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


<div class="modal fade" id="modal-cliente" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Novo Cliente</h5>
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
								<label class="col-form-label" id="lbl_cpf_cnpj">CPF</label>
								<div class="">
									<input type="text" id="cpf_cnpj" class="form-control @if($errors->has('cpf_cnpj')) is-invalid @endif" name="cpf_cnpj">

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
								<label class="col-form-label">Razao Social/Nome</label>
								<div class="">
									<input id="razao_social2" type="text" class="form-control @if($errors->has('razao_social')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-10 col-lg-6">
								<label class="col-form-label">Nome Fantasia</label>
								<div class="">
									<input id="nome_fantasia2" type="text" class="form-control @if($errors->has('nome_fantasia')) is-invalid @endif">
								</div>
							</div>

							<div class="form-group validated col-sm-3 col-lg-4">
								<label class="col-form-label" id="lbl_ie_rg">RG</label>
								<div class="">
									<input type="text" id="ie_rg" class="form-control @if($errors->has('ie_rg')) is-invalid @endif">
								</div>
							</div>
							<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
								<label class="col-form-label">Consumidor Final</label>

								<select class="custom-select form-control" id="consumidor_final">
									<option value="1">SIM</option>
									<option value="0">NAO</option>
								</select>

							</div>

							<div class="form-group validated col-lg-2 col-md-3 col-sm-10">
								<label class="col-form-label">Contribuinte</label>

								<select class="custom-select form-control" id="contribuinte">

									<option value="1">SIM</option>
									<option value="0">NAO</option>
								</select>
							</div>

							<div class="form-group validated col-sm-3 col-lg-3">
								<label class="col-form-label" id="lbl_ie_rg">Limite de Venda</label>
								<div class="">
									<input type="text" id="limite_venda" class="form-control @if($errors->has('limite_venda')) is-invalid @endif money"  value="0">

								</div>
							</div>

						</div>
						<hr>
						<h5>Endereço de Faturamento</h5>
						<div class="row">

							<div class="form-group validated col-sm-8 col-lg-2">
								<label class="col-form-label">CEP</label>
								<div class="">
									<input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif">

								</div>
							</div>
							<div class="form-group validated col-sm-8 col-lg-4">
								<label class="col-form-label">Rua</label>
								<div class="">
									<input id="rua" type="text" class="form-control @if($errors->has('rua')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-2 col-lg-2">
								<label class="col-form-label">Número</label>
								<div class="">
									<input id="numero2" type="text" class="form-control @if($errors->has('numero')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Bairro</label>
								<div class="">
									<input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Email</label>
								<div class="">
									<input id="email" type="text" class="form-control @if($errors->has('email')) is-invalid @endif">

								</div>
							</div>

							@php
							$cidade = App\Models\Cidade::getCidadeCod($config->codMun);
							@endphp
							<div class="form-group validated col-lg-6 col-md-6 col-sm-10">
								<label class="col-form-label">Cidade</label><br>
								<select style="width: 100%" class="form-control select2" id="kt_select2_4">
									@foreach(App\Models\Cidade::all() as $c)
									<option @if($cidade->id == $c->id) selected @endif value="{{$c->id}}">
										{{$c->nome}} ({{$c->uf}})
									</option>
									@endforeach
								</select>

							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Telefone (Opcional)</label>
								<div class="">
									<input id="telefone" type="text" class="form-control @if($errors->has('telefone')) is-invalid @endif">
								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-3">
								<label class="col-form-label">Celular (Opcional)</label>
								<div class="">
									<input id="celular" type="text" class="form-control @if($errors->has('celular')) is-invalid @endif">
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" id="btn-frete" class="btn btn-danger font-weight-bold spinner-white spinner-right" data-dismiss="modal" aria-label="Close">Fechar</button>
				<button type="button" onclick="salvarCliente()" class="btn btn-success font-weight-bold spinner-white spinner-right">Salvar</button>
			</div>
		</div>
	</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">
	function novoCliente(){
		$('#modal-cliente').modal('show')
	}

	function consultaCadastro() {
		let cnpj = $('#cpf_cnpj').val().replace(/[^0-9]/g,'')

		if (cnpj.length == 14){
			$('#btn-consulta-cadastro').addClass('spinner')
			$.get('https://publica.cnpj.ws/cnpj/' + cnpj)
			.done((data) => {
				$('#btn-consulta-cadastro').removeClass('spinner')
				console.log(data)
				if (data!= null) {
					let ie = ''
					if (data.estabelecimento.inscricoes_estaduais.length > 0) {
						ie = data.estabelecimento.inscricoes_estaduais[0].inscricao_estadual
					}
					$('#ie_rg').val(ie)
					$('#razao_social2').val(data.razao_social)
					$('#nome_fantasia2').val(data.estabelecimento.nome_fantasia)
					$("#rua").val(data.estabelecimento.tipo_logradouro + " " + data.estabelecimento.logradouro)
					$('#numero2').val(data.estabelecimento.numero)
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
				swal("Erro", err.responseJSON.titulo, "error")
			})
		}else{
			swal("Alerta", "Informe corretamente o CNPJ", "warning")
		}

	}

	function findCidadeCodigo(codigo_ibge){

		$.get(path + "cidades/cidadePorCodigoIbge/" + codigo_ibge)
		.done((res) => {
			console.log(res)
			$('#kt_select2_4').val(res.id).change();
		})
		.fail((err) => {
			console.log(err)
		})

	}

	function limparCamposCliente(){
		$('#razao_social2').val('')
		$('#nome_fantasia2').val('')

		$('#rua').val('')
		$('#numero2').val('')
		$('#bairro').val('')
		$('#cep').val('')
		$('#kt_select2_4').val('1').change();
	}

	$('#pessoaFisica').click(function () {
		$('#lbl_cpf_cnpj').html('CPF');
		$('#lbl_ie_rg').html('RG');
		$('#cpf_cnpj').mask('000.000.000-00', { reverse: true });
		$('#btn-consulta-cadastro').css('display', 'none')

	})

	$('#pessoaJuridica').click(function () {
		$('#lbl_cpf_cnpj').html('CNPJ');
		$('#lbl_ie_rg').html('IE');
		$('#cpf_cnpj').mask('00.000.000/0000-00', { reverse: true });
		$('#btn-consulta-cadastro').css('display', 'block');
	});

	function getDataFromCep(cep) {
		if (cep.length < 9 ) {
			return false;
		}else{
			cep = cep.replace("-", "")
			$.get('https://ws.apicep.com/cep.json', { code: cep })
			.done((response) => {
				$('#bairro').val(response.district);
				$('#rua').val(response.address);

				findNomeCidade(response.city, (res) => {
					let jsCidade = JSON.parse(res);
					console.log(jsCidade)
					if (jsCidade) {
						console.log(jsCidade.id + " - " + jsCidade.nome)
						$('#kt_select2_4').val(jsCidade.id).change();
					}
				})
			})
		}
	}

	$('#cep').blur((event) => {
		getDataFromCep(event.target.value);
	});

	function salvarCliente(){
		let js = {
			razao_social: $('#razao_social2').val(),
			nome_fantasia: $('#nome_fantasia2').val() ? $('#nome_fantasia2').val() : '',
			rua: $('#rua').val() ? $('#rua').val() : '',
			numero: $('#numero2').val() ? $('#numero2').val() : '',
			cpf_cnpj: $('#cpf_cnpj').val() ? $('#cpf_cnpj').val() : '',
			ie_rg: $('#ie_rg').val() ? $('#ie_rg').val() : '',
			bairro: $('#bairro').val() ? $('#bairro').val() : '',
			cep: $('#cep').val() ? $('#cep').val() : '',
			consumidor_final: $('#consumidor_final').val() ? $('#consumidor_final').val() : '',
			contribuinte: $('#contribuinte').val() ? $('#contribuinte').val() : '',
			limite_venda: $('#limite_venda').val() ? $('#limite_venda').val() : '',
			cidade_id: $('#kt_select2_4').val() ? $('#kt_select2_4').val() : NULL,
			telefone: $('#telefone').val() ? $('#telefone').val() : '',
			celular: $('#celular').val() ? $('#celular').val() : '',
		}

		console.log(js)
		if(js.razao_social == ''){
			swal("Erro", "Informe a razão social", "warning")
		}else{
			swal({
				title: "Cuidado",
				text: "Ao salvar o cliente com os dados incompletos não será possível emitir NFe até que edite o seu cadstro?",
				icon: "warning",
				buttons: ["Cancelar", 'Salvar'],
				dangerMode: true,
			})
			.then((v) => {
				if (v) {
					let token = $('#_token').val();
					$.post(path + 'clientes/quickSave',
					{
						_token: token,
						data: js
					})
					.done((res) =>{
						CLIENTE = res;
						console.log(res)
						$('#kt_select2_1').append('<option value="'+res.id+'">'+ 
							res.razao_social+'</option>').change();
						$('#kt_select2_1').val(res.id).change();
						swal("Sucesso", "Cliente adicionado!!", 'success')
						.then(() => {
							$('#modal-cliente').modal('hide')
						})
					})
					.fail((err) => {
						console.log(err)
						swal("Alerta", err.responseJSON, "warning")
						
					})
				}
			})
		}

		console.log(js)
	}
</script>
@endsection