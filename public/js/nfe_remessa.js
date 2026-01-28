$(function () {
	let semCertificado = $('#semCertificado').val() ? $('#semCertificado').val() : false;
	if(semCertificado){
		// swal("Aviso", "Os botões inferiores seram mostrados após o upload de certificado", "warning")
	}
})


function removerVenda(id){
	let senha = $('#pass').val()
	if(senha != ""){

		swal({
			title: 'Cancelamento de venda',
			text: 'Informe a senha!',
			content: {
				element: "input",
				attributes: {
					placeholder: "Digite a senha",
					type: "password",
				},
			},
			button: {
				text: "Cancelar!",
				closeModal: false,
				type: 'error'
			},
			confirmButtonColor: "#DD6B55",
		}).then(v => {
			if(v.length > 0){
				$.get(path+'configNF/verificaSenha', {senha: v})
				.then(
					res => {
						location.href="/nferemessa/delete/"+id;
					},
					err => {
						swal("Erro", "Senha incorreta", "error")
						.then(() => {
							location.reload()
						});
					}
					)
			}else{
				location.reload()
			}
		})
	}else{
		location.href="/nferemessa/delete/"+id;
	}
}

function transmitirNFe(id){
	if(!EMITINDO){
		EMITINDO = true;
		$('#btn_trnasmitir_grid_'+id).addClass('spinner');
		$('#btn_trnasmitir_grid_'+id).removeAttr('disabled', true);

		let token = $('#_token').val();
		$.ajax
		({
			type: 'POST',
			data: {
				vendaId: id,
				_token: token
			},
			url: path + 'nferemessa/transmitir',
			dataType: 'json',
			success: function(e){
				EMITINDO = false;
				let recibo = e;
				let retorno = recibo.substring(0,4);
				let mensagem = recibo.substring(5,recibo.length);
				if(retorno == 'Erro'){
					let m = JSON.parse(mensagem);
					swal("Erro", "[" + m.protNFe.infProt.cStat + "] : " + m.protNFe.infProt.xMotivo, "error")
				}
				else if(e == 'Apro'){
					swal("Cuidado!", "Esta NF já esta aprovada, não é possível enviar novamente!", "warning");
				}
				else{
					swal("Sucesso", "NFe gerada com sucesso RECIBO: "+recibo, "success")
					.then(() => {
						window.open(path+"nferemessa/imprimir/"+id, "_blank");
						location.reload();
					})
				}

				$('#btn_trnasmitir_grid_'+id).removeClass('spinner');
				$('#btn_trnasmitir_grid_'+id).removeClass('disabled');

			}, error: function(e){
				EMITINDO = false;
				try{
					let js = e.responseJSON;

					if(js.message){

						swal("Erro!", js.message, "warning")

					}else if(e.status == 407){
						swal("", js, "warning")

					}else{
						let err = "";
						js.map((v) => {
							err += v + "\n";
						});

						swal("Erro!", err, "warning")
					}
				}catch{
					console.log(e)
					swal("", e, "warning")

				}
				$('#btn_trnasmitir_grid_'+id).removeClass('spinner');
				$('#btn_trnasmitir_grid_'+id).removeClass('disabled');

			}
		});
	}
}
var EMITINDO = false;
function enviar(){
	// $('#preloader1').css('display', 'block');
	if(!EMITINDO){
		EMITINDO = true;
		$('#btn-enviar').addClass('spinner')
		$('#btn-enviar').attr('disabled', 'disabled');

		let id = 0;
		$('#body tr').each(function(){
			if($(this).find('#checkbox input').is(':checked'))
				id = $(this).find('#id').html();
		})

		let token = $('#_token').val();
		$.ajax
		({
			type: 'POST',
			data: {
				nfeid: id,
				_token: token
			},
			url: path + 'nferemessa/transmitir',
			dataType: 'json',
			success: function(e){
				EMITINDO = false;
				let recibo = e;
				let retorno = recibo.substring(0,4);
				let mensagem = recibo.substring(5,recibo.length);
				if(retorno == 'Erro'){
					try{
						let m = JSON.parse(mensagem);
						try{
							swal("Erro", "[" + m.protNFe.infProt.cStat + "] : " + m.protNFe.infProt.xMotivo, "error")
						}catch{
							swal("Erro", "[" + m.cStat + "] : " + m.xMotivo, "error")
						}
					}catch{
						swal("Erro", mensagem, "error")
					}
				}
				else if(e == 'Apro'){
					swal("Cuidado!", "Esta NF já esta aprovada, não é possível enviar novamente!", "warning")

				}
				else{
					swal("Sucesso", "NFe gerada com sucesso RECIBO: "+recibo, "success")
					.then(() => {
						window.open(path+"nferemessa/imprimir/"+id, "_blank");
						location.reload();
					})
				}


				$('#btn-enviar').removeClass('spinner')
				$('#btn-enviar').removeAttr('disabled');

			}, error: function(e){
				EMITINDO = false;
				try{
					let js = e.responseJSON;

					if(js.message){

						swal("Erro!", js.message, "warning")

					}else if(e.status == 407){
						swal("Algo deu errrado", js, "error")

					}else{
						let err = "";
						js.map((v) => {
							err += v + "\n";
						});

						swal("Erro!", err, "warning")

					}
				}catch{
					console.log(e)
					swal("", e.responseText, "warning")

				}

				$('#btn-enviar').removeAttr('disabled');
				$('#btn-enviar').removeClass('spinner')

			}
		});
	}
}

