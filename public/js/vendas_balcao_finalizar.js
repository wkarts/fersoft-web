$(function(){

})

function modalFinalizar(id){
	$.get(path + 'vendas-balcao-find', {id: id})
	.done((success) => {

		$('#modal-finalizar').modal('show')
		$('#modal-finalizar .modal-body').html(success)
	}).fail((err) => {
		swal("Erro", "erro ao buscar venda", "error")
	})
}

$(document).on("blur", ".valor_parcela", function () {
	calcParcelas()
})

$(document).on("click", ".btn-clone-tbl", function () {
	console.clear()
	var $elem = $(this)
	.closest(".row")
	.prev()
	.find(".table-dynamic");

	let total_venda = parseFloat($('#total_venda').val())
	var total = 0

	$(".valor_parcela").each(function () {
		total += convertMoedaToFloat($(this).val())
	})

	var hasEmpty = false;

	if(total > total_venda){
		var hasEmpty = true;
	}

	if (hasEmpty) {
		swal(
			"Atenção",
			"Soma das parcelas excede o valor total!",
			"warning"
			);
		return;
	}

	$elem.find("input, select").each(function() {
		if (($(this).val() == "" || $(this).val() == null) && $(this).attr("type") != "hidden" && $(this).attr("type") != "file" && !$(this).hasClass("ignore")) {
			hasEmpty = true;
		}
	});

	if (hasEmpty) {
		swal(
			"Atenção",
			"Preencha todos os campos antes de adicionar novos.",
			"warning"
			);
		return;
	}
	$("tbody .custom-select-prod").select2("destroy");
	var $tr = $elem.find(".dynamic-form").first();
	var $clone = $tr.clone();

	$clone.show();
	$clone.find("input,select").val("");

	$elem.append($clone);
	calcParcelas()
	
})

function calcParcelas(){
	$('.btn-finish').attr('disabled', true)

	var total = 0
	let total_venda = parseFloat($('#total_venda').val())

	$(".valor_parcela").each(function () {
		total += convertMoedaToFloat($(this).val())
	})
	total = arredondaTotal(total)
	total_venda = arredondaTotal(total_venda)
	console.log(total)
	console.log(total_venda)
	if(total_venda == total){
		$('.btn-finish').removeAttr('disabled')
	}
}

function convertMoedaToFloat(value) {
	if (!value) {
		return 0;
	}
	var number_without_mask = value.replaceAll(".", "").replaceAll(",", ".");
	return parseFloat(number_without_mask.replace(/[^0-9\.]+/g, ""));
}

function convertFloatToMoeda(value) {
	value = parseFloat(value)
	return value.toLocaleString("pt-BR", {
		minimumFractionDigits: casas_decimais,
		maximumFractionDigits: casas_decimais
	});
}

function arredondaTotal(total){
	return parseFloat(total).toFixed(2)
}

$(document).delegate(".btn-line-delete", "click", function(e) {

	e.preventDefault();
	swal({
		title: "Você esta certo?",
		text: "Deseja remover esse item mesmo?",
		icon: "warning",
		buttons: true
	}).then(willDelete => {
		if (willDelete) {
			var trLength = $(this)
			.closest("tr")
			.closest("tbody")
			.find("tr")
			.not(".dynamic-form-document").length;
			if (!trLength || trLength > 1) {
				$(this)
				.closest("tr")
				.remove();
				calcParcelas()
			} else {
				swal(
					"Atenção",
					"Você deve ter ao menos um item na lista",
					"warning"
					);
			}
			calcParcelas()
		}
	});
});

function preparaObjeto(){
	console.clear()
	let fatura = []

	$(".valor_parcela").each(function () {

		let a = {
			valor_parcela: $(this).val(),
			vencimento: $(this).closest('td').next().find('input').val(),
			forma_pagamento: $(this).closest('td').next().next().find('select').val()
		}
		fatura.push(a)
	})

	let data = {
		natureza_id: $('#natureza_id').val(),
		venda_id: $('#venda_id').val(),
		fatura: fatura
	}
	return data
}

$(document).on("click", "#btn-finalizar", function () {
	let data = preparaObjeto()

	if(data.fatura[0].valor_parcela == '' || !data.fatura[0].valor_parcela){
		swal("Alerta", "Informe um valor para a fatura", "warning")
		return;
	}

	if(data.fatura[0].vencimento == '' || !data.fatura[0].vencimento){
		swal("Alerta", "Informe um vencimento para a fatura", "warning")
		return;
	}

	setTimeout(() => {
		console.log(data)

		$.post(path + 'vendas-balcao-store-pedido', {data: data, _token: $('#_token').val()})
		.done((success) => {
			console.log(success)
			swal("Sucesso", "Venda balcão finalizada", "success")
			.then(() => {
				// location.reload()
				swal({
					title: "Alerta",
					text: "Deseja imprimir o pedido de venda?",
					icon: "warning",
					buttons: ["Não", 'Sim'],
					dangerMode: true,
				}).then((v) => {
					if (v) {
						window.open(path+'vendas/imprimirPedido/'+success.id)
						location.reload()

					} else {
						location.reload()
						
					}
				});
			})
		}).fail((err) => {
			console.log(err)
			swal("Erro", "erro ao finalizar venda", "error")
		})
	}, 100)
})

