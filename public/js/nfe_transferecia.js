$('#btn-transmitir').click(() => {
	$('.modal-loading').css('display', 'block')

	$.post(path + 'transferencia/transmitir-nfe',
	{
		_token: $('#_token').val(),
		transferencia_id: $('#transferencia_id').val()
	})
	.done((e) => {
		console.log(e)
		let id = $('#transferencia_id').val()
		$('.modal-loading').css('display', 'none')
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
				window.open(path+"transferencia/imprimir-nfe/"+id, "_blank");
				location.reload();
			})
		}


	})
	.fail((e) => {
		console.log(e)
		$('.modal-loading').css('display', 'none')

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
	})
})

function corrigirNfe(){
    $('.modal-loading').css('display', 'block');

    $.post(path + 'transferencia/corrigir-nfe', {
        transferencia_id: $('#transferencia_id').val(),
        correcao: $('#correcao').val(),
        _token: $('#_token').val()
    })
        .done((e) => {
            $('.modal-loading').css('display', 'none');
            try {
                let js = JSON.parse(e);

                if(js.success){
                    let id = $('#transferencia_id').val();

                    swal("Sucesso", js.mensagem, "success") // <- aqui corrigido para usar "mensagem"
                        .then(() => {
                            window.open(path + "transferencia/imprimir-correcao/" + id, "_blank");
                            location.reload();
                        })
                } else {
                    swal("Erro", js.data?.retEvento?.infEvento?.xMotivo || "Erro desconhecido", "error")
                        .then(() => {
                            location.reload();
                        });
                }
            } catch {
                swal("Erro", "Falha inesperada ao processar a resposta.", "error")
                    .then(() => {
                        location.reload();
                    });
            }
        })
        .fail((e) => {
            $('.modal-loading').css('display', 'none');
            console.log(e);
            swal("Erro", e.responseText, "error");
        });
}

function corrigirNfe_(){
	$('.modal-loading').css('display', 'block')
	$.post(path + 'transferencia/corrigir-nfe',
	{
		transferencia_id: $('#transferencia_id').val(),
		correcao: $('#correcao').val(),
		_token: $('#_token').val()
	})
	.done((e) => {
		$('.modal-loading').css('display', 'none')
		try{
			let js = JSON.parse(e);

			if(js.success){

				let id = $('#transferencia_id').val()

				swal("Sucesso", js.data, "success")
				.then(() => {
					window.open(path+"transferencia/imprimir-correcao/"+id, "_blank");
					location.reload()
				})
			}
		}catch{

			try{
				let js = JSON.parse(e);
				console.log(js)
				swal("Erro", js.data.retEvento.infEvento.xMotivo, "error")
				.then(() => {
					location.reload()
				})
			}catch{
				swal("Erro", e, "error")
				.then(() => {
					location.reload()
				})
			}

		}
	})
	.fail((e) => {
		$('.modal-loading').css('display', 'none')
		console.log(e)
		swal("Erro", e.responseText, "error")
	})
}

function cancelarNfe(){
    $('.modal-loading').css('display', 'block');

    $.post(path + 'transferencia/cancelar-nfe',
        {
            transferencia_id: $('#transferencia_id').val(),
            justificativa: $('#justificativa').val(),
            _token: $('#_token').val()
        })
        .done((e) => {
            $('.modal-loading').css('display', 'none');

            let id = $('#transferencia_id').val();

            if (e.success) {
                swal("Sucesso", e.mensagem ?? "Cancelamento realizado com sucesso.", "success")
                    .then(() => {
                        window.open(path + "transferencia/imprimir-cancela/" + id, "_blank");
                        location.reload();
                    });
            } else {
                swal("Erro", e.mensagem ?? "Erro desconhecido ao cancelar a NFe.", "error");
            }
        })
        .fail((e) => {
            $('.modal-loading').css('display', 'none');
            console.log(e);

            try {
                if (e.responseJSON && e.responseJSON.mensagem) {
                    swal("Erro", e.responseJSON.mensagem, "error");
                } else {
                    swal("Erro", e.responseText, "error");
                }
            } catch {
                swal("Erro", "Erro inesperado ao tentar cancelar a NFe.", "error");
            }
        });
}

function consultarSituacaoNFe() {
    $('.modal-loading').css('display', 'block');

    $.post(path + 'transferencia/consultar-situacao-nfe', {
        _token: $('#_token').val(),
        transferencia_id: $('#transferencia_id').val()
    })
        .done((e) => {
            $('.modal-loading').css('display', 'none');

            if (e.success) {
                swal("Sucesso", e.mensagem, "success")
                    .then(() => {
                        location.reload();
                    });
            } else {
                swal("Erro", e.mensagem, "error");
            }
        })
        .fail((e) => {
            $('.modal-loading').css('display', 'none');
            console.log(e);
            swal("Erro", "Erro ao consultar situação na SEFAZ!", "error");
        });
}