function redireciona(){
	location.reload();
}

function imprimir(){
	let id = 0;
	let cont = 0;
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			id = $(this).find('#id').html();
			cont++
		}
	})

	if(cont > 1){
		swal("Atenção", "Selecione um documento para impressão", "warning")
	}else{
		window.open(path+"nferemessa/imprimir/"+id, "_blank");
	}
}

function consultarNFe(id){
	$('#btn_consulta_grid_'+id).addClass('spinner')
	$('#btn_consulta_grid_'+id).addClass('disabled')
	let token = $('#_token').val();
	$.ajax
	({
		type: 'POST',
		data: {
			id: id,
			_token: token
		},
		url: path + 'nferemessa/consultar',
		dataType: 'json',
		success: function(e){
			let js = JSON.parse(e)
			if(js.cStat != '656'){
				// $('#motivo').html(js.xMotivo);
				// $('#chave').html(js.chNFe);
				// $('#protocolo').html(js.protNFe.infProt.nProt);

				swal("Sucesso", "Status: " + js.xMotivo + " - chave: " + js.chNFe + ", protocolo: " + js.protNFe.infProt.nProt, "success")

				$('#btn_consulta_grid_'+id).removeClass('spinner')
				$('#btn_consulta_grid_'+id).removeClass('disabled')
			}else{

				swal("Erro", "Consumo indevido!", "error")
			}
			$('#btn-consultar').removeClass('spinner')

		}, error: function(e){

			swal("Erro", e.responseText, "error")

			$('#btn_consulta_grid_'+id).removeClass('spinner')
			$('#btn_consulta_grid_'+id).removeClass('disabled')
		}
	});
}

function imprimirCCe(){
	let id = 0;
	let cont = 0;
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			id = $(this).find('#id').html();
			cont++
		}
	})

	if(cont > 1){
		swal("Atenção", "Selecione um documento para impressão", "warning")
	}else{
		window.open(path+"nferemessa/imprimirCce/"+id, "_blank");
	}
}

function imprimirCancela(){
	let id = 0;
	let cont = 0;
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			id = $(this).find('#id').html();
			cont++
		}
	})

	if(cont > 1){

		swal("Atenção", "Selecione um documento para impressão", "warning")

	}else{
		window.open(path+"nferemessa/imprimirCancela/"+id, "_blank");
	}
}

