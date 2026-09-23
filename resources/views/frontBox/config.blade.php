@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/frenteCaixa/configSave">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">Atalhos para PDV</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<p>O atalho deve ser o nome separados por teclas '+'; Exemplo: <strong>ctrl+shift+b, ctrl+h</strong></p>
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">


									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Finalizar Venda</label>
											<div class="">
												<input id="finalizar" type="text" class="form-control @if($errors->has('finalizar')) is-invalid @endif" name="finalizar" value="{{{ isset($config) ? $config->finalizar : old('finalizar') }}}">
												@if($errors->has('finalizar'))
												<div class="invalid-feedback">
													{{ $errors->first('finalizar') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Reiniciar</label>
											<div class="">
												<input id="reiniciar" type="text" class="form-control @if($errors->has('reiniciar')) is-invalid @endif" name="reiniciar" value="{{{ isset($config) ? $config->reiniciar : old('reiniciar') }}}">
												@if($errors->has('reiniciar'))
												<div class="invalid-feedback">
													{{ $errors->first('reiniciar') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Editar desconto</label>
											<div class="">
												<input id="editar_desconto" type="text" class="form-control @if($errors->has('editar_desconto')) is-invalid @endif" name="editar_desconto" value="{{{ isset($config) ? $config->editar_desconto : old('editar_desconto') }}}">
												@if($errors->has('editar_desconto'))
												<div class="invalid-feedback">
													{{ $errors->first('editar_desconto') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Editar acréscimo</label>
											<div class="">
												<input id="editar_acrescimo" type="text" class="form-control @if($errors->has('editar_acrescimo')) is-invalid @endif" name="editar_acrescimo" value="{{{ isset($config) ? $config->editar_acrescimo : old('editar_acrescimo') }}}">
												@if($errors->has('editar_acrescimo'))
												<div class="invalid-feedback">
													{{ $errors->first('editar_acrescimo') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Editar observação</label>
											<div class="">
												<input id="editar_observacao" type="text" class="form-control @if($errors->has('editar_observacao')) is-invalid @endif" name="editar_observacao" value="{{{ isset($config) ? $config->editar_observacao : old('editar_observacao') }}}">
												@if($errors->has('editar_observacao'))
												<div class="invalid-feedback">
													{{ $errors->first('editar_observacao') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Setar valor recebido</label>
											<div class="">
												<input id="setar_valor_recebido" type="text" class="form-control @if($errors->has('setar_valor_recebido')) is-invalid @endif" name="setar_valor_recebido" value="{{{ isset($config) ? $config->setar_valor_recebido : old('setar_valor_recebido') }}}">
												@if($errors->has('setar_valor_recebido'))
												<div class="invalid-feedback">
													{{ $errors->first('setar_valor_recebido') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">
												Forma de pagamento dinheiro
											</label>
											<div class="">
												<input id="forma_pagamento_dinheiro" type="text" class="form-control @if($errors->has('forma_pagamento_dinheiro')) is-invalid @endif" name="forma_pagamento_dinheiro" value="{{{ isset($config) ? $config->forma_pagamento_dinheiro : old('forma_pagamento_dinheiro') }}}">
												@if($errors->has('forma_pagamento_dinheiro'))
												<div class="invalid-feedback">
													{{ $errors->first('forma_pagamento_dinheiro') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">
												Forma de pagamento débito
											</label>
											<div class="">
												<input id="forma_pagamento_debito" type="text" class="form-control @if($errors->has('forma_pagamento_debito')) is-invalid @endif" name="forma_pagamento_debito" value="{{{ isset($config) ? $config->forma_pagamento_debito : old('forma_pagamento_debito') }}}">
												@if($errors->has('forma_pagamento_debito'))
												<div class="invalid-feedback">
													{{ $errors->first('forma_pagamento_debito') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">
												Forma de pagamento crédito
											</label>
											<div class="">
												<input id="forma_pagamento_credito" type="text" class="form-control @if($errors->has('forma_pagamento_credito')) is-invalid @endif" name="forma_pagamento_credito" value="{{{ isset($config) ? $config->forma_pagamento_credito : old('forma_pagamento_credito') }}}">
												@if($errors->has('forma_pagamento_credito'))
												<div class="invalid-feedback">
													{{ $errors->first('forma_pagamento_credito') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">
												Forma de pagamento PIX
											</label>
											<div class="">
												<input id="forma_pagamento_pix" type="text" class="form-control @if($errors->has('forma_pagamento_pix')) is-invalid @endif" name="forma_pagamento_pix" value="{{{ isset($config) ? $config->forma_pagamento_pix : old('forma_pagamento_pix') }}}">
												@if($errors->has('forma_pagamento_pix'))
												<div class="invalid-feedback">
													{{ $errors->first('forma_pagamento_pix') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">
												Leitor ativo
											</label>
											<div class="">
												<input id="setar_leitor" type="text" class="form-control @if($errors->has('setar_leitor')) is-invalid @endif" name="setar_leitor" value="{{{ isset($config) ? $config->setar_leitor : old('setar_leitor') }}}">
												@if($errors->has('setar_leitor'))
												<div class="invalid-feedback">
													{{ $errors->first('setar_leitor') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">
												Setar quantidade
											</label>
											<div class="">
												<input id="setar_quantidade" type="text" class="form-control @if($errors->has('setar_quantidade')) is-invalid @endif" name="setar_quantidade" value="{{{ isset($config) ? $config->setar_quantidade : old('setar_quantidade') }}}">
												@if($errors->has('setar_quantidade'))
												<div class="invalid-feedback">
													{{ $errors->first('setar_quantidade') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">
												Finalizar venda com NFCe
											</label>
											<div class="">
												<input id="finalizar_fiscal" type="text" class="form-control @if($errors->has('finalizar_fiscal')) is-invalid @endif" name="finalizar_fiscal" value="{{{ isset($config) ? $config->finalizar_fiscal : old('finalizar_fiscal') }}}">
												@if($errors->has('finalizar_fiscal'))
												<div class="invalid-feedback">
													{{ $errors->first('finalizar_fiscal') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">
												Finalizar venda
											</label>
											<div class="">
												<input id="finalizar_nao_fiscal" type="text" class="form-control @if($errors->has('finalizar_nao_fiscal')) is-invalid @endif" name="finalizar_nao_fiscal" value="{{{ isset($config) ? $config->finalizar_nao_fiscal : old('finalizar_nao_fiscal') }}}">
												@if($errors->has('finalizar_nao_fiscal'))
												<div class="invalid-feedback">
													{{ $errors->first('finalizar_nao_fiscal') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">
												Digitos referencia produto balança
											</label>
											<div class="">
												<input id="balanca_digito_verificador" type="text" class="form-control @if($errors->has('balanca_digito_verificador')) is-invalid @endif" name="balanca_digito_verificador" value="{{{ isset($config) ? $config->balanca_digito_verificador : old('balanca_digito_verificador') }}}">
												@if($errors->has('balanca_digito_verificador'))
												<div class="invalid-feedback">
													{{ $errors->first('balanca_digito_verificador') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">
												Tipo unidade balança
											</label>
											<div class="">
												<select name="balanca_valor_peso" class="custom-select">
													<option @isset($config) @if($config->balanca_valor_peso == 1) selected @endif @endisset value="1">Valor</option>
													<option @isset($config) @if($config->balanca_valor_peso == 0) selected @endif @endisset value="0">Peso</option>
												</select>
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">
												Valor recebido automatico
											</label>
											<div class="switch switch-outline switch-info">
												<label class="">
													<input @if(isset($config) && $config->valor_recebido_automatico) checked @else
													@if(old('valor_recebido_automatico')) checked @endif @endif value="true" name="valor_recebido_automatico" class="red-text" type="checkbox">
													<span class="lever"></span>
												</label>
											</div>
										</div>


										<div class="form-group validated col-sm-6 col-lg-4">
											<label class="col-form-label">Impressora (QZ Tray)</label>
											<div class="input-group">
												<select id="impressora_nome" name="impressora_nome" class="form-control">
													<option value="{{ $config->impressora_nome ?? '' }}">{{ $config->impressora_nome ?? 'Selecione ou busque...' }}</option>
												</select>
												<div class="input-group-append">
													<button type="button" class="btn btn-info" id="btn-buscar-impressoras" onclick="carregarImpressorasQZ()">
														<i class="la la-search"></i> Buscar
													</button>
												</div>
											</div>
											<small class="form-text text-muted">Ligue o QZ Tray antes de clicar em Buscar.</small>
										</div>

										<div class="form-group validated col-sm-6 col-lg-4 col-12">
											<label class="col-form-label">Mercado pago public key</label>
											<div class="">
												<input id="mercadopago_public_key" type="text" class="form-control @if($errors->has('mercadopago_public_key')) is-invalid @endif" name="mercadopago_public_key" value="{{{ isset($config) ? $config->mercadopago_public_key : old('mercadopago_public_key') }}}">
												@if($errors->has('mercadopago_public_key'))
												<div class="invalid-feedback">
													{{ $errors->first('mercadopago_public_key') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-5 col-12">
											<label class="col-form-label">Mercado pago access token</label>
											<div class="">
												<input id="mercadopago_access_token" type="text" class="form-control @if($errors->has('mercadopago_access_token')) is-invalid @endif" name="mercadopago_access_token" value="{{{ isset($config) ? $config->mercadopago_access_token : old('mercadopago_access_token') }}}">
												@if($errors->has('mercadopago_access_token'))
												<div class="invalid-feedback">
													{{ $errors->first('mercadopago_access_token') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">
												Modelo de PDV
											</label>
											<div class="">
												<select id="modelo_pdv" name="modelo_pdv" class="custom-select">
													<option @isset($config) @if($config->modelo_pdv == 2) selected @endif @endisset value="2">PDV - 2</option>
													<option @isset($config) @if($config->modelo_pdv == 0) selected @endif @endisset value="0">PDV - 1</option>
													<!-- <option @isset($config) @if($config->modelo_pdv == 1) selected @endif @endisset value="1">PDV - 2</option> -->
													
												</select>
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">
												Modelo de Cupom
											</label>
											<div class="">
												<select id="cupom_modelo" name="cupom_modelo" class="custom-select">
													<option @isset($config) @if($config->cupom_modelo == 2) selected @endif @endisset value="2">Cupom 2</option>
													<option @isset($config) @if($config->cupom_modelo == 1) selected @endif @endisset value="1">Cupom 1</option>
													<option @isset($config) @if($config->cupom_modelo == 3) selected @endif @endisset value="3">Cupom A4</option>
												</select>
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">
												Tipo impressão pré-venda
											</label>
											<div class="">
												<select name="impressao_pre_venda" class="custom-select">
													<option @isset($config) @if($config->impressao_pre_venda == '80') selected @endif @endisset value="80">80mm</option>
													<option @isset($config) @if($config->impressao_pre_venda == 'a4') selected @endif @endisset value="a4">A4</option>
												</select>
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">
												Impressora largura valor entre 58 e 80
											</label>
											<div class="">
												<input id="impressora_modelo" type="text" class="form-control @if($errors->has('impressora_modelo')) is-invalid @endif" name="impressora_modelo" value="{{{ isset($config) ? $config->impressora_modelo : old('impressora_modelo') }}}">
												@if($errors->has('impressora_modelo'))
												<div class="invalid-feedback">
													{{ $errors->first('impressora_modelo') }}
												</div>
												@endif
											</div>
										</div>

                                        <div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">
												Tipo de pagamento padrão
											</label>
											<div class="">
												<select name="tipo_pagamento_padrao" class="custom-select">
													<option value="">Selecione o Tipo de pagamento</option>

                                                    @foreach(App\Models\VendaCaixa::tiposPagamento() as $key => $t)
                                                    <option value="{{$key}}" @if($config != null) @if($config->tipo_pagamento_padrao == $key) selected @endif @endif>{{$t}}</option>
                                                    @endforeach

												</select>
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2 div-produto-lateral d-none">
											<label class="col-form-label">
												Exibir produtos lateral
											</label>
											<div class="switch switch-outline switch-primary">
												<label class="">
													<input @if(isset($config) && $config->exibe_produtos) checked @else
													@if(old('exibe_produtos')) checked @endif @endif value="true" name="exibe_produtos" class="red-text" type="checkbox">
													<span class="lever"></span>
												</label>
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2 div-produto-lateral d-none">
											<label class="col-form-label">
												Exibir botão finalizar
											</label>
											<div class="switch switch-outline switch-dark">
												<label class="">
													<input @if(isset($config) && $config->botao_nao_fiscal) checked @else
													@if(old('botao_nao_fiscal')) checked @endif @endif value="true" name="botao_nao_fiscal" class="red-text" type="checkbox">
													<span class="lever"></span>
												</label>
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">
												Exibir modal cartões
											</label>
											<div class="switch switch-outline switch-info">
												<label class="">
													<input @if(isset($config) && $config->exibe_modal_cartoes) checked @else
													@if(old('exibe_modal_cartoes')) checked @endif @endif value="true" name="exibe_modal_cartoes" class="red-text" type="checkbox">
													<span class="lever"></span>
												</label>
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">
												Imprimir ticket de troca
											</label>
											<div class="switch switch-outline switch-warning">
												<label class="">
													<input @if(isset($config) && $config->imprimir_ticket_troca) checked @else
													@if(old('imprimir_ticket_troca')) checked @endif @endif value="true" name="imprimir_ticket_troca" class="red-text" type="checkbox">
													<span class="lever"></span>
												</label>
											</div>
										</div>

										<div class="form-group validated col-12">
											<label class="col-form-label">Mensagem padrão cupom</label>
											<div class="">
												<input id="mensagem_padrao_cupom" type="text" class="form-control @if($errors->has('mensagem_padrao_cupom')) is-invalid @endif" name="mensagem_padrao_cupom" value="{{{ isset($config) ? $config->mensagem_padrao_cupom : old('mensagem_padrao_cupom') }}}">
												@if($errors->has('mensagem_padrao_cupom'))
												<div class="invalid-feedback">
													{{ $errors->first('mensagem_padrao_cupom') }}
												</div>
												@endif
											</div>
										</div>

                                        <div class="col-12">
											<label class="col-form-label">
												Tipos de pagamento a serem mostrados
											</label>
											<div class="" style="display: grid;grid-template-columns: 1fr 1fr 1fr;">
                                                @foreach(App\Models\VendaCaixa::tiposPagamento() as $key => $t)
                                                <label>
                                                    <input type="checkbox" name="tipos_pagamento[]" value="{{$key}}" @if($config != null) @if(sizeof($config->tipos_pagamento) > 0 && in_array($key, $config->tipos_pagamento)) checked="true" @endif @endif>
                                                    {{$t}}
                                                </label>
                                                @endforeach
											</div>
										</div>
									</div>

								</div>

							</div>
						</div>
					</div>


                    <div class="row" style="padding: 20px;">
                        <div class="col-12 mt-4" style="border-top: 1px solid #EBEDF3; padding-top: 20px;">
                            <h5 class="text-info">Integrações de Delivery (Opcional)</h5>
                            <p>Preencha somente as integrações utilizadas neste caixa.</p>
                        </div>

                        <div class="form-group validated col-sm-6 col-lg-4">
                            <label class="col-form-label">ID Loja iFood</label>
                            <input type="text" class="form-control" name="id_ifood" value="{{{ isset($config) ? $config->id_ifood : old('id_ifood') }}}">
                        </div>

                        <div class="form-group validated col-sm-6 col-lg-4">
                            <label class="col-form-label">Token Zé Delivery</label>
                            <input type="text" class="form-control" name="id_ze_delivery" value="{{{ isset($config) ? $config->id_ze_delivery : old('id_ze_delivery') }}}">
                        </div>

                        <div class="form-group validated col-sm-6 col-lg-4">
                            <label class="col-form-label">Token Anota Aí</label>
                            <input type="text" class="form-control" name="id_anota_ai" value="{{{ isset($config) ? $config->id_anota_ai : old('id_anota_ai') }}}">
                        </div>
                    </div>

					<div class="card-footer">

						<div class="row">
							<div class="col-xl-2">

							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/frenteCaixa">
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
<script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.min.js"></script>
<script type="text/javascript">
	$(function(){
		selectModelo()
	})

	$('#modelo_pdv').change(() => {
		selectModelo()
	})

	function selectModelo(){
		let modelo = $('#modelo_pdv').val()
		if(modelo == 2){
			$('.div-produto-lateral').removeClass('d-none')
		}else{
			$('.div-produto-lateral').addClass('d-none')
		}
	}

	function carregarImpressorasQZ() {
		let $btn = $('#btn-buscar-impressoras');
		$btn.addClass('spinner spinner-white spinner-right').attr('disabled', true);

		if (typeof qz === 'undefined') {
			$btn.removeClass('spinner spinner-white spinner-right').removeAttr('disabled');
			swal("Erro", "Biblioteca QZ Tray não encontrada na página.", "error");
			return;
		}

		function executarBusca() {
			qz.printers.find().then(function(printers) {
				$btn.removeClass('spinner spinner-white spinner-right').removeAttr('disabled');
				let $select = $('#impressora_nome');
				let impressoraAtual = $select.val();
				$select.empty().append('<option value="">-- Selecione a Impressora --</option>');
				printers.forEach(function(printerName) {
					let selected = (printerName === impressoraAtual) ? 'selected' : '';
					$select.append(`<option value="${printerName}" ${selected}>${printerName}</option>`);
				});
				swal("Sucesso!", `${printers.length} impressoras encontradas.`, "success");
			}).catch(function(e) {
				$btn.removeClass('spinner spinner-white spinner-right').removeAttr('disabled');
				console.error("Erro QZ:", e);
				swal("Erro", "A requisição de busca foi recusada pelo QZ Tray.", "error");
			});
		}

		if (!qz.websocket.isActive()) {
			qz.websocket.connect().then(executarBusca).catch(function(err) {
				$btn.removeClass('spinner spinner-white spinner-right').removeAttr('disabled');
				console.error("Erro Conexão QZ:", err);
				swal("Erro", "Não foi possível conectar ao QZ Tray. Verifique se o aplicativo está em execução.", "error");
			});
		} else {
			executarBusca();
		}
	}

</script>
@endsection
