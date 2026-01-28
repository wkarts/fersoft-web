var SENHADESBLOQUEADA = false
var PERCENTUALMAXDESCONTO = 0;
var PERMITEDESCONTO = 0;

$('#natureza').change(() => {
	changeNatureza()
})

function changeNatureza(){
	let natureza_id = $('#natureza').val()
	if(natureza_id){
		$.get(path + "naturezaOperacao/find/"+natureza_id)
		.done((res) => {
			$('#baixa_estoque').val(res.nao_movimenta_estoque == 0 ? 1 : 0).change()
		}).fail((err) => {
			console.log(err)
		})
	}
}

$('body').on('change', '.forma-pagamento', function() {
	let forma = $(this).val()
	let size = $('.forma-pagamento').length
	if(size == 1){
		let total = convertMoedaToFloat($('.total-nf').html())
		$('.valor_parcela').val(convertFloatToMoeda2(total))
	}
})

$('.btn-referencias').click(() => {
	$('.referencias').html('')
	$('.chave_nfe').each(function () {

		$el = $(this)
		$el2 = $el.clone()
		$el2.attr('type', 'hidden')
		$('.referencias').append($el2)
	})
})

$(function(){

	$('.referencias').html('')
	$('.chave_nfe').each(function () {

		$el = $(this)
		$el2 = $el.clone()
		$el2.attr('type', 'hidden')
		$('.referencias').append($el2)
	})

	PERMITEDESCONTO = $('#permite_desconto').val()
	PERCENTUALMAXDESCONTO = $('#percentual_max_desconto').val()
	setTimeout(() => {
		initSelectProduct()
		calcTotal()
		changeNatureza()
	}, 100)
})

function convertMoedaToFloat(value) {
	if (!value) {
		return 0;
	}

	var number_without_mask = value.replaceAll(".", "").replaceAll(",", ".");
	return parseFloat(number_without_mask.replace(/[^0-9\.]+/g, ""));
}

// ==== formatação de número como moeda (pt-BR) ====
function convertFloatToMoeda(value) {
    // mesmo comportamento do convertFloatToMoeda2
    value = parseFloat(value) || 0;
    let str = value.toLocaleString("pt-BR", {
        minimumFractionDigits: casas_decimais,
        maximumFractionDigits: casas_decimais
    });
    return str.replace(".", "");
}

// alias para quem usar o nome “2”
function convertFloatToMoeda2(value) {
    return convertFloatToMoeda(value);
}

// ==== a partir daqui seu código original começa ====
var SENHADESBLOQUEADA = false;

function convertFloatToMoeda2_(value) {
	value = parseFloat(value)
	value = value.toLocaleString("pt-BR", {
		minimumFractionDigits: casas_decimais,
		maximumFractionDigits: casas_decimais
	});

	return value.replace(".", "")
}

function convertQtd(value) {
	value = parseFloat(value)
	value = value.toLocaleString("pt-BR", {
		minimumFractionDigits: casas_decimais_qtd,
		maximumFractionDigits: casas_decimais_qtd
	});

	return value.replace(",", ".")
}