function consultar(){
	let id = 0;
	let cont = 0;
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			id = $(this).find('#id').html();
			cont++;
		}
	})

	if(cont > 1){

		swal("Atenção", "Selecione um documento para consultar", "warning")

	}else{
		$('#btn-consultar').addClass('spinner')
		let token = $('#_token').val();
		$.ajax
		({
			type: 'POST',
			data: {
				id: id,
				_token: token
			},
			url: path + 'nferemessa/consultar',
			dataType: 'json',
			success: function(e){

				let js = JSON.parse(e)
				try{
					if(js.cStat != '656'){
						$('#motivo').html(js.xMotivo);
						$('#chave').html(js.chNFe);
						$('#protocolo').html(js.protNFe.infProt.nProt);
						$('#modal2').modal('show');
						$('#preloader1').css('display', 'none');
					}else{
						alert('Consumo indevido!')
					}
				}catch{
					console.log(js)
					swal("Erro", js.xMotivo, "error")
				}
				$('#btn-consultar').removeClass('spinner')

			}, error: function(e){
				console.log(e)
				swal("Erro", e.responseText, "error")

				// $('#preloader1').css('display', 'none');
				$('#btn-consultar').removeClass('spinner')

			}
		});
	}
}

function setarNumero(buscarCliente = false){

	let id = 0;
	let nf = 0;
	let cont = 0;
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			nf = $(this).find('#id').html();
			$('#numero_cancelamento').html(nf)
			$('#numero_correcao').html(nf)
			$('#numero_nf').html(nf)

			if(buscarCliente){
				buscarDadosCliente();
			}

			cont++;
		}
	})
	
	if(cont > 1){
		swal("Atenção", "Selecione apenas um documento para continuar!", "warning")
	}
}

function buscarDadosCliente(){
	let id = 0;
	let cont = 0;

	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			id = $(this).find('#id').html();
			cont++;
		}
	})

	if(cont > 1){
		swal("Atenção", "Selecione apenas um documento para continuar!", "warning")
	}else{

		$.get(path+'nferemessa/consultar_cliente/'+id)
		.done(function(data){
			data = JSON.parse(data)

			$('#email').val(data.email)
			$('#venda_id').val(id)

			if(data.email){
				$('#info-email').html('*Este é o email do cadastro');
			}else{
				$('#info-email').html('*Este cliente não possui email cadastrado');
			}
		})
		.fail(function(err){
			console.log(err)
		})
	}
}

function cancelarNFe(id, nf){
	$('#modal1_aux').modal('show')
	$('#numero_cancelamento2').html(nf)
	$('#id_cancela').val(id)
}

function cancelar2(){
	// $('#preloader5').css('display', 'block');

	let id = $('#id_cancela').val();
	
	let justificativa = $('#justificativa2').val();
	if(justificativa.length < 15){
		swal("Alerta", "Informe no mínimo 15 caraceres", "warning")
		return;
	}
	$('#btn-cancelar-3').addClass('spinner')
	
	let token = $('#_token').val();
	$.ajax
	({
		type: 'POST',
		data: {
			id: id,
			justificativa: justificativa,
			_token: token
		},
		url: path + 'nferemessa/cancelar',
		dataType: 'json',
		success: function(e){

			let js = JSON.parse(e);

			$('#btn-cancelar-3').removeClass('spinner')
			swal("Sucesso", js.retEvento.infEvento.xMotivo, "success")
			.then(() => {
				window.open(path+"nferemessa/imprimirCancela/"+id, "_blank");
				location.reload();
			})


		}, error: function(e){
			console.log(e)
			let js = e.responseJSON;
			try{
				swal("Erro", js.retEvento.infEvento.xMotivo, "error");
			}catch{
				swal("Erro", e.responseText, "error");
			}

			$('#btn-cancelar-3').removeClass('spinner')

		}
	});
}