$(document).on("click", "#btn-finalizar-nfe", function () {
	// emitirNfe(8)
	// return;
	let data = preparaObjeto()

	if(data.fatura[0].valor_parcela == '' || !data.fatura[0].valor_parcela){
		swal("Alerta", "Informe um valor para a fatura", "warning")
		return;
	}

	if(data.fatura[0].vencimento == '' || !data.fatura[0].vencimento){
		swal("Alerta", "Informe um vencimento para a fatura", "warning")
		return;
	}

	if(data.natureza_id){
		setTimeout(() => {
			console.log(data)

			$.post(path + 'vendas-balcao-store-pedido', {data: data, _token: $('#_token').val()})
			.done((success) => {
				console.log(success)
				emitirNfe(success.id)
			}).fail((err) => {
				console.log(err)
				swal("Erro", "erro ao finalizar venda", "error")
			})
		}, 100)
	}else{
		swal("Alerta", "Selecione a natureza de operação", "warning")
	}
})

function emitirNfe(id){
	$('.modal-loading').css('display', 'block')

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
			$('.modal-loading').css('display', 'none')
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

				swal({
					title: "Sucesso",
					text: "NFe gerada com sucesso RECIBO: "+recibo,
					icon: "success",
				}).then((v) => {
					window.open(path+"nf/imprimir/"+id, "_blank");
					location.reload();
				});
			}

		}, error: function(e){
			$('.modal-loading').css('display', 'none')
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
		}
	});

}

$(document).on("click", "#btn-finalizar-nfce", function () {
	// emitirNfe(8)
	// return;
	let data = preparaObjeto()
	if(data.fatura[0].valor_parcela == '' || !data.fatura[0].valor_parcela){
		swal("Alerta", "Informe um valor para a fatura", "warning")
		return;
	}

	if(data.fatura[0].vencimento == '' || !data.fatura[0].vencimento){
		swal("Alerta", "Informe um vencimento para a fatura", "warning")
		return;
	}

	setTimeout(() => {
		console.log(data)

		$.post(path + 'vendas-balcao-store-nfce', {data: data, _token: $('#_token').val()})
		.done((success) => {
			console.log(success)
			$('#modal-finalizar').modal('hide')
			swal({
				title: 'CPF/CNPJ na Nota?',
				text: 'Somente números(Opcional)',
				content: "input",
				button: {
					text: "Transmitir!",
					closeModal: false,
					type: 'error'
				}
			}).then((doc) => {
				emitirNfce(success.id, doc)

			})
		}).fail((err) => {
			console.log(err)
			swal("Erro", "erro ao finalizar venda", "error")
		})
	}, 100)
	
})

function emitirNfce(id, doc){
	$('.modal-loading').css('display', 'block')

	let token = $('#_token').val();
	$.ajax
	({
		type: 'POST',
		url: path + 'nfce/gerar',
		dataType: 'json',
		data: {
			vendaId: id,
			doc: doc,
			_token: token
		},
		success: function(e){

			$('.modal-loading').css('display', 'none')
			
			let recibo = e;
			let retorno = recibo.substring(0,4);
			let mensagem = recibo.substring(5,recibo.length);
			if(retorno == 'Erro'){
				try{

					let m = JSON.parse(mensagem);
					swal("Algo deu errado!", "[" + m.protNFe.infProt.cStat + "] : " + m.protNFe.infProt.xMotivo, "error")
					.then(() => {
						location.reload()
					})
				}catch{
					console.log(e);
					swal("Algo deu errado!", mensagem, "error").then(() => {
						location.reload()
					})
				}
			}
			
			else if(retorno == 'erro'){
				swal("Algo deu errado!", "WebService sefaz em manutenção, falha de comunicação SOAP", "error").then(() => {
					location.reload()
				})
			}
			else if(e == 'Apro'){
				swal("Cuidado", "Esta NFCe já esta aprovada, não é possível enviar novamente!", "warning").then(() => {
					location.reload()
				})
			}
			else if(e == 'OFFL'){
				swal("Alerta", "NFCe gerada em contigência!", "success").then(() => {
					window.open(path + 'nfce/imprimir/'+id, '_blank');
					location.reload()
				})
			}
			else{
				swal("Sucesso", "NFCe gerada com sucesso RECIBO: " +recibo, "success")
				.then(() => {
					window.open(path + 'nfce/imprimir/'+id, '_blank');
					location.reload()
				})
			}

		}, error: function(err){
			console.log(err)
			$('.modal-loading').css('display', 'none')

			let js = err.responseJSON;
			
			if(js.message){
				swal("Algo errado", js.message, "error")

			}else{
				let err = "";
				try{
					js.map((v) => {
						err += v + "\n";
					});
					swal("Erro", err, "warning")
				}catch{
					swal("Erro", js, "warning")

				}

			}
		}
	})
}



