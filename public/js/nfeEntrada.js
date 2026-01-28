
function normalizaRetornoEntrada(resp) {
    let payload = resp;
    if (typeof resp === 'string') {
        try {
            payload = JSON.parse(resp);
        } catch (err) {
            payload = { raw: resp };
        }
    }
    payload = payload || {};

    const cStat = payload.cStat || payload.cstat || payload.CSTAT || null;
    const xMotivo = payload.xMotivo || payload.xmotivo || payload.mensagem || null;
    let status = payload.status || null;

    if (!status && cStat) {
        const n = parseInt(cStat);
        if ([100, 150, 180].includes(n)) status = 'autorizado';
        else if ([110, 301, 302].includes(n)) status = 'denegado';
        else status = 'rejeitado';
    }

    const sucesso = payload.sucesso !== undefined ? !!payload.sucesso : status === 'autorizado';
    const mensagem = xMotivo || payload.mensagem || payload.raw || 'Retorno sem detalhes.';

    return {
        sucesso,
        status: status || (sucesso ? 'autorizado' : 'rejeitado'),
        cStat: cStat || '-',
        mensagem,
        raw: payload.raw || resp
    };
}

function enviar(id){
    swal("Atenção", "Deseja gerar entrada fiscal desta Compra?", "warning")
        .then((v) => {
            $('#btn-enviar-nfe').addClass('spinner')

            let token = $('#_token').val();
            let js = {
                compra_id: id,
                natureza: $('#natureza').val(),
                tipo_pagamento: $('#tipo_pagamento').val(),
                _token: token
            }
            $.ajax({
                type: 'POST',
                data: js,
                url: path + 'compras/gerarEntrada',
                dataType: 'json',
                success: function(e){
                    $('#btn-enviar-nfe').removeClass('spinner')
                    const info = normalizaRetornoEntrada(e);

                    const titulo = info.sucesso ? "Sucesso" : (info.status === 'denegado' ? "Atenção" : "Erro");
                    const texto  = `[${info.cStat}] ${info.mensagem}`;

                    swal(titulo, texto, info.sucesso ? "success" : (info.status === 'denegado' ? "warning" : "error"))
                        .then(() => {
                            if (info.sucesso) {
                                window.open(path+"compras/imprimir/"+id, "_blank");
                                location.reload()
                            }
                        })
                },
                error: function(e){
                    console.log(e)
                    $('#btn-enviar-nfe').removeClass('spinner')

                    let payload = e.responseJSON || e.responseText || e.response || e;
                    const info = normalizaRetornoEntrada(payload);
                    const titulo = info.status === 'denegado' ? "Atenção" : "Erro";
                    swal(titulo, `[${info.cStat}] ${info.mensagem}`, info.status === 'denegado' ? "warning" : "error")
                }
            });
        })
}

function xmlTemporaria(id){

	let natureza = $('#natureza').val()
	let tipo_pagamento = $('#tipo_pagamento').val()

	window.open(path + "compras/xmlTemporaria?id="+id+"&natureza="+natureza+"&tipo_pagamento="+tipo_pagamento)
}

function danfeTemporaria(id){

	let natureza = $('#natureza').val()
	let tipo_pagamento = $('#tipo_pagamento').val()

	window.open(path + "compras/danfeTemporaria?id="+id+"&natureza="+natureza+"&tipo_pagamento="+tipo_pagamento)
}

function editarXml(id){

	let natureza = $('#natureza').val()
	let tipo_pagamento = $('#tipo_pagamento').val()

	location.href = path + "compras/edit_xml?id="+id+"&natureza="+natureza+"&tipo_pagamento="+tipo_pagamento

}

function redireciona(){
	location.reload();
}

