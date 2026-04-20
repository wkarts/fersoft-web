
function stripBom(value){
	if(typeof value !== 'string') return value;
	return value.replace(/^\uFEFF/, '').trim();
}

function safeParseJson(value){
	if(typeof value !== 'string') return value;
	let parsed = stripBom(value);
	for(let i=0;i<3;i++){
		if(typeof parsed !== 'string') return parsed;
		const t = parsed.trim();
		if(!t) return t;
		if(!((t.startsWith('{') && t.endsWith('}')) || (t.startsWith('[') && t.endsWith(']')))){
			return parsed;
		}
		try{
			parsed = JSON.parse(t);
		}catch(e){
			return value;
		}
	}
	return parsed;
}

function normalizeMessageText(value){
	if(value === null || value === undefined) return '';
	let text = String(value);
	text = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
	text = text.replace(/\n{3,}/g, '\n\n');
	text = text.replace(/[\t ]{2,}/g, ' ');
	return text.trim();
}

function extractFiscalMessage(payload){
	if(payload === null || payload === undefined) return '';
	if(typeof payload === 'string'){
		const parsed = safeParseJson(payload);
		if(parsed !== payload){
			return extractFiscalMessage(parsed);
		}
		return normalizeMessageText(payload);
	}
	if(Array.isArray(payload)){
		for(const item of payload){
			const msg = extractFiscalMessage(item);
			if(msg) return msg;
		}
		return '';
	}
	if(typeof payload === 'object'){
		const preferredKeys = ['mensagem','message','xMotivo','error','erro','detail','details','title'];
		for(const key of preferredKeys){
			if(payload[key]){
				const msg = extractFiscalMessage(payload[key]);
				if(msg) return msg;
			}
		}
		if(payload.cStat && payload.xMotivo){
			return normalizeMessageText('[' + payload.cStat + '] - ' + payload.xMotivo);
		}
		if(payload.protNFe && payload.protNFe.infProt){
			const inf = payload.protNFe.infProt;
			if(inf.cStat || inf.xMotivo){
				return normalizeMessageText('[' + (inf.cStat || '') + '] - ' + (inf.xMotivo || ''));
			}
		}
		if(payload.retEvento && payload.retEvento.infEvento){
			const inf = payload.retEvento.infEvento;
			if(inf.cStat || inf.xMotivo){
				return normalizeMessageText('[' + (inf.cStat || '') + '] - ' + (inf.xMotivo || ''));
			}
		}
		if(payload.payload){
			const msg = extractFiscalMessage(payload.payload);
			if(msg) return msg;
		}
		if(payload.responseJSON){
			const msg = extractFiscalMessage(payload.responseJSON);
			if(msg) return msg;
		}
		if(payload.responseText){
			const msg = extractFiscalMessage(payload.responseText);
			if(msg) return msg;
		}
	}
	return '';
}

function showFiscalError(title, payload, fallback){
	const msg = extractFiscalMessage(payload) || fallback || 'Algo deu errado';
	swal(title || 'Erro', msg, 'error');
}

$(function () {
	let semCertificado = $('#semCertificado').val() ? $('#semCertificado').val() : false;
	if(semCertificado){
		// swal("Aviso", "Os botões inferiores seram mostrados após o upload de certificado", "warning")
	}
})