function cancelar(){
	// $('#preloader5').css('display', 'block');
	let id = 0;
	let cont = 0;
	let justificativa = $('#justificativa').val();
	if(justificativa.length < 15){
		swal("Alerta", "Informe no mínimo 15 caraceres", "warning")
		return;
	}
	$('#btn-cancelar-2').addClass('spinner')
	
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			id = $(this).find('#id').html();
			cont++;
		}
	})

	if(cont > 1){
		swal("Atenção", "Selecione apenas um documento para cancelar!", "warning")
	}else{
		let token = $('#_token').val();
		$.ajax
		({
			type: 'POST',
			data: {
				id: id,
				justificativa: justificativa,
				_token: token
			},
			url: path + 'nferemessa/cancelar',
			dataType: 'json',
			success: function(e){

				let js = JSON.parse(e);

				$('#btn-cancelar-2').removeClass('spinner')
				// alert(js.retEvento.infEvento.xMotivo)
				swal("Sucesso", js.retEvento.infEvento.xMotivo, "success")
				.then(() => {
					window.open(path+"nferemessa/imprimirCancela/"+id, "_blank");
					location.reload();
				})

				// $('#preloader5').css('display', 'none');

			}, error: function(e){
				console.log(e)
				let js = e.responseJSON;
				try{
					swal("Erro", js.retEvento.infEvento.xMotivo, "error");
				}catch{
					swal("Erro", e.responseText, "error");
				}

				// $('#preloader5').css('display', 'none');
				$('#btn-cancelar-2').removeClass('spinner')

			}
		});
	}
}

function corrigirrNFe(id, nf){
	$('#modal4_aux').modal('show')
	$('#numero_correcao_aux').html(nf)
	$('#id_correcao').val(id)
}

function cartaCorrecao(){
	// $('#preloader4').css('display', 'block');
	$('#btn-corrigir-2').addClass('spinner');
	let id = 0;
	let cont = 0;
	let correcao = $('#correcao').val();
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			id = $(this).find('#id').html();
			cont++;
		}
	})

	if(cont > 1){
		swal("Atenção", "Selecione apenas um documento para continuar!", "warning")

	}else{

		let token = $('#_token').val();
		$.ajax
		({
			type: 'POST',
			data: {
				id: id,
				correcao: correcao,
				_token: token
			},
			url: path + 'nferemessa/cartaCorrecao',
			dataType: 'json',
			success: function(e){

				try{
					let js = JSON.parse(e);

					$('#btn-corrigir-2').removeClass('spinner');

					swal("Sucesso", js.retEvento.infEvento.xMotivo, "success")
					.then(() => {
						window.open(path+"nferemessa/imprimirCce/"+id, "_blank");
						location.reload()
					})
				}catch{
					swal("Erro", e, "error")
					.then(() => {
						location.reload()
					})
				}
				// $('#preloader4').css('display', 'none');

			}, error: function(e){
				console.log(e)
				swal("Erro", e.responseText, "error")
				$('#btn-corrigir-2').removeClass('spinner');

				// $('#preloader4').css('display', 'none');
			}
		});
	}
}

function cartaCorrecaoAux(){
	$('#btn-corrigir-2-aux').addClass('spinner')
	$('#btn-corrigir-2-aux').addClass('disabled')
	
	let token = $('#_token').val();
	let id = $('#id_correcao').val()
	let correcao = $('#correcao_aux').val()
	$.ajax
	({
		type: 'POST',
		data: {
			id: id,
			correcao: correcao,
			_token: token
		},
		url: path + 'nferemessa/cartaCorrecao',
		dataType: 'json',
		success: function(e){

			try{
				let js = JSON.parse(e);

				$('#btn-corrigir-2-aux').removeClass('spinner')
				$('#btn-corrigir-2-aux').removeClass('disabled')

				swal("Sucesso", js.retEvento.infEvento.xMotivo, "success")
				.then(() => {
					window.open(path+"nferemessa/imprimirCce/"+id, "_blank");
					location.reload()
				})
			}catch{
				swal("Erro", e, "error")
				.then(() => {
					location.reload()
				})
			}
				// $('#preloader4').css('display', 'none');

			}, error: function(e){
				console.log(e)
				swal("Erro", e.responseText, "error")

				$('#btn-corrigir-2-aux').removeClass('spinner')
				$('#btn-corrigir-2-aux').removeClass('disabled')

				// $('#preloader4').css('display', 'none');
			}
		});

}