function initSelectProduct(){
	$(".custom-select-prod").select2({
		minimumInputLength: 2,
		language: "pt-BR",
		placeholder: "Digite para buscar o produto",
		width: "85%",
		ajax: {
			cache: true,
			url: path + 'produtos/autocomplete',
			dataType: "json",
			data: function(params) {
				console.clear()
				let filial = $('#filial').val()
				let natureza_id = $('#natureza').val()

				var query = {
					pesquisa: params.term,
					filial_id: filial,

				};
				return query;
			},
			processResults: function(response) {
				var results = [];

				$.each(response, function(i, v) {
					var o = {};
					o.id = v.id;

					o.text = v.nome + (v.grade ? " "+v.str_grade : "") + " | R$ " + parseFloat(v.valor_venda).toFixed(casas_decimais).replace(".", ",")
					+ (v.referencia != "" ? " - Ref: " + v.referencia: "") + (parseFloat(v.estoqueAtual) > 0 ? " | Estoque: " + v.estoqueAtual : "");
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

var objEdit = null
$('body').on('click', '.modal-edit-nome', function() {
	let produto = $(this).prev()[0]
	objEdit = $(this)
	if(produto.innerText != 'Digite para buscar o produto'){
		$('#modal-nome-produto').modal('show')
		let str = produto.innerText.split('|');
		$('#modal-nome-produto #descricao_item').val(str[0])
	}else{
		swal("Alerta", "Selecione o produto", "warning")
	}


})

function apontarNovaDescricao(){
	if(objEdit != null){
		objEdit.next().val($('#modal-nome-produto #descricao_item').val())
		$('#modal-nome-produto').modal('hide')
		let span = objEdit.closest('div').find('.text-descricao')
		span.html('alterado para: <strong>' + $('#modal-nome-produto #descricao_item').val() + '</strong>')

		// var newOption = new Option($('#modal-nome-produto #descricao_item').val(), 1, false, false);
		// objEdit.prev().append(newOption).trigger('change');
		// // produto.outerText = $('#modal-nome-produto #descricao_item').val()
	}
}

$('body').on('change', '.custom-select-prod', function() {

	let lista_id = $('#lista_id').val();
	let cliente_id = $('#kt_select2_3').val();
	let natureza_id = $('#natureza').val();
	let id = $(this).val()
	console.clear()

	if(id){
		$.get(path + 'produtos/autocompleteProduto',
		{
			id: id,
			lista_id: lista_id,
			cliente_id: cliente_id,
			natureza_id: natureza_id
		})
		.done((res) => {
			$vlUnit = $(this).closest('td').next().find('input')
			$vlQtd = $(this).closest('td').next().next().find('input')
			$vlSubTotal = $(this).closest('td').next().next().next().find('input')
			$cfop = $(this).closest('td').next().next().next().next().find('input')

			$vlUnit.val(convertFloatToMoeda2(res.valor_venda))
			$vlQtd.val(convertQtd(1))
			$vlSubTotal.val(convertFloatToMoeda2(res.valor_venda))
			$cfop.val(res.CFOP_saida_estadual)

			$cstCsosn = $cfop.closest('td').next().find('select')
			$cstPis = $cfop.closest('td').next().next().find('select')
			$cstCofins = $cfop.closest('td').next().next().next().find('select')
			$cstIpi = $cfop.closest('td').next().next().next().next().find('select')
			$cest = $cfop.closest('td').next().next().next().next().next().find('input')

			$cstCsosn.val(res.CST_CSOSN).change()
			$cstPis.val(res.CST_PIS).change()
			$cstCofins.val(res.CST_COFINS).change()
			$cstIpi.val(res.CST_IPI).change()
			$cest.val(res.CEST)

			$cest.closest('td').next().find('input').val('0')
			$cest.closest('td').next().next().find('input').val(res.perc_icms > 0 ? convertFloatToMoeda(res.valor_venda) : '0')
			$cest.closest('td').next().next().next().find('input').val(convertFloatToMoeda(res.perc_icms))
			$cest.closest('td').next().next().next().next().find('input').val(res.perc_icms > 0 ? convertFloatToMoeda(res.valor_venda*(res.perc_icms/100)) : '0')
			$cest.closest('td').next().next().next().next().next().find('input').val(res.perc_pis > 0 ? convertFloatToMoeda(res.valor_venda) : '0')
			$out = $cest.closest('td').next().next().next().next().next().next().find('input').val(convertFloatToMoeda(res.perc_pis))

			$out.closest('td').next().find('input').val(res.perc_pis != '0' ? convertFloatToMoeda(res.valor_venda*(res.perc_pis/100)) : '0')
			$out.closest('td').next().next().find('input').val(res.perc_cofins > 0 ? convertFloatToMoeda(res.valor_venda) : '0')
			$out.closest('td').next().next().next().find('input').val(convertFloatToMoeda(res.perc_cofins))
			$out.closest('td').next().next().next().next().find('input').val(res.perc_cofins > 0 ? convertFloatToMoeda(res.valor_venda*(res.perc_cofins/100)) : '0')
			$out.closest('td').next().next().next().next().next().find('input').val(res.perc_ipi > 0 ? convertFloatToMoeda(res.valor_venda) : '0')
			$out.closest('td').next().next().next().next().next().next().find('input').val(convertFloatToMoeda(res.perc_ipi))
			$out = $out.closest('td').next().next().next().next().next().next().next().find('input').val(res.perc_ipi > 0 ? convertFloatToMoeda(res.valor_venda*(res.perc_ipi/100)) : '0')

			$out.closest('td').next().find('select').val('0').change()

			$out.closest('td').next().next().find('input').val('0')
			$out.closest('td').next().next().next().find('input').val('0')
			$out.closest('td').next().next().next().next().find('input').val('0')
			$out.closest('td').next().next().next().next().next().find('input').val('0')
			$out.closest('td').next().next().next().next().next().next().find('input').val('0')
			$out.closest('td').next().next().next().next().next().next().next().find('input').val('0')

			calcTotal()
		})
		.fail((err) => {
			console.log(err)
			swal("Erro", "Erro ao encontrar produto", "error")
		})
	}
})

$('body').on('blur', '.qtd-p', function() {
	var qtd = $(this).val();
	var $total_amount = $(this).closest('td').next().find('input');
	var $vl_unit = $(this).closest('td').prev().find('input');

	let v = convertMoedaToFloat($vl_unit.val())

	$total_amount.val(convertFloatToMoeda2(v*qtd));
	calcTotal()
})

$('body').on('blur', '.perc_icms', function() {
	var perc = $(this).val();
	var $total = $(this).closest('td').next().find('input');
	var $sub_total = $(this).closest('td').prev().find('input');

	let v = convertMoedaToFloat($sub_total.val())
	perc = convertMoedaToFloat(perc)

	$total.val(convertFloatToMoeda((v*perc)/100));
})

$('body').on('blur', '.perc_pis', function() {
	var perc = $(this).val();
	var $total = $(this).closest('td').next().find('input');
	var $sub_total = $(this).closest('td').prev().find('input');

	let v = convertMoedaToFloat($sub_total.val())
	perc = convertMoedaToFloat(perc)

	$total.val(convertFloatToMoeda((v*perc)/100));
})

$('body').on('blur', '.perc_cofins', function() {
	var perc = $(this).val();
	var $total = $(this).closest('td').next().find('input');
	var $sub_total = $(this).closest('td').prev().find('input');

	let v = convertMoedaToFloat($sub_total.val())
	perc = convertMoedaToFloat(perc)

	$total.val(convertFloatToMoeda((v*perc)/100));
})

$('body').on('blur', '.perc_ipi', function() {
	var perc = $(this).val();
	var $total = $(this).closest('td').next().find('input');
	var $sub_total = $(this).closest('td').prev().find('input');

	let v = convertMoedaToFloat($sub_total.val())
	perc = convertMoedaToFloat(perc)

	$total.val(convertFloatToMoeda((v*perc)/100));
})

function calcTotal(){
	var total = 0
	$(".subtotal-item").each(function () {
		total += convertMoedaToFloat($(this).val())
	})
	setTimeout(() => {
		total_venda = total
		validateButtonSave()
		$('.total-nf').html("R$ " + convertFloatToMoeda(total))
	}, 100)
}


$('body').on('blur', '.valor_parcela', function() {
	calcParcelas()
})


function calcParcelas(){
	var total = 0

	$(".valor_parcela").each(function () {
		total += convertMoedaToFloat($(this).val())
	})
	setTimeout(() => {
		validateButtonSave()
		$('.total-fatura').text("R$ " + convertFloatToMoeda(total))
	}, 100)
}

$('#kt_select2_3').change(() => {
	validateButtonSave()
})

$('.vICMSST').blur(() => {
	validateButtonSave()
})

function validateButtonSave(){
	$('.alerts').html('')
	let cliente_id = $('#kt_select2_3').val()

	let total_nf = 0
	let totalvICMSST= 0
	let total_parcela = 0
	let desconto = convertMoedaToFloat($('#desconto').val())
	let acrescimo = convertMoedaToFloat($('#acrescimo').val())
	$(".subtotal-item").each(function () {
		total_nf += convertMoedaToFloat($(this).val())
	})

	$(".vICMSST").each(function () {
		totalvICMSST += convertMoedaToFloat($(this).val())
	})

	let valor_frete = $('#valor_frete').val() ? $('#valor_frete').val() : '0'
	valor_frete = convertMoedaToFloat(valor_frete)
	total_nf += valor_frete

	total_nf = parseFloat(total_nf.toFixed(2))

	$(".valor_parcela").each(function () {
		total_parcela += convertMoedaToFloat($(this).val())
	})
	$('#valor_total').val(total_nf-desconto+acrescimo)
	$('.total-geral').text("R$ " + convertFloatToMoeda(total_nf-desconto+acrescimo+totalvICMSST))

	if(total_parcela > (total_nf-desconto+acrescimo+totalvICMSST)){
		swal(
			"Atenção",
			"Soma das parcelas ultrapassa o valor do documento!",
			"warning"
			);

		$(".valor_parcela").each(function () {
			$(this).val('0')
			calcParcelas()
		})
		return;
	}
	setTimeout(() => {
		if(cliente_id){
			$('.btn-nfe').removeAttr("disabled")

		}else{
			$('.btn-nfe').attr("disabled", true);

		}
	}, 100)
}

$('.btn-clone-tbl').on("click", function() {
	console.clear()
	var $elem = $(this)
	.closest(".row")
	.prev()
	.find(".table-dynamic");


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
	$("tbody .custom-select-prod").select2("destroy");
	var $tr = $elem.find(".dynamic-form").first();
	var $clone = $tr.clone();

	$clone.show();
	$clone.find("input,select").val("");

	$elem.append($clone);
	calcParcelas()
	setTimeout(function() {

		$("tbody .custom-select-prod").select2({
			minimumInputLength: 2,
			language: "pt-BR",
			placeholder: "Digite para buscar o produto",
			width: "85%",
			ajax: {
				cache: true,
				url: path + 'produtos/autocomplete',
				dataType: "json",
				data: function(params) {
					console.clear()
					let filial = $('#filial').val()
					var query = {
						pesquisa: params.term,
						filial_id: filial
					};
					return query;
				},
				processResults: function(response) {
					var results = [];

					$.each(response, function(i, v) {
						var o = {};
						o.id = v.id;

						o.text = v.nome + (v.grade ? " "+v.str_grade : "") + " | R$ " + parseFloat(v.valor_venda).toFixed(casas_decimais).replace(".", ",")
						+ (v.referencia != "" ? " - Ref: " + v.referencia: "") + (parseFloat(v.estoqueAtual) > 0 ? " | Estoque: " + v.estoqueAtual : "");
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
	}, 100);
})

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

function percDesconto(){

	let senha = $('#pass').val()
	var TOTAL = 0
	$(".subtotal-item").each(function () {
		TOTAL += convertMoedaToFloat($(this).val())
	})
	if(senha != "" && !SENHADESBLOQUEADA){

		swal({
			title: 'Desconto de item',
			text: 'Informe a senha!',
			content: {
				element: "input",
				attributes: {
					placeholder: "Digite a senha",
					type: "password",
				},
			},
			button: {
				text: "Desbloquear!",
				closeModal: false,
				type: 'error'
			},
			confirmButtonColor: "#DD6B55",
		}).then(v => {
			if(v.length > 0){
				$.get(path+'configNF/verificaSenha', {senha: v})
				.then(
					res => {
						swal.close()


						if(TOTAL > 0){
							swal({
								title: 'Valor desconto?',
								text: 'Ultiliza ponto(.) ao invés de virgula, valor %!',
								content: "input",
								button: {
									text: "Ok",
									closeModal: false,
									type: 'error'
								}
							}).then(v => {
								if(v) {

									let desconto = v;

									let perc = desconto;
									DESCONTO = TOTAL * (perc/100);

									if(desconto.length == 0) DESCONTO = 0;

									$('#desconto').val(DESCONTO.toFixed(2).replace('.', ','))

									if(PERCENTUALMAXDESCONTO > 0){
										let tempDesc = TOTAL*PERCENTUALMAXDESCONTO/100
										if(tempDesc < DESCONTO){
											swal("Erro", "Máximo de deconto permitido é de " +  PERCENTUALMAXDESCONTO + "%", "error")
											$('#desconto').val('')
										}
									}
									calcTotal()

								}
								swal.close()

							});
						}else{
							swal("Alerta", "Adicione produtos a venda", "warning")
						}

						SENHADESBLOQUEADA = true
					},
					err => {
						$('#desconto').val('')
						swal.close()
						swal("Erro", "Senha incorreta", "error")
						.then(() => {
						});
					}
					)
			}else{
				location.reload()
			}
		})
	}else{

		if(TOTAL > 0){
			swal({
				title: 'Valor desconto?',
				text: 'Ultiliza ponto(.) ao invés de virgula, valor %!',
				content: "input",
				button: {
					text: "Ok",
					closeModal: false,
					type: 'error'
				}
			}).then(v => {
				if(v) {

					let desconto = v;

					let perc = desconto;
					desconto = TOTAL * (perc/100);

					if(desconto.length == 0) DESCONTO = 0;

					$('#desconto').val(convertFloatToMoeda(desconto))

					if(PERCENTUALMAXDESCONTO > 0){
						let tempDesc = TOTAL*PERCENTUALMAXDESCONTO/100
						if(tempDesc < DESCONTO){
							swal("Erro", "Máximo de deconto permitido é de " +  PERCENTUALMAXDESCONTO + "%", "error")
							$('#desconto').val('')
						}
					}

					calcTotal()

				}
				swal.close()

			});
		}else{
			swal("Alerta", "Adicione produtos a venda", "warning")
		}
	}
}

function setaAcresicmo(){
	var TOTAL = 0
	$(".subtotal-item").each(function () {
		TOTAL += convertMoedaToFloat($(this).val())
	})
	if(TOTAL == 0){
		swal("Erro", "Total da venda é igual a zero", "warning")
	}else{
		swal({
			title: 'Valor acrescimo?',
			text: 'Ultiliza ponto(.) ao invés de virgula, valor %',
			content: "input",
			button: {
				text: "Ok",
				closeModal: false,
				type: 'error'
			}
		}).then(v => {
			if(v) {

				let acrescimo = v;
				if(acrescimo > 0){
					$('#desconto').html(convertFloatToMoeda(0))
				}

				let VALORACRESCIMO = 0;
				let perc = acrescimo;
				VALORACRESCIMO = TOTAL * (perc/100);

				if(acrescimo.length == 0) VALORACRESCIMO = 0;
				$('#acrescimo').val(convertFloatToMoeda(VALORACRESCIMO))

				calcTotal();

			}
			swal.close()

		});
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
		email: $('#email').val() ? $('#email').val() : '',
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

					$('#kt_select2_3').append('<option value="'+res.id+'">'+
						res.razao_social+'</option>').change();
					swal("Sucesso", "Cliente adicionado!!", 'success')
					.then(() => {
						$('#modal-cliente').modal('hide')
						$('#kt_select2_3').val(res.id).change();

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

$('#pessoaFisica3').click(function () {
	$('#lbl_cpf_cnpj3').html('CPF');
	$('#lbl_ie_rg3').html('RG');
	$('#cpf_cnpj3').mask('000.000.000-00', { reverse: true });
	$('#btn-consulta-cadastro3').css('display', 'none')

})

$('#pessoaJuridica3').click(function () {
	$('#lbl_cpf_cnpj3').html('CNPJ');
	$('#lbl_ie_rg3').html('IE');
	$('#cpf_cnpj3').mask('00.000.000/0000-00', { reverse: true });
	$('#btn-consulta-cadastro3').css('display', 'block');
});

function consultaCadastro3() {
	let cnpj = $('#cpf_cnpj3').val();
	let uf = $('#sigla_uf3').val();
	cnpj = cnpj.replace('.', '');
	cnpj = cnpj.replace('.', '');
	cnpj = cnpj.replace('-', '');
	cnpj = cnpj.replace('/', '');

	if (cnpj.length == 14 && uf.length != '--') {
		$('#btn-consulta-cadastro3').addClass('spinner')

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
				$('#btn-consulta-cadastro3').removeClass('spinner')

				if (e.infCons.infCad) {
					let info = e.infCons.infCad;

					$('#razao_social3').val(info.xNome)

					$('#logradouro3').val(info.ender.xLgr + ", " + info.ender.nro + " - " + info.ender.xBairro)

					findNomeCidade(info.ender.xMun, (res) => {

						let jsCidade = JSON.parse(res);

						if (jsCidade) {

							$('#kt_select2_10').val(jsCidade.id).change();
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
						$('#razao_social3').val(data.nome)

						$('#logradouro3').val(data.logradouro + ", " + data.numero + " - " + data.bairro)

						findNomeCidade(data.municipio, (res) => {
							let jsCidade = JSON.parse(res);

							if (jsCidade) {

								$('#kt_select2_10').val(jsCidade.id).change();
							}
						})
					}
				})
				$('#btn-consulta-cadastro3').removeClass('spinner')
			}
		});
	}else{
		swal("Alerta", "Informe corretamente o CNPJ e UF", "warning")
	}
}

function salvarTransportadora(){
	let js = {
		razao_social: $('#razao_social3').val(),
		logradouro: $('#logradouro3').val(),
		cpf_cnpj: $('#cpf_cnpj3').val(),
		cidade_id: $('#kt_select2_10').val(),
		telefone: $('#telefone3').val() ? $('#telefone3').val() : '',
		email: $('#email3').val() ? $('#email3').val() : '',
	}

	if(js.razao_social == ''){
		swal("Erro", "Informe a razão social", "warning")
	}else if(js.logradouro == ''){
		swal("Erro", "Informe o logradouro", "warning")
	}else if(js.cpf_cnpj == ''){
		swal("Erro", "Informe o CPF/CNPJ", "warning")
	}

	else{
		let token = $('#_token').val();
		$.post(path + 'transportadoras/quickSave',
		{
			_token: token,
			data: js
		})
		.done((res) =>{
				// T = res;

				$('#kt_select2_2').append('<option value="'+res.id+'">'+
					res.razao_social+'</option>').change();
				$('#kt_select2_2').val(res.id).change();
				swal("Sucesso", "Transportadora adicionada!!", 'success')
				.then(() => {
					$('#modal-transportadora').modal('hide')
				})
			})
		.fail((err) => {
			console.log(err)
			swal("Alerta", err.responseJSON, "warning")
		})

	}

}


