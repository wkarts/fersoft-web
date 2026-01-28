@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{{ isset($conta) ? '/contasReceber/update': '/contasReceber/save' }}}" enctype="multipart/form-data" id="form-register">
					<input type="hidden" name="id" value="{{{ isset($conta) ? $conta->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">{{{ isset($conta) ? "Editar": "Cadastrar" }}} Conta a Receber</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Referencia</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('referencia')) is-invalid @endif" name="referencia" value="{{{ isset($conta) ? $conta->referencia : old('referencia') }}}">
												@if($errors->has('referencia'))
												<div class="invalid-feedback">
													{{ $errors->first('referencia') }}
												</div>
												@endif
											</div>
										</div>

										@if(!isset($conta) || $conta->venda_id == null)
										<div class="form-group validated col-sm-9 col-lg-4 col-12">
											<label class="col-form-label" id="">Cliente</label>
											<div class="input-group">

												<select class="form-control select2 @if($errors->has('cliente_id')) is-invalid @endif"  id="kt_select2_3" name="cliente_id">
													<option value="">Selecione o cliente</option>
													@foreach($clientes as $c)
													<option
													@if(isset($conta))
													@if($conta->cliente_id != null)
													@if($conta->cliente_id == $c->id)
													selected
													@endif
													@endif
													@endif
													value="{{$c->id}}">{{$c->id}} - {{$c->razao_social}} ({{$c->cpf_cnpj}})</option>
													@endforeach
												</select>
												<button type="button" onclick="novoCliente()" class="btn btn-warning btn-sm">
													<i class="la la-plus-circle icon-add"></i>
												</button>
												@if($errors->has('cliente_id'))
												<div class="invalid-feedback">
													{{ $errors->first('cliente_id') }}
												</div>
												@endif
											</div>

										</div>
										@endif

										<div class="form-group validated col-lg-3 col-md-4 col-sm-6">
											<label class="col-form-label">Categoria</label>

											<select class="custom-select form-control @if($errors->has('categoria_id')) is-invalid @endif" id="categoria_id" name="categoria_id">
												@foreach($categorias as $cat)
												<option value="{{$cat->id}}" @isset($conta)
													@if($cat->id == $conta->categoria_id)
													selected
													@endif
													@endisset >{{$cat->nome}}
												</option>

												@endforeach

											</select>
											@if($errors->has('categoria_id'))
											<div class="invalid-feedback">
												{{ $errors->first('categoria_id') }}
											</div>
											@endif

										</div>

										<div class="form-group col-lg-2 col-md-9 col-sm-12">
											<label class="col-form-label">Data de vencimento</label>
											<div class="">
												<div class="input-group date">
													<input type="text" name="vencimento" class="form-control @if($errors->has('vencimento')) is-invalid @endif date-input" value="{{{ isset($conta) ? \Carbon\Carbon::parse($conta->data_vencimento)->format('d/m/Y') : old('vencimento') }}}" id="kt_datepicker_3" />
													<div class="input-group-append">
														<span class="input-group-text">
															<i class="la la-calendar"></i>
														</span>
													</div>
												</div>
												@if($errors->has('vencimento'))
												<div class="invalid-feedback">
													{{ $errors->first('vencimento') }}
												</div>
												@endif

											</div>
										</div>

										<div class="form-group validated col-lg-2 col-md-4 col-sm-6">
											<label class="col-form-label">Valor</label>

											<input type="text" id="valor" class="form-control @if($errors->has('valor')) is-invalid @endif money" name="valor" value="{{{ isset($conta) ? number_format($conta->valor_integral, $casasDecimais, ',', '.') : old('valor') }}}">
											@if($errors->has('valor'))
											<div class="invalid-feedback">
												{{ $errors->first('valor') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-lg-2 col-md-4 col-sm-6">
											<label class="col-form-label">Nº nota fiscal</label>

											<input id="numero_nota_fiscal" type="text" class="form-control @if($errors->has('numero_nota_fiscal')) is-invalid @endif" name="numero_nota_fiscal" value="{{{ isset($conta) ? $conta->numero_nota_fiscal : old('numero_nota_fiscal') }}}">
											@if($errors->has('numero_nota_fiscal'))
											<div class="invalid-feedback">
												{{ $errors->first('numero_nota_fiscal') }}
											</div>
											@endif
										</div>

										<div class="form-group validated col-sm-12 col-lg-3">
											<label class="col-form-label" id="">Tipo de Pagamento</label>
											<select class="custom-select form-control" id="forma" name="tipo_pagamento">
												<option value="">Selecione o tipo de pagamento</option>
												@foreach(App\Models\ContaPagar::tiposPagamento() as $c)
												<option @isset($conta) @if($conta->tipo_pagamento == $c) selected @endif @else @if(old('tipo_pagamento') == $c) selected @endif @endif value="{{$c}}">{{$c}}</option>
												@endforeach
											</select>
										</div>

										@if(!isset($conta))
										<div class="form-group col-lg-2 col-md-9 col-sm-12">
											<label class="col-form-label">Conta Recebida</label>
											
											<div class="col-lg-12 col-xl-12">
												<span class="switch switch-outline switch-success">
													<label>
														<input @if(isset($conta) && $conta->status) checked 
														@endif type="checkbox" id="recebido" name="status" type="checkbox" id="status">
														<span></span>
													</label>
												</span>

											</div>
										</div>
										<div class="form-group validated col-lg-2 col-md-4 col-sm-6 div-recebido" style="display: none">
											<label class="col-form-label">Valor recebido</label>

											<input id="valor_recebido" type="text" class="form-control @if($errors->has('valor_recebido')) is-invalid @endif money" name="valor_recebido" value="{{{ isset($conta) ? number_format($conta->valor_integral, $casasDecimais, ',', '.') : old('valor_recebido') }}}">
											@if($errors->has('valor_recebido'))
											<div class="invalid-feedback">
												{{ $errors->first('valor_recebido') }}
											</div>
											@endif
										</div>
										@endif

										<div class="form-group validated col-12">
											<label class="col-form-label">Observação</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('observacao')) is-invalid @endif" name="observacao" value="{{{ isset($conta) ? $conta->observacao : old('observacao') }}}">
												@if($errors->has('observacao'))
												<div class="invalid-feedback">
													{{ $errors->first('observacao') }}
												</div>
												@endif
											</div>
										</div>

										@isset($conta)
										{!! __view_locais_select_edit("Local", $conta->filial_id) !!}
										@else
										{!! __view_locais_select() !!}
										@endif
									</div>

									@if(!isset($conta))
									<div class="row">

										<div class="form-group validated col-lg-4 col-md-4 col-sm-6">
											<label class="col-form-label">Salvar até este mês (opcional) </label>

											<input placeholder="mm/aa" type="text" class="form-control @if($errors->has('recorrencia')) is-invalid @endif" id="recorrencia" name="recorrencia" >
											@if($errors->has('recorrencia'))
											<div class="invalid-feedback">
												{{ $errors->first('recorrencia') }}
											</div>
											@endif
											<p style="color: red; margin-top: 5px;"> *Este campo deve ser preenchido se ouver recorrência para este registro
											</p>
										</div>
									</div>

									@endif

									<div class="row tbl" style="display: none">
										<div class="col-12 col-sm-6">
											<table class="table">
												<thead>
													<tr>
														<td>Data</td>
														<td>Valor</td>
													</tr>
												</thead>
												<tbody>
													
												</tbody>
											</table>
										</div>
									</div>
								</div>

							</div>
						</div>
					</div>
					<input type="hidden" name="parcelas" id="parcelas">
				</div>
				<div class="card-footer">

					<div class="row">
						<div class="col-xl-2">

						</div>
						<div class="col-lg-3 col-sm-6 col-md-4">
							<a style="width: 100%" class="btn btn-danger" href="/contasReceber">
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

@if(!isset($conta))
<input type="hidden" id="_token" value="{{csrf_token()}}" name="">

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
								<label class="col-form-label">UF</label>

								<select class="custom-select form-control" id="sigla_uf" name="sigla_uf">
									@foreach(App\Models\Cidade::estados() as $c)
									<option value="{{$c}}">{{$c}}
									</option>
									@endforeach
								</select>

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

							<div class="form-group validated col-sm-3 col-lg-3">
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

							<div class="form-group validated col-sm-3 col-lg-2">
								<label class="col-form-label" id="lbl_ie_rg">Limite de Venda</label>
								<div class="">
									<input type="text" id="limite_venda" class="form-control @if($errors->has('limite_venda')) is-invalid @endif money"  value="0">

								</div>
							</div>

						</div>
						<hr>
						<h5>Endereço de Faturamento</h5>
						<div class="row">
							<div class="form-group validated col-sm-8 col-lg-6">
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

							<div class="form-group validated col-sm-8 col-lg-4">
								<label class="col-form-label">Bairro</label>
								<div class="">
									<input id="bairro" type="text" class="form-control @if($errors->has('bairro')) is-invalid @endif">

								</div>
							</div>

							<div class="form-group validated col-sm-8 col-lg-2">
								<label class="col-form-label">CEP</label>
								<div class="">
									<input id="cep" type="text" class="form-control @if($errors->has('cep')) is-invalid @endif">
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
							<div class="form-group validated col-lg-4 col-md-6 col-sm-10">
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
@endif
@endsection
@section('javascript')
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.3/moment.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script type="text/javascript">
	var PARCELAS = []
	$(function () {
		changeRecebido()
	})
	$('#recorrencia').blur(() => {

		let recorrencia = $('#recorrencia').val()
		let valor = $('#valor').val()
		if(!valor){
			swal("Alerta", "Informe o valor", "warning")
		}else{
			if(recorrencia.length == 5){
				$('.tbl').css('display', 'block')
				let vencimento = $('#kt_datepicker_3').val()
				let dia = vencimento.split('/')[0]

				let mes = recorrencia.split('/')[0]
				let ano = "20"+recorrencia.split('/')[1]

				// vencimento = converteData(vencimento)
				if(dia == 31){
					dia = 30
				}
				if(mes == "02"){
					dia = 28
				}
				let d1 = moment(converteData(vencimento))
				let d2 = moment(ano + "-" + mes + "-" + dia)
				let duration = moment.duration(d2.diff(d1));
				let meses = parseInt(duration.asMonths())
				
				if(duration.asDays() <= 30){
					meses++
				}

				montaHtml(meses, vencimento, dia+"/"+recorrencia, dia)

			}else{
				$('.tbl').css('display', 'none')
			}
		}
	})

	function montaHtml(meses, vencimento, ultimoDia, dia){
		$('table tbody').html('')
		let valor = $('#valor').val()
		vencimento = converteData(vencimento)
		let venc = new Date(vencimento);
		console.log("vencimento", venc)
		// PARCELAS = []
		if(dia == '01'){
			venc = new Date(venc.setDate(venc.getDate()+1));
		}
		for(let i=0; i<=meses; i++){
			html = ''
			// let data = converteData(vencimento);
			if(i > 0){
				venc = new Date(venc.setMonth(venc.getMonth()+1));
				data = (venc.getDate() < 10 ? ("0" + venc.getDate()) : venc.getDate()) + 
				"/"+ ((venc.getMonth()+1) < 10 ? "0" + (venc.getMonth()+1) : (venc.getMonth()+1)) + 
				"/" + venc.getFullYear();
				data = converteData(data);

				html += '<tr>'
				html += '<td>'
				html += '<input value="'+data+'" type="date" class="form-control dt" '
				html += 'name="">'
				html += '</td>'
				html += '<td>'
				html += '<input value="'+valor+'" type="text" class="form-control valor" '
				html += 'name="">'
				html += '</td>'
				html += '</tr>'
				$('table tbody').append(html)
			}

		}
	}

	function converteData(data){
		let temp = data.split('/')
		return temp[2] + '-' + temp[1] + '-' + temp[0]
	}

	$('#form-register').submit(() => {
		PARCELAS = []
		$('table tbody tr').each(function(){
			let js = {
				vencimento : $(this).find('.dt').val(),
				valor : $(this).find('.valor').val(),
			}
			PARCELAS.push(js)
		})
		console.log(JSON.stringify(PARCELAS))

		$('#parcelas').val(JSON.stringify(PARCELAS))
	})

	$('#recebido').change(() => {
		changeRecebido()
	})

	function changeRecebido(){
		let valor = $('#valor').val()
		$('#valor_recebido').val(valor)
		let recebido = $('#recebido').is(':checked')
		if(recebido){
			$('.div-recebido').css('display', 'block')
		}else{
			$('.div-recebido').css('display', 'none')
		}
	}

	function novoCliente(){
		$('#modal-cliente').modal('show')
	}

	function consultaCadastro() {
		let cnpj = $('#cpf_cnpj').val();
		let uf = $('#sigla_uf').val();
		cnpj = cnpj.replace('.', '');
		cnpj = cnpj.replace('.', '');
		cnpj = cnpj.replace('-', '');
		cnpj = cnpj.replace('/', '');

		if (cnpj.length == 14 && uf.length != '--') {
			$('#btn-consulta-cadastro').addClass('spinner')

			$.ajax
			({
				type: 'GET',
				data: {
					cnpj: cnpj,
					uf: uf
				},
				url: path + 'nf/consultaCadastro',

				dataType: 'json',

				success: function (e) {
					$('#btn-consulta-cadastro').removeClass('spinner')

					if (e.infCons.infCad) {
						let info = e.infCons.infCad;

						$('#ie_rg').val(info.IE)
						$('#razao_social2').val(info.xNome)
						$('#nome_fantasia2').val(info.xFant ? info.xFant : info.xNome)

						$('#rua').val(info.ender.xLgr)
						$('#numero2').val(info.ender.nro)
						$('#bairro').val(info.ender.xBairro)
						let cep = info.ender.CEP;
						$('#cep').val(cep.substring(0, 5) + '-' + cep.substring(5, 9))

						findNomeCidade(info.ender.xMun, (res) => {

							let jsCidade = JSON.parse(res);
							if (jsCidade) {

								$('#kt_select2_4').val(jsCidade.id).change();
							}
						})

					} else {
						swal("Erro", e.infCons.xMotivo, "error")

					}
				}, error: function (e) {
					consultaAlternativa(cnpj, (data) => {

						if(data == false){
							swal("Alerta", "Nenhum retorno encontrado para este CNPJ, informe manualmente por gentileza", "warning")
						}else{
							$('#razao_social2').val(data.nome)
							$('#nome_fantasia2').val(data.nome)

							$('#rua').val(data.logradouro)
							$('#numero2').val(data.numero)
							$('#bairro').val(data.bairro)
							let cep = data.cep;
							$('#cep').val(cep.replace(".", ""))

							findNomeCidade(data.municipio, (res) => {
								let jsCidade = JSON.parse(res);

								if (jsCidade) {

									$('#kt_select2_4').val(jsCidade.id).change();
								}
							})
						}
					})
					$('#btn-consulta-cadastro').removeClass('spinner')
				}
			});
		}else{
			swal("Alerta", "Informe corretamente o CNPJ e UF", "warning")
		}
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

	function consultaAlternativa(cnpj, call){
		cnpj = cnpj.replace('.', '');
		cnpj = cnpj.replace('.', '');
		cnpj = cnpj.replace('-', '');
		cnpj = cnpj.replace('/', '');
		let res = null;
		$.ajax({

			url: 'https://www.receitaws.com.br/v1/cnpj/'+cnpj, 
			type: 'GET', 
			crossDomain: true, 
			dataType: 'jsonp', 
			success: function(data) 
			{ 
				$('#consulta').removeClass('spinner');

				if(data.status == "ERROR"){
					swal(data.message, "", "error")
					call(false)
				}else{
					call(data)
				}

			}, 
			error: function(e) { 
				$('#consulta').removeClass('spinner');
				console.log(e)

				call(false)

			},
		});
	}

	function findNomeCidade(nomeCidade, call) {

		$.get(path + 'cidades/findNome/' + nomeCidade)
		.done((success) => {
			call(success)
		})
		.fail((err) => {
			call(err)
		})
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
						limparCamposCliente()
						$('#kt_select2_3').append('<option value="'+res.id+'">'+ 
							res.razao_social+'</option>').change();
						$('#kt_select2_3').val(res.id).change();
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

	}
</script>
@endsection