function inutilizar(){

	let justificativa = $('#justificativa_inut').val();
	let nInicio = $('#nInicio').val();
	let nFinal = $('#nFinal').val();

	if(!justificativa){
		swal("Erro", "Informe a justificativa", "error")
		return;
	}

	if(!nInicio || !nFinal){
		swal("Erro", "Informe a Número inicial e final", "error")
		return;
	}
	
	// $('#preloader3').css('display', 'block');
	$('#btn-inut-2').addClass('spinner')

	let token = $('#_token').val();
	$.ajax
	({
		type: 'POST',
		data: {
			justificativa: justificativa,
			nInicio: nInicio,
			nFinal: nFinal,
			_token: token
		},
		url: path + 'nferemessa/inutilizar',
		dataType: 'json',
		success: function(e){

			if(e.infInut.cStat == '102'){
				// alert("cStat:" + e.infInut.cStat + "\n" + e.infInut.xMotivo);
				swal("Sucesso", "["+e.infInut.cStat + "] " + e.infInut.xMotivo, "success")
				.then(() => {
					location.reload()
				})
			}else{
				swal("Erro", "["+e.infInut.cStat + "] " + e.infInut.xMotivo, "error")
				.then(() => {
					location.reload()
				})
			}


			// $('#preloader3').css('display', 'none');
			$('#btn-inut-2').removeClass('spinner')

		}, error: function(e){
			console.log(e)
			swal("Erro", e.responseText, "error")
			$('#preloader1').css('display', 'none');
		}
	});
	
}

$(function () {
	validaBtns();
})

$('#checkbox input').click(() => {
	validaBtns();
})

function validaBtns(){

	let cont = 0;
	let estado = "";
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			id = $(this).find('#id').html();
			estado = $(this).find('#estado_'+id).val();
			cont++;
		}
	})

	if(cont > 1 || cont == 0){
		desabilitaBotoes();
	}else{
		habilitaBotoes();
		if(estado == 'novo'){
			$('#btn-enviar').removeClass("disabled");
			$('#btn-imprimir').addClass("disabled", true);
			$('#btn-consultar').addClass("disabled", true);
			$('#btn-cancelar').addClass("disabled", true);
			$('#btn-inutilizar').removeClass("disabled");
			$('#btn-correcao').addClass("disabled", true);
			$('#btn-xml').removeClass("disabled", true);

			$('#btn-danfe').removeClass("disabled");
			$('#btn-imprimir-cce').addClass("disabled", true);
			$('#btn-imprimir-cancelar').addClass("disabled", true);
			$('#btn-baixar-xml').addClass("disabled", true);

		} else if(estado == 'rejeitado'){
			$('#btn-enviar').removeClass("disabled");
			$('#btn-imprimir').addClass("disabled", true);
			$('#btn-inutilizar').removeClass("disabled");
			$('#btn-correcao').addClass("disabled", true);

			$('#btn-consultar').removeClass("disabled");
			$('#btn-cancelar').addClass("disabled", true);
			$('#btn-xml').removeClass("disabled", true);

			$('#btn-danfe').removeClass("disabled");
			$('#btn-imprimir-cce').addClass("disabled", true);
			$('#btn-imprimir-cancelar').addClass("disabled", true);
			$('#btn-baixar-xml').addClass("disabled", true);


		} else if(estado == 'cancelado'){
			$('#btn-enviar').addClass("disabled", true);
			$('#btn-inutilizar').addClass("disabled", true);
			$('#btn-correcao').addClass("disabled", true);
			$('#btn-imprimir').addClass("disabled", true);
			$('#btn-consultar').removeClass("disabled");
			$('#btn-cancelar').addClass("disabled", true);
			$('#btn-xml').removeClass("disabled", true);

			$('#btn-danfe').removeClass("disabled");
			$('#btn-imprimir-cce').addClass("disabled", true);
			$('#btn-imprimir-cancelar').removeClass("disabled");
			$('#btn-baixar-xml').addClass("disabled", true);

		} else if(estado == 'aprovado'){
			$('#btn-enviar').addClass("disabled", true);
			$('#btn-inutilizar').addClass("disabled", true);
			$('#btn-imprimir').removeClass("disabled");
			$('#btn-consultar').removeClass("disabled");
			$('#btn-cancelar').removeClass("disabled");
			$('#btn-correcao').removeClass("disabled");
			$('#btn-xml').removeClass("disabled");
			$('#btn-baixar-xml').removeClass("disabled");
			

			$('#btn-danfe').addClass("disabled", true);
			$('#btn-imprimir-cce').removeClass("disabled");
			$('#btn-imprimir-cancelar').addClass("disabled", true);
		}

	}
}

