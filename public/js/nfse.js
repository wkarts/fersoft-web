$(function(){
	setTimeout(() => {
		// $('#grade').trigger('click')
		initSelectService()
		calcServicos()
	}, 50)

})

$('#kt_select2_3').change(() => {
	let cliente = $('#kt_select2_3').val()
	if(cliente){
		buscaCliente(cliente)
	}
})


function initSelectService(){
	$(".custom-select-servico").select2({
		minimumInputLength: 2,
		language: "pt-BR",
		placeholder: "Digite para buscar o servico",
		width: "100%",
		ajax: {
			cache: true,
			url: path + 'servicos/autocomplete',
			dataType: "json",
			data: function(params) {
				console.clear()
				var query = {
					pesquisa: params.term,
				};
				return query;
			},
			processResults: function(response) {
				console.log("response", response)
				var results = [];

				$.each(response, function(i, v) {
					var o = {};
					o.id = v.id;

					o.text = v.nome;
					o.value = v.id;
					results.push(o);
				});
				return {
					results: results
				};
			}
		}
	});

	$('.select2-selection__arrow').addClass('select2-selection__arroww')
	$('.select2-selection__arrow').removeClass('select2-selection__arrow')
}

function buscaCliente(id){
	$.get(path + 'clientes/findCliente/'+id)
	.done((res) => {
		console.log(res)
		$('#documento').val(res.cpf_cnpj)
		$('#razao_social').val(res.razao_social)
		$('#cep').val(res.cep)
		$('#rua').val(res.rua)
		$('#numero').val(res.numero)
		$('#bairro').val(res.bairro)
		$('#complemento').val(res.complemento)
		$('#email').val(res.email)
		$('#telefone').val(res.telefone)
		$('#cidade_id').val(res.cidade_id).change()
	})
	.fail((err) => {
		console.log(err)
	})
}


$('body').on('change', '.custom-select-servico', function() {
	let servico = $(this).val()
	if(servico){
		buscaServico(servico, $(this))
	}
})

function buscaServico(id, elem){
	$.get(path + 'servicos/find/'+id)
	.done((res) => {
		console.log(res)
		$inpName = elem.closest('td').next().find('input')
		$inpValue = elem.closest('td').next().next().find('input')

		$inpName.val(res.nome)
		$inpValue.val(res.valor.replace(".", ","))
	})
	.fail((err) => {
		console.log(err)
	})
}

$('.btn-clone-tbl').on("click", function() {
	console.clear()
	var $elem = $(this)
	.closest(".row")
	.prev()
	.find(".table-dynamic");

	console.log($elem)

	var hasEmpty = false;

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
	$("tbody .custom-select-servico").select2("destroy");
	var $tr = $elem.find(".dynamic-form").first();
	var $clone = $tr.clone();

	$clone.show();
	$clone.find("input,select").val("");

	$elem.append($clone);

	setTimeout(function() {

		$("tbody .custom-select-servico").select2({
			minimumInputLength: 2,
			language: "pt-BR",
			placeholder: "Digite para buscar o servico",
			width: "100%",
			ajax: {
				cache: true,
				url: path + 'servicos/autocomplete',
				dataType: "json",
				data: function(params) {
					console.clear()
					var query = {
						pesquisa: params.term,
					};
					return query;
				},
				processResults: function(response) {
					console.log("response", response)
					var results = [];

					$.each(response, function(i, v) {
						var o = {};
						o.id = v.id;

						o.text = v.nome;
						o.value = v.id;
						results.push(o);
					});
					return {
						results: results
					};
				}
			}
		});

		$('.select2-selection__arrow').addClass('select2-selection__arroww')
		$('.select2-selection__arrow').removeClass('select2-selection__arrow')
	});
	

})



$('body').on('blur', '.valor_servico', function() {
	calcServicos()
})

function calcServicos(){
	var total = 0

	$(".valor_servico").each(function () {
		total += convertMoedaToFloat($(this).val())
	})
	setTimeout(() => {
		$('.total-servico').text("R$ " + convertFloatToMoeda(total))
	}, 100)
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
		}
	});
});



// function transmitir(id){
// 	let token = $('#_token').val();
// 	$.ajax
// 	({
// 		type: 'POST',
// 		data: {
// 			id: id,
// 			_token: token
// 		},
// 		url: path + 'nfse/enviar',
// 		dataType: 'json',
// 		success: function(e){
// 			console.log(e)
// 			EMITINDO = false;
// 			$('#btn-enviar').removeClass('spinner');
// 			$('#btn-enviar').removeClass('disabled');

// 			swal("Sucesso", "NFSe gerada com sucesso, código de verificação: " + e.codigo_verificacao, "success")
// 			.then(() => {
// 				window.open(e.pdf_nfse)
// 				setTimeout(() => {
// 					location.href = path+'nfse'
// 				}, 100)
// 			})

// 		}, error: function(e){
// 			$('#btn-enviar').removeClass('spinner');
// 			$('#btn-enviar').removeClass('disabled');

// 			EMITINDO = false;
// 			console.log(e)

// 			if(e.status == 401){
// 				let json = e.responseJSON
// 				let link_xml = json.xml
// 				console.log("link_xml", link_xml)
// 					// swal("Algo deu errado", json.motivo[0] + " xml: " + link_xml, "error")

// 					let motivo = Array.isArray(json.motivo) ? json.motivo[0] : json.motivo

// 					let icon = "error"
// 					let title = "Algo deu errado"
// 					if(motivo == "Lote enviado para processamento"){
// 						icon = "warning"
// 						title = "Aguarde"
// 					}
// 					swal({
// 						title: title,
// 						text: motivo,
// 						icon: icon,
// 						buttons: ["Fechar", 'Ver XML'],
// 						dangerMode: true,
// 					})
// 					.then((v) => {
// 						if (v) {
// 							if(link_xml){
// 								window.open(link_xml, '_blank');
// 							}else{
// 								swal("Erro", "Não existe nenhum XML para visualizar", "error")
// 							}
// 						} else {
// 						}
// 						location.href = path+'nfse'

// 					});
// 				}else{
// 					swal("Algo deu errado", e.responseJSON, "error")
// 					// location.reload()
					
// 				}

// 			}
// 		});
// }