$('.btn-consulta-status').click(() => {
	console.clear()
	$('.btn-consulta-status').addClass('spinner')
	let token = $('#_token').val();
	$.post(path + 'nf/consultaStatusSefaz',{_token: token})
	.done((res) => {
		$('.btn-consulta-status').removeClass('spinner')
		console.log(res)
		let msg = "cStat: " + res.cStat
		msg += "\nMotivo: " + res.xMotivo
		msg += "\nAmbiente: " + (res.tpAmb == 2 ? "Homologação" : "Produção")
		msg += "\nverAplic: " + res.verAplic
		
		swal("Sucesso", msg, "success")
	})
	.fail((err) => {
		$('.btn-consulta-status').removeClass('spinner')
		console.log(err)
		try{
			showFiscalError("Erro", err, "Algo deu errado")
		}catch{
			swal("Erro", "Algo deu errado", "error")
		}
	})
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
						location.href="/vendas/delete/"+id;
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
		location.href="/vendas/delete/"+id;
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
			url: path + 'nf/gerarNf',
			dataType: 'json',
			success: function(e){
                                EMITINDO = false;

                                if (handleTransmissaoResponse(e, id)) {
                                        $('#btn_trnasmitir_grid_'+id).removeClass('spinner');
                                        $('#btn_trnasmitir_grid_'+id).removeClass('disabled');
                                        return;
                                }

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
                                        swal({
                                                title: "Sucesso",
                                                text: "NFe gerada com sucesso RECIBO: "+recibo+", deseja enviar email para o cliente?",
                                                icon: "success",
                                                buttons: ["Não", 'Sim'],
                                                dangerMode: true,
                                        }).then((v) => {
                                                setarNumero(true)
                                                window.open(path+"nf/imprimir/"+id, "_blank");
                                                if (v) {
                                                        $('#modal5').modal('show')
                                                } else {
                                                        location.reload();
                                                }
                                        });
                                }

                                $('#btn_trnasmitir_grid_'+id).removeClass('spinner');
                                $('#btn_trnasmitir_grid_'+id).removeClass('disabled');

			}, error: function(e){
				EMITINDO = false;
				try{
					let js = e.responseJSON;

					if(js.message){

						showFiscalError("Erro", js.message)

					}else if(e.status == 407){
						showFiscalError("Erro", js)

					}else{
						let err = "";
						js.map((v) => {
							err += v + "\n";
						});

						showFiscalError("Erro", err)
					}
				}catch{
					console.log(e)
					showFiscalError("Erro", e)

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
				vendaId: id,
				_token: token
			},
			url: path + 'nf/gerarNf',
			dataType: 'json',
			success: function(e){
                                EMITINDO = false;
                                if (handleTransmissaoResponse(e, id)) {
                                        $('#btn-enviar').removeClass('spinner')
                                        $('#btn-enviar').removeAttr('disabled');
                                        return;
                                }

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
                                        swal("Cuidado!", "Esta NF já esta aprovada, não é possível enviar novamente!", "warning");

                                }
                                else{
                                        swal({
                                                title: "Sucesso",
                                                text: "NFe gerada com sucesso RECIBO: "+recibo+", deseja enviar email para o cliente?",
                                                icon: "success",
                                                buttons: ["Não", 'Sim'],
                                                dangerMode: true,
                                        }).then((v) => {
                                                setarNumero(true)
                                                window.open(path+"nf/imprimir/"+id, "_blank");
                                                if (v) {
                                                        $('#modal5').modal('show')
                                                } else {
                                                        location.reload();
                                                }
                                        });
                                }


                                $('#btn-enviar').removeClass('spinner')
                                $('#btn-enviar').removeAttr('disabled');

			}, error: function(e){
				EMITINDO = false;
				try{
					let js = e.responseJSON;

					if(js.message){

						showFiscalError("Erro", js.message)

					}else if(e.status == 407){
						showFiscalError("Erro", js)

					}else{
						let err = "";
						js.map((v) => {
							err += v + "\n";
						});

						showFiscalError("Erro", err)

					}
				}catch{
					console.log(e)
					showFiscalError("Erro", e, "Algo deu errado")

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

function handleTransmissaoResponse(resp, vendaId) {
        if (!resp || typeof resp !== 'object') return false;

        const temChave = resp.success !== undefined || resp.status !== undefined || resp.cStat !== undefined;
        if (!temChave) return false;

        const status = resp.status || '';
        const mensagem = resp.mensagem || resp.xMotivo || '';
        const protocolo = resp.protocolo ? ` Protocolo: ${resp.protocolo}.` : '';
        const recibo = resp.recibo ? ` Recibo: ${resp.recibo}.` : '';

        if (resp.success && status === 'autorizado') {
                swal({
                        title: "Sucesso",
                        text: `NF-e autorizada! ${mensagem}${protocolo}${recibo} Deseja enviar email para o cliente?`,
                        icon: "success",
                        buttons: ["Não", 'Sim'],
                        dangerMode: true,
                }).then((v) => {
                        setarNumero(true)
                        window.open(path+"nf/imprimir/"+vendaId, "_blank");
                        if (v) {
                                $('#modal5').modal('show')
                        } else {
                                location.reload();
                        }
                });
                return true;
        }

        if (status === 'denegado') {
                swal("Denegada", mensagem || "NF-e denegada.", "warning").then(() => location.reload());
                return true;
        }

        if (resp.success) {
                swal("Sucesso", mensagem || "NF-e transmitida com sucesso.", "success").then(() => location.reload());
                return true;
        }

        if (status === 'em_processamento') {
                swal("Processando", mensagem || "Lote em processamento, consulte em instantes.", "info");
                return true;
        }

        swal("Erro", mensagem || "Falha ao transmitir NF-e.", "error");
        return true;
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
		window.open(path+"nf/imprimir/"+id, "_blank");
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
		url: path + 'nf/consultar',
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

			showFiscalError("Erro", e, "Algo deu errado")

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
		window.open(path+"nf/imprimirCce/"+id, "_blank");
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
		window.open(path+"nf/imprimirCancela/"+id, "_blank");
	}
}

function consultar(){
	console.clear()
	
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
			url: path + 'nf/consultar',
			dataType: 'json',
			success: function(e){
				$('#btn-consultar').removeClass('spinner')
				console.log(e)
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

			}, error: function(e){
				$('#btn-consultar').removeClass('spinner')
				console.log(e)
				showFiscalError("Erro", e, "Algo deu errado")

				// $('#preloader1').css('display', 'none');

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

		$.get(path+'nf/consultar_cliente/'+id)
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
	let justificativa = $('#justificativa2').val();
	if(justificativa.length < 15){
		swal("Erro", "Informe no minímo 15 caracteres", "error")
		return
	}
	$('#btn-cancelar-3').addClass('spinner')

	let id = $('#id_cancela').val();
	

	let token = $('#_token').val();
	$.ajax
	({
		type: 'POST',
		data: {
			id: id,
			justificativa: justificativa,
			_token: token
		},
		url: path + 'nf/cancelar',
		dataType: 'json',
		success: function(e){

			let js = JSON.parse(e);

			$('#btn-cancelar-3').removeClass('spinner')
				// alert(js.retEvento.infEvento.xMotivo)
				swal("Sucesso", js.retEvento.infEvento.xMotivo, "success")
				.then(() => {
					window.open(path+"nf/imprimirCancela/"+id, "_blank");
					location.reload();
				})

				// $('#preloader5').css('display', 'none');

			}, error: function(e){
				console.log(e)
				let js = e.responseJSON;
				try{
					swal("Erro", js.retEvento.infEvento.xMotivo, "error");
				}catch{
					showFiscalError("Erro", e, "Algo deu errado");
				}

				$('#btn-cancelar-3').removeClass('spinner')

			}
		});
}

function cancelar(){
	// $('#preloader5').css('display', 'block');
	let justificativa = $('#justificativa').val();
	if(justificativa.length < 15){
		swal("Erro", "Informe no minímo 15 caracteres", "error")
		return
	}
	$('#btn-cancelar-2').addClass('spinner')

	let id = 0;
	let cont = 0;

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
			url: path + 'nf/cancelar',
			dataType: 'json',
			success: function(e){

				let js = JSON.parse(e);

				$('#btn-cancelar-2').removeClass('spinner')
				// alert(js.retEvento.infEvento.xMotivo)
				swal("Sucesso", js.retEvento.infEvento.xMotivo, "success")
				.then(() => {
					window.open(path+"nf/imprimirCancela/"+id, "_blank");
					location.reload();
				})

				// $('#preloader5').css('display', 'none');

			}, error: function(e){
				console.log(e)
				let js = e.responseJSON;
				try{
					swal("Erro", js.retEvento.infEvento.xMotivo, "error");
				}catch{
					swal("Erro", e.responseJSON, "error");
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
			url: path + 'nf/cartaCorrecao',
			dataType: 'json',
			success: function(e){

				try{
					let js = JSON.parse(e);

					$('#btn-corrigir-2').removeClass('spinner');

					swal("Sucesso", js.retEvento.infEvento.xMotivo, "success")
					.then(() => {
						window.open(path+"nf/imprimirCce/"+id, "_blank");
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
				showFiscalError("Erro", e, "Algo deu errado")
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
		url: path + 'nf/cartaCorrecao',
		dataType: 'json',
		success: function(e){

			try{
				let js = JSON.parse(e);

				$('#btn-corrigir-2-aux').removeClass('spinner')
				$('#btn-corrigir-2-aux').removeClass('disabled')

				swal("Sucesso", js.retEvento.infEvento.xMotivo, "success")
				.then(() => {
					window.open(path+"nf/imprimirCce/"+id, "_blank");
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
				showFiscalError("Erro", e, "Algo deu errado")

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
		url: path + 'nf/inutilizar',
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
			showFiscalError("Erro", e, "Algo deu errado")
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
			estado = $(this).find('#estado_'+id).html();
			cont++;
		}
	})

	if(cont > 1 || cont == 0){
		desabilitaBotoes();
	}else{
		estado = $.trim(estado)

		habilitaBotoes();
		if(estado == 'DISPONIVEL'){
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

		} else if(estado == 'REJEITADO'){
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


		} else if(estado == 'CANCELADO'){
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

		} else if(estado == 'APROVADO'){
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
	
	let id = $('#venda_id').val();
	let email = $('#email').val();

	$.get(path+'nf/enviarXml', {id: id, email: email})
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
	let id = 0;
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked'))
			id = $(this).find('#id').html();
	})

	$.get(path + 'vendas/find/'+id)
	.done((res) => {

		$('#celular').val(res.cliente.celular)
		$('#venda_whats_id').val(res.id)
		if(res.estado == 'APROVADO'){
			$('.div-danfe').removeClass('d-none')
		}else{
			$('.div-danfe').addClass('d-none')
		}
	})
	.fail((err) => {
		console.log(err)
	})
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
		window.open(path+"vendas/baixarXml/"+id, "_blank");
	}
}

function enviarWhatsApp(){
	let celular = $('#celular').val();
	let texto = $('#texto').val();

	let mensagem = texto.split(" ").join("%20");

	let celularEnvia = '55'+celular.replace(' ', '');
	celularEnvia = celularEnvia.replace('-', '');
	let api = 'https://api.whatsapp.com/send?phone='+celularEnvia
	+'&text='+mensagem;
	window.open(api)
}

$('#btn-danfe').click(() => {
	let id = 0
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked'))
			id = $(this).find('#id').html();
	})
	window.open(path + 'vendas/rederizarDanfe/' + id);
})