function desabilitaBotoes(){
	$('#btn-enviar').addClass("disabled");
	$('#btn-imprimir').addClass("disabled");
	$('#btn-consultar').addClass("disabled");
	$('#btn-cancelar').addClass("disabled");
	$('#btn-correcao').addClass("disabled");
	// $('#btn-inutilizar').addClass("disabled");
	$('#btn-xml').addClass("disabled");
	$('#btn-baixar-xml').addClass("disabled");
	$('#btn-danfe').addClass("disabled");
	$('#btn-imprimir-cce').addClass("disabled");
	$('#btn-imprimir-cancelar').addClass("disabled");

}


function habilitaBotoes(){
	$('#btn-enviar').removeClass("disabled");
	$('#btn-imprimir').removeClass("disabled");
	$('#btn-consultar').removeClass("disabled");
	$('#btn-cancelar').removeClass("disabled");
	$('#btn-correcao').removeClass("disabled");
	$('#btn-inutilizar').removeClass("disabled");
	$('#btn-xml').removeClass("disabled");

}

function enviarEmailXMl(){
	$('#btn-send').addClass('spinner');
	$('#btn-send').addClass('disabled');
	
	let id = 0;
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			id = $(this).find('#id').html();
		}
	})

	let email = $('#email').val();

	$.get(path+'nferemessa/enviarXml', {id: id, email: email})
	.done(function(data){

		$('#btn-send').removeClass('spinner');
		$('#btn-send').removeClass('disabled');
		swal("Sucesso", "Email enviado!!", "success")
		.then(() => {
			$('#modal5').modal('hide')
		})
	})
	.fail(function(err){
		console.log(err)
		$('#btn-send').removeClass('spinner');
		$('#btn-send').removeClass('disabled');
		swal("Erro", "Erro ao enviar email!!", "error")

	})
}

function modalWhatsApp(){
	$('#modal-whatsApp').modal('show')
}

function baixarXml(){
	let id = 0;
	let cont = 0;
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			id = $(this).find('#id').html();
			cont++
		}
	})

	if(cont > 1){
		Materialize.toast('Selecione apenas um documento para impressão!', 5000)
	}else{
		window.open(path+"nferemessa/baixarXml/"+id, "_blank");
	}
}

$('#btn-danfe').click(() => {
	let id = 0
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked'))
			id = $(this).find('#id').html();
	})
	window.open(path + 'nferemessa/rederizarDanfe/' + id);
})