function cancelar(){
    const id  = $('#compra_id').val();
    const jus = ($('#justificativa').val() || '').trim();
    if (jus.length < 15) {
        swal("Atenção", "Informe no mínimo 15 caracteres na justificativa.", "warning");
        return;
    }

    swal("Confirma?", "Deseja realmente cancelar esta NF-e de Entrada?", "warning")
        .then((v) => {
            if (!v) return;

            const $btn = $('#btn-cancelar');
            $btn.addClass('spinner').prop('disabled', true);

            $.ajax({
                type: 'POST',
                url: '/compras/cancelarEntrada',
                dataType: 'json',
                data: {
                    compra_id: id,
                    justificativa: jus,
                    _token: $('#_token').val()
                }
            })
                .done(function(resp){
                    // resp pode vir como objeto (novo controller) ou string (alguma variação antiga)
                    let aprovado = false, mensagem = '', icon = 'success';

                    if (typeof resp === 'string') {
                        // fallback: se for string, decide pelo prefixo "Erro: "
                        if (resp.substr(0,5) === 'Erro:') {
                            aprovado = false;
                            mensagem = resp.substring(6);
                            icon     = 'error';
                        } else {
                            aprovado = true;
                            mensagem = resp;
                            icon     = 'success';
                        }
                    } else {
                        aprovado = !!resp.aprovado;
                        mensagem = resp.mensagem || (aprovado ? 'Cancelamento processado.' : 'Cancelamento rejeitado.');
                        if (!aprovado) {
                            // warning para rejeição; error se veio como exception
                            icon = (resp.stage === 'exception') ? 'error' : 'warning';
                        } else {
                            icon = 'success';
                        }

                        // Se vieram códigos da SEFAZ, agrega no texto
                        const cod = resp.cStatEvento || resp.cStatInfEvento || resp.cStatEnvelope;
                        if (cod && !/^\s*\[/.test(mensagem)) {
                            mensagem = `[${cod}] : ${mensagem}`;
                        }
                    }

                    // fecha o modal antes de abrir o swal de retorno
                    $('#modal-cancelar').modal('hide');

                    swal(aprovado ? "Sucesso" : (icon === 'error' ? "Erro" : "Atenção"), mensagem, icon)
                        .then(() => {
                            // reload para refletir estado (CANCELADO ou permanece)
                            location.reload();
                        });
                })
                .fail(function(err){
                    console.error(err);
                    $('#modal-cancelar').modal('hide');
                    swal("Erro", "Falha de rede ao cancelar. Tente novamente.", "error")
                        .then(() => location.reload());
                })
                .always(function(){
                    $btn.removeClass('spinner').prop('disabled', false);
                });
        });
}

function cartaCorrecao(){
    $('#btn-corrigir-2').addClass('spinner');

    $.ajax({
        type: 'POST',
        url:  path + 'compras/cartaCorrecao',
        dataType: 'json',
        data: {
            id: $('#compra_id').val(),
            correcao: $('#correcao').val(),
            _token: $('#_token').val()
        },
        success: function (e) {
            $('#btn-corrigir-2').removeClass('spinner');

            // e JÁ é objeto JSON
            const motivo = e?.retEvento?.infEvento?.xMotivo || 'Carta de Correção registrada';
            swal("Sucesso", motivo, "success").then(() => {
                window.open(path + "compras/imprimirCce/" + $('#compra_id').val(), "_blank");
                location.reload();
            });
        },
        error: function (xhr) {
            $('#btn-corrigir-2').removeClass('spinner');

            let msg = "Erro de comunicação contate o desenvolvedor!";
            if (xhr.status === 422) {
                // validações do Laravel
                const json = xhr.responseJSON || {};
                if (json.errors) {
                    msg = Object.values(json.errors).flat().join('\n');
                } else if (json.mensagem) {
                    msg = json.mensagem;
                }
            } else if (xhr.responseText) {
                msg = xhr.responseText;
            }

            swal("Erro", msg, "error");
        }
    });
}

$('#btn-consulta').click(() => {
	$('#btn-consulta').addClass('spinner')
	$('#btn-consulta').addClass('disabled')
	let token = $('#_token').val();


	let js = {
		compra_id: $('#compra_id').val(),
		_token: token
	}

	$.ajax
	({
		type: 'POST',
		data: js,
		url: path + 'compras/consultar',
		dataType: 'json',
		success: function(e){
			$('#btn-consulta').removeClass('spinner')
			$('#btn-consulta').removeClass('disabled')

			let js = JSON.parse(e)
			if(js.cStat != '656'){
				swal("Sucesso", "Status: " + js.xMotivo + " - chave: " + js.chNFe + ", protocolo: " + js.protNFe.infProt.nProt, "success")
			}else{

				swal("Erro", "Consumo indevido!", "error")
			}

		}, error: function(e){
			console.log(e)
			$('#btn-consulta').removeClass('spinner')
			$('#btn-consulta').removeClass('disabled')
			swal("Erro", "Algo deu errado", "warning")

			// Materialize.toast('Erro de comunicação contate o desenvolvedor', 5000)

		}
	});
});

$('#natureza').change(() => {
	setParametros()
})

$('#tipo_pagamento').change(() => {
	setParametros()
})

function setParametros(){
	let id = $('#compra_id').val()
	let natureza_id = $('#natureza').val()
	let tipo_pagamento = $('#tipo_pagamento').val()

	$.get(path + 'compras/setNaturezaPagamento',
	{
		id: id,
		natureza_id: natureza_id,
		tipo_pagamento: tipo_pagamento
	}).done((res) => {
		console.log(res)
	})
	.fail((err) => {
		console.log(err)
	})
}

