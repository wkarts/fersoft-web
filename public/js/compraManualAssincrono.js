

var ITENS = [];
var FATURA = [];
var TOTAL = 0;
var PRODUTOS = [];
var PRODUTO = null;

$(function () {

});

function novaTransportadora(){
	$('#modal-transportadora').modal('show')
}

function findCidadeCodigo3(codigo_ibge){

	$.get(path + "cidades/cidadePorCodigoIbge/" + codigo_ibge)
	.done((res) => {
		$('#kt_select2_10').val(res.id).change();
	})
	.fail((err) => {
		console.log(err)
	})

}


function consultaCadastro3() {
	let cnpj = $('#cpf_cnpj3').val();
	cnpj = cnpj.replace(/[^0-9]/g,'')

	if (cnpj.length == 14) {
		$('#btn-consulta-cadastro3').addClass('spinner')

		$.get('https://publica.cnpj.ws/cnpj/' + cnpj)
		.done((data) => {
			$('#btn-consulta-cadastro3').removeClass('spinner')
			if (data!= null) {
				let ie = ''
				if (data.estabelecimento.inscricoes_estaduais.length > 0) {
					ie = data.estabelecimento.inscricoes_estaduais[0].inscricao_estadual
				}

				$('#razao_social3').val(data.razao_social)
				$("#logradouro3").val(data.estabelecimento.tipo_logradouro + " " + data.estabelecimento.logradouro)
				$('#numero3').val(data.estabelecimento.numero)

				findCidadeCodigo3(data.estabelecimento.cidade.ibge_id)

			}
		})
		.fail((err) => {
			$('#btn-consulta-cadastro3').removeClass('spinner')
			console.log(err)
		})

	}else{
		swal("Alerta", "Informe corretamente o CNPJ", "warning")
	}
}

function salvarTransportadora(){
	let js = {
		razao_social: $('#razao_social3').val(),
		logradouro: $('#logradouro3').val(),
		cpf_cnpj: $('#cpf_cnpj3').val(),
		cidade_id: $('#kt_select2_10').val(),
		numero: $('#numero3').val() ? $('#numero3').val() : '',
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
				// CLIENTE = res;

				$('#kt_select2_3').append('<option value="'+res.id+'">'+
					res.razao_social+'</option>').change();
				$('#kt_select2_3').val(res.id).change();
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

$('.fornecedor').change(() => {
	let fornecedor = $('.fornecedor').val()

	if (fornecedor != '--') {
		getFornecedor(fornecedor, (d) => {

			habilitaBtnSalarVenda();
			$('#fornecedor').css('display', 'block');
			$('#razao_social').html(d.razao_social)
			$('#nome_fantasia').html(d.nome_fantasia)
			$('#logradouro').html(d.rua)
			$('#numero').html(d.numero)

			$('#cnpj').html(d.cpf_cnpj)
			$('#ie').html(d.ie_rg)
			$('#fone').html(d.telefone)
			$('#cidade').html(d.nome_cidade)

		})
	}
})

$('#kt_select2_2').change((target) => {

	let prod = $('.produto').val().split('-');
	let codigo = prod[0];
	if(codigo != "null"){
		$('#quantidade').val('1')
		let p = PRODUTOS.filter((x) => { return x.id == codigo })
		p = p[0]
		$('#valor').val(parseFloat(p.valor_compra).toFixed(casas_decimais))
		$('#subtotal').val(parseFloat(p.valor_compra).toFixed(casas_decimais))
	}
})

function getLastPurchase(produto_id, call) {
	$('#preloader-last-purchase').css('display', 'block')
	$.get(path + 'compraManual/ultimaCompra/' + produto_id)
	.done((success) => {
		call(success)
		$('#preloader-last-purchase').css('display', 'none')
	})
	.fail((err) => {
		call(err)
		$('#preloader-last-purchase').css('display', 'none')
	})
}


function getFornecedores(data) {
	$.ajax
	({
		type: 'GET',
		url: path + 'fornecedores/all',
		dataType: 'json',
		success: function (e) {
			data(e)
		}, error: function (e) {
			console.log(e)
		}

	});
}

function getFornecedor(id, data) {
	$.ajax
	({
		type: 'GET',
		url: path + 'fornecedores/find/' + id,
		dataType: 'json',
		success: function (e) {
			data(e)

		}, error: function (e) {
			console.log(e)
		}

	});
}

function getProdutos(data) {
	$.ajax
	({
		type: 'GET',
		url: path + 'produtos/naoComposto',
		dataType: 'json',
		success: function (e) {
			data(e)

		}, error: function (e) {
			console.log(e)
		}

	});
}

function getProduto(id, data) {

	$.ajax
	({
		type: 'GET',
		url: path + 'produtos/getProduto/' + id,
		dataType: 'json',
		success: function (e) {
			data(e)

		}, error: function (e) {
			console.log(e)
		}

	});
}

$('#filial_id').change(() => {
	habilitaBtnSalarVenda()
})

function habilitaBtnSalarVenda() {
	var fornecedor = $('.fornecedor').val().split('-');

	let filial = true
	if($('#filial_id')){
		if($('#filial_id').val() == ""){
			filial = false
		}
	}

	if (ITENS.length > 0 && FATURA.length > 0 && TOTAL > 0 && parseInt(fornecedor[0]) > 0 && filial) {
		$('#salvar-venda').removeAttr('disabled', 'false')
	}else{
		$('#salvar-venda').attr('disabled', 'true')
	}
}

$('#valor').on('keyup', () => {
	calcSubtotal()
})

function calcSubtotal() {
	let quantidade = $('#quantidade').val();
	let valor = $('#valor').val();
	let subtotal = parseFloat(valor.replace(',', '.')) * (quantidade.replace(',', '.'));
	let sub = formatReal(subtotal)
	$('#subtotal').val(sub)
}

function maskMoney(v) {
	return v.toFixed(casas_decimais).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

$('#autocomplete-produto').on('keyup', () => {
	$('#last-purchase').css('display', 'none')
})

function convertFloatToMoeda(value) {
	value = parseFloat(value)
	return value.toLocaleString("pt-BR", {
		minimumFractionDigits: casas_decimais,
		maximumFractionDigits: casas_decimais
	});
}

$('#addProd').click(() => {
	console.clear()
	$('#last-purchase').css('display', 'none')
	$.get(path + 'compraManual/custo-medio',
	{
		id: PRODUTO.id,
		quantidade: $('#quantidade').val(),
		valor: $('#valor').val(),
	})
	.done((res) => {
		swal({
			title: "Alerta",
			text: "Deseja atualizar o valor de compra deste produto com o custo médio " + res + "?",
			icon: "warning",
			buttons: ["Não", 'Sim'],
			dangerMode: true,
		}).then((v) => {
			try{
				console.log(v)
				let quantidade = $('#quantidade').val();
				let valor = $('#valor').val();

				if (PRODUTO != null && quantidade.length > 0 && parseFloat(quantidade.replace(',', '.')) && valor.length > 0 && parseFloat(valor.replace(',', '.')) > 0) {
					valor = valor.replace(",", ".");

					addItemTable(PRODUTO.id, PRODUTO.nome, quantidade, valor, (v != null ? res : 0));
					$('#produto-search').val('')
				} else {
					swal("Erro", "Informe corretamente os campos para continuar!", "error")
				}
			}catch{
				swal("Erro", "Informe corretamente os campos para continuar!!", "error")
			}
			calcTotal()
		});
	}).fail((err) => {
		console.log(err)
	})


});

$('#desconto').keyup(() => {
	calcTotal()
	$('.fatura tbody').html("");
	FATURA = []
	limparDadosFatura()
	habilitaBtnSalarVenda()
})

$('#acrescimo').keyup(() => {
	calcTotal()
	$('.fatura tbody').html("");
	FATURA = []
	limparDadosFatura()
	habilitaBtnSalarVenda()
})

function addItemTable(codigo, nome, quantidade, valor, valor_custo) {

	if (!verificaProdutoIncluso()) {
		limparDadosFatura();
		TOTAL += parseFloat(valor.replace(',', '.')) * parseFloat(quantidade.replace(',', '.'));

		ITENS.push({
			id: (ITENS.length + 1),
			codigo: codigo,
			nome: nome,
			quantidade: quantidade,
			valor: valor,
			valor_custo: valor_custo
		})
		// apagar linhas tabela
		$('.prod tbody').html("");

		console.log(ITENS)
		atualizaTotal();
		limparCamposFormProd();
		let t = montaTabela();
		$('.prod tbody').html(t)
	}
}

function verificaProdutoIncluso() {
	if (ITENS.length == 0) return false;
	if ($('#prod tbody tr').length == 0) return false;
	let cod = $('#autocomplete-produto').val().split('-')[0];
	let duplicidade = false;

	ITENS.map((v) => {
		if (v.codigo == cod) {
			duplicidade = true;
		}
	})

	let c;
	if (duplicidade) c = !confirm('Produto já adicionado, deseja incluir novamente?');
	else c = false;

	return c;
}

function limparCamposFormProd() {
	$('#autocomplete-produto').val('');
	$('#quantidade').val('0');
	$('#valor').val('0');
}

function limparDadosFatura() {
	$('#fatura tbody').html('')
	$(".data-input").val("");
	$("#valor_parcela").val("");
	$('#add-pag').removeClass("disabled");
	FATURA = [];

}

function atualizaTotal() {
	if(TOTAL < 0){
		$('#total').html(0);
	}else{
		$('#total').html(formatReal(TOTAL));
	}
}

function formatReal(v) {
	return v.toLocaleString('pt-br', { style: 'currency', currency: 'BRL', minimumFractionDigits: casas_decimais });;
}

function montaTabela() {
	let t = "";
	ITENS.map((v) => {
		t += "<tr class='datatable-row' style='left: 0px;'>";
		t += "<td class='datatable-cell'><span class='' style='width: 60px;'>" + v.id + "</span></td>";
		t += "<td class='datatable-cell cod'><span class='codigo' style='width: 60px;'>" + v.codigo + "</span></td>";
		t += "<td class='datatable-cell'><span class='' style='width: 120px;'>" + v.nome + "</span></td>";
		t += "<td class='datatable-cell'><span class='' style='width: 100px;'>" + v.valor + "</span></td>";
		t += "<td class='datatable-cell'><span class='' style='width: 80px;'>" + v.quantidade + "</span></td>";
		t += "<td class='datatable-cell'><span class='' style='width: 80px;'>" + formatReal(v.valor.replace(',', '.') * v.quantidade.replace(',', '.')) + "</span></td>";
		t += "<td class='datatable-cell'><span class='svg-icon svg-icon-danger' style='width: 80px;'><a class='btn btn-sm btn-danger btn-sm' href='#prod tbody' onclick='deleteItem(" + v.id + ")'>"
		t += "<i class='la la-trash'></i></a></span></td>";
		t += "</tr>";
	});
	return t
}

function deleteItem(id) {
	let temp = [];
	ITENS.map((v) => {
		if (v.id != id) {
			temp.push(v)
		} else {
			TOTAL -= parseFloat(v.valor.replace(',', '.')) * (v.quantidade.replace(',', '.'));
		}
	});
	ITENS = temp;
	let t = montaTabela(); // para remover
	$('.prod tbody').html(t)
	atualizaTotal();
}

function calcTotal(){
	TOTAL = 0;
	ITENS.map((v) => {

		TOTAL += parseFloat(v.valor.replace(',', '.')) * (v.quantidade.replace(',', '.'));

	});

	let desconto = $('#desconto').val().replace(',', '.')
	let acrescimo = $('#acrescimo').val().replace(',', '.')
	if(desconto){
		TOTAL -= parseFloat(desconto)
	}

	if(acrescimo){
		TOTAL += parseFloat(acrescimo)
	}
	atualizaTotal()
}

$('#formaPagamento').change(() => {
	calcTotal()
	limparDadosFatura();
	let now = new Date();
	let data = (now.getDate() < 10 ? "0" + now.getDate() : now.getDate()) +
	"/" + ((now.getMonth() + 1) < 10 ? "0" + (now.getMonth() + 1) : (now.getMonth() + 1)) +
	"/" + now.getFullYear();

	var date = new Date(new Date().setDate(new Date().getDate() + 30));
	let data30 = (date.getDate() < 10 ? "0" + date.getDate() : date.getDate()) +
	"/" + ((date.getMonth() + 1) < 10 ? "0" + (date.getMonth() + 1) : (date.getMonth() + 1)) +
	"/" + date.getFullYear();

	$("#qtdParcelas").attr("disabled", true);
	$(".data-input").attr("disabled", true);
	$("#valor_parcela").attr("disabled", true);
	$("#qtdParcelas").val('1');

	if ($('#formaPagamento').val() == 'a_vista') {
		$("#qtdParcelas").val(1)
		$('#valor_parcela').val(formatReal(TOTAL));
		$('.data-input').val(data);
	} else if ($('#formaPagamento').val() == '30_dias') {
		$("#qtdParcelas").val(1)
		$('#valor_parcela').val(formatReal(TOTAL));
		$('.data-input').val(data30);
	} else if ($('#formaPagamento').val() == 'personalizado') {
		$("#qtdParcelas").removeAttr("disabled");
		$(".data-input").removeAttr("disabled");
		$("#valor_parcela").removeAttr("disabled");
		$(".data-input").val("");
		$("#valor_parcela").val(formatReal(TOTAL));
	}
})

$('#qtdParcelas').on('keyup', () => {
	limparDadosFatura();

	if ($("#qtdParcelas").val()) {
		let qtd = $("#qtdParcelas").val();

		$('#valor_parcela').val(formatReal(TOTAL / qtd));
	}
})

$('#add-pag').click(() => {

	if (!verificaValorMaiorQueTotal()) {
		let data = $('.data-input').val();
		let valor = $('#valor_parcela').val();
		let cifrao = valor.substring(0, 2);
		if (cifrao == 'R$') valor = valor.substring(3, valor.length)
			if (data.length > 0 && valor.length > 0 && parseFloat(valor.replace(',', '.')) > 0) {
				addpagamento(data, valor);
			} else {
				swal(
				{
					title: "Erro",
					text: "Informe corretamente os campos para continuar!",
					type: "warning",
				}
				)

			}
		}
	})

function verificaValorMaiorQueTotal(data) {
	let retorno;
	let valorParcela = $('#valor_parcela').val();
	let qtdParcelas = $('#qtdParcelas').val();
	let desconto = $('#desconto').val();
	let acrescimo = $('#acrescimo').val();

	if (valorParcela <= 0) {

		retorno = true;


		swal(
		{
			title: "Erro",
			text: "Valor deve ser maior que 0",
			type: "warning",
		}
		)
	}

	else if (valorParcela > TOTAL) {

		swal(
		{
			title: "Erro",
			text: "Valor da parcela maior que o total da venda!",
			type: "warning",
		}
		)
		retorno = true;
	}

	else if (qtdParcelas > 1) {
		somaParcelas((v) => {

			if (v + parseFloat(valorParcela) > TOTAL) {

				swal(
				{
					title: "Erro",
					text: "Valor ultrapassaou o total!",
					type: "warning",
				}
				)
				retorno = true;
			}
			else if (v + parseFloat(valorParcela) == TOTAL && (FATURA.length + 1) < parseInt(qtdParcelas)) {

				swal(
				{
					title: "Erro",
					text: "Respeite a quantidade de parcelas pré definido!",
					type: "warning",
				}
				)
				retorno = true;

			}
			else if (v + parseFloat(valorParcela) < TOTAL && (FATURA.length + 1) == parseInt(qtdParcelas)) {

				swal(
				{
					title: "Erro",
					text: "Somátoria incorreta!",
					type: "warning",
				}
				)
				let dif = TOTAL - v;
				$('#valor_parcela').val(formatReal(dif))
				retorno = true;

			}
			else {
				retorno = false;

			}
		})
	}
	else {
		retorno = false;
	}

	return retorno;
}

function somaParcelas(call) {
	let soma = 0;
	FATURA.map((v) => {

		// if(v.valor.length > 6){
		// 	v = v.valor.replace('.','');
		// 	v = v.replace(',','.');
		// 	soma += parseFloat(v);

		// }else{
		// 	soma += parseFloat(v.valor.replace(',','.'));
		// }
		soma += parseFloat(v.valor.replace(',', '.'));

	})
	call(soma)
}

function addpagamento(data, valor) {
	let result = verificaProdutoIncluso();
	if (!result) {
		FATURA.push({ data: data, valor: valor, numero: (FATURA.length + 1) })

		$('.fatura tbody').html(""); // apagar linhas da tabela
		let t = "";
		FATURA.map((v) => {
			t += "<tr class='datatable-row' style='left: 0px;'>";
			t += "<td class='datatable-cell'><span class='numero' style='width: 180px;'>" + v.numero + "</span></td>";
			t += "<td class='datatable-cell'><span class='' style='width: 220px;'>" + v.data + "</span></td>";
			t += "<td class='datatable-cell'><span class='' style='width: 260px;'>" + v.valor.replace('.', ',') + "</span></td>";
			t += "<td class='datatable-cell'><span class='' style='width: 220px;'><button class='btn btn-danger' onclick='removeParcela("+v.numero+")'>"
			+"<i class='la la-trash'></i></button></span></td>";
			t += "</tr>";
		});

		$('.fatura tbody').html(t)
		verificaValor();
	}
	habilitaBtnSalarVenda();
}

function removeParcela(numero){
	let temp = [];
	FATURA.map((v) => {
		if (v.numero != numero) {
			temp.push(v)
		}
	});
	FATURA = temp;
	$('.fatura tbody').html(""); // apagar linhas da tabela
	let t = "";
	FATURA.map((v) => {
		t += "<tr class='datatable-row' style='left: 0px;'>";
		t += "<td class='datatable-cell'><span class='numero' style='width: 160px;'>" + v.numero + "</span></td>";
		t += "<td class='datatable-cell'><span class='' style='width: 160px;'>" + v.data + "</span></td>";
		t += "<td class='datatable-cell'><span class='' style='width: 160px;'>" + v.valor.replace(',', '.') + "</span></td>";
		t += "<td class='datatable-cell'><span class='' style='width: 160px;'><a class='btn btn-danger' onclick='removeParcela("+v.numero+")'>"
		+"<i class='la la-trash'></i></a></span></td>";
		t += "</tr>";
	});

	$('.fatura tbody').html(t)
	verificaValor();
}

function verificaValor() {
	let soma = 0;
	FATURA.map((v) => {
		soma += parseFloat(v.valor.replace(',', '.'));
	})
	if (soma >= TOTAL) {
		$('#add-pag').addClass("disabled");
	}
}

var salvando = false
function salvarCompra() {

	$('#salvar-venda').attr('disabled', 1)
	if(salvando == false){
		salvando =  true
		$('#preloader2').css('display', 'block');

		var fornecedor = $('.fornecedor').val();
		if (fornecedor == '--') {
			swal(
			{
				title: "Erro",
				text: "Selecione um fornecedor para continuar!",
				type: "warning",
			})
		} else {

			var transportadora = $('#kt_select2_3').val();
			transportadora = transportadora == 'null' ? null : transportadora;
			let js = {
				fornecedor: fornecedor,
				formaPagamento: $('#formaPagamento').val(),
				itens: ITENS,
				fatura: FATURA,
				total: TOTAL,
				desconto: $('#desconto').val(),
				acrescimo: $('#acrescimo').val(),
				observacao: $('#obs').val(),
				lote: $('#lote').val(),
				categoria_conta_id: $('#categoria_conta_id').val(),

				especie: $('#especie').val(),
				numeracaoVol: $('#numeracaoVol').val(),
				qtdVol: $('#qtdVol').val(),
				pesoL: $('#pesoL').val(),
				pesoB: $('#pesoB').val(),
				transportadora: transportadora,
				frete: $('#frete').val(),
				placaVeiculo: $('#placa').val(),
				ufPlaca: $('#uf_placa').val(),
				valorFrete: $('#valor_frete').val(),
				filial_id: $('#filial_id') ? $('#filial_id').val() : -1,
                data_retroativa: $('#data_retroativa_dynamic').val(),
                data_saida: $('#data_saida_dynamic').val()
			}

			console.log(js)

			let token = $('#_token').val();

			$.ajax
			({
				type: 'POST',
				data: {
					compra: js,
					_token: token
				},
				url: path + 'compraManual/salvar',
				dataType: 'json',
				success: function (e) {
					$('#preloader2').css('display', 'none');
					swal({
						title: "Alerta",
						text: "Deseja imprimir o pedido de compra?",
						icon: "warning",
						buttons: ["Não", 'Sim'],
						dangerMode: true,
					}).then((v) => {

						if (v) {

							modeloImpressao(e)
						} else {
							sucesso(e)
						}
					});


				}, error: function (e) {
					console.log(e)
					$('#preloader2').css('display', 'none');
				}
			});
		}
	}
	salvando = false
}

function modeloImpressao(e){
	swal({
		title: "Impressão",
		text: "Qual o modelo de impressão?",
		icon: "warning",
		buttons: ["A4", '80mm'],
		dangerMode: true,
	}).then((v) => {
		console.log(v)
		if (v) {
			window.open(path+'compras/print80/'+e.id)
			sucesso(e)
		} else {
			window.open(path+'compras/print/'+e.id)
			sucesso(e)
		}
	});
}

function sucesso() {
	audioSuccess()
	$('#content').css('display', 'none');
	$('#anime').css('display', 'block');
	setTimeout(() => {
		location.href = path + 'compras';
	}, 4000)
}

$('#produto-search').keyup(() => {
	console.clear()
	let pesquisa = $('#produto-search').val();
	let filial_id = $('#filial_id').val()
	if(!filial_id){
		swal("Alerta", "Primeiramente selecione o local", "warning")
		return;
	}
	if(pesquisa.length > 1){
		montaAutocomplete(pesquisa, filial_id, (res) => {
			if(res){
				if(res.length > 0){
					montaHtmlAutoComplete(res, (html) => {
						$('.search-prod').html(html)
						$('.search-prod').css('display', 'block')
					})

				}else{
					$('.search-prod').css('display', 'none')
				}
			}else{
				$('.search-prod').css('display', 'none')
			}
		})
	}else{
		$('.search-prod').css('display', 'none')
	}
})

function montaAutocomplete(pesquisa, filial_id, call){
	$.get(path + 'produtos/autocomplete', {pesquisa: pesquisa, filial_id: filial_id})
	.done((res) => {

		call(res)
	})
	.fail((err) => {
		console.log(err)
		call([])
	})
}

function montaHtmlAutoComplete(arr, call){
	let html = ''
	arr.map((rs) => {
		let p = rs.nome
		if(rs.grade){
			p += ' ' + rs.str_grade
		}
		if(rs.referencia != ""){
			p += ' | REF: ' + rs.referencia
		}
		if(parseFloat(rs.estoqueAtual) > 0){
			p += ' | Estoque: ' + rs.estoqueAtual
		}
		html += '<label onclick="selectProd('+rs.id+')">'+p+'</label>'
	})
	call(html)
}


function selectProd(id){
	$.get(path + 'produtos/autocompleteProduto', {id: id, lista_id: 0})
	.done((res) => {
		PRODUTO = res

		let p = PRODUTO.nome
		if(PRODUTO.referencia != ""){
			p += ' | REF: ' + PRODUTO.referencia
		}
		if(parseFloat(PRODUTO.estoqueAtual) > 0){
			p += ' | Estoque: ' + PRODUTO.estoqueAtual
		}

		$('#valor').val(parseFloat(PRODUTO.valor_compra).toFixed(casas_decimais))
		$('#quantidade').val(1)
		$('#subtotal').val(parseFloat(PRODUTO.valor_compra).toFixed(casas_decimais))
		$('#produto-search').val(p)
	})
	.fail((err) => {
		console.log(err)
		swal("Erro", "Erro ao encontrar produto", "error")
	})
	$('.search-prod').css('display', 'none')
}

function novoFornecedor(){
	$('#modal-fornecedor').modal('show')
}

function salvarFornecedor(){
	let js = {
		razao_social: $('#razao_social2').val(),
		nome_fantasia: $('#nome_fantasia2').val() ? $('#nome_fantasia2').val() : '',
		rua: $('#rua').val() ? $('#rua').val() : '',
		numero: $('#numero2').val() ? $('#numero2').val() : '',
		cpf_cnpj: $('#cpf_cnpj').val() ? $('#cpf_cnpj').val() : '',
		ie_rg: $('#ie_rg').val() ? $('#ie_rg').val() : '',
		bairro: $('#bairro').val() ? $('#bairro').val() : '',
		cep: $('#cep').val() ? $('#cep').val() : '',
		contribuinte: $('#contribuinte').val() ? $('#contribuinte').val() : '',
		cidade_id: $('#kt_select2_4').val() ? $('#kt_select2_4').val() : NULL,
		telefone: $('#telefone').val() ? $('#telefone').val() : '',
		celular: $('#celular').val() ? $('#celular').val() : '',
	}

	if(js.razao_social == ''){
		swal("Erro", "Informe a razão social", "warning")
	}else if(js.rua == ''){
		swal("Erro", "Informe a rua", "warning")
	}
	else if(js.cpf_cnpj == ''){
		swal("Erro", "Informe o CPF/CNPJ", "warning")
	}else if(js.bairro == ''){
		swal("Erro", "Informe o bairro", "warning")
	}else if(js.cep == ''){
		swal("Erro", "Informe o CEP", "warning")
	}else if(js.cep == ''){
		swal("Erro", "Informe o CEP", "warning")
	}
	else{

		let token = $('#_token').val();
		$.post(path + 'fornecedores/quickSave',
		{
			_token: token,
			data: js
		})
		.done((res) =>{

			$('#kt_select2_1').append('<option value="'+res.id+'">'+
				res.razao_social+'</option>').change();
			$('#kt_select2_1').val(res.id).change();
			swal("Sucesso", "Fornecedor adicionado!!", 'success')
			.then(() => {
				$('#modal-fornecedor').modal('hide')
			})
		})
		.fail((err) => {
			console.log(err)
		})
	}

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

function limparCampos(){
	$('#razao_social2').val('')
	$('#nome_fantasia2').val('')

	$('#rua').val('')
	$('#numero2').val('')
	$('#bairro').val('')
	$('#cep').val('')
	$('#kt_select2_4').val('1').change();
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



function novoProduto(){

	$('#modal-produto').modal('show')
}

function salvarProduto(){
	let data = {
		nome: $('#nome').val(),
		referencia: $('#referencia').val(),
		valor_compra: $('#valor_compra').val(),
		valor_venda: $('#valor_venda').val(),
		percentual_lucro: $('#percentual_lucro').val(),
		estoque: $('#estoque').val(),
		codBarras: $('#codBarras').val(),
		estoque_minimo: $('#estoque_minimo').val(),
		gerenciar_estoque: $('#gerenciar_estoque').is(':checked') ? 1 : 0,
		inativo: $('#inativo').is(':checked'),
		categoria_id: $('#categoria_id').val(),
		limite_maximo_desconto: $('#limite_maximo_desconto').val(),
		alerta_vencimento: $('#alerta_vencimento').val(),
		unidade_compra: $('#unidade_compra').val(),
		unidade_venda: $('#unidade_venda').val(),
		NCM: $('#NCM').val(),
		CEST: $('#CEST').val(),
		anp: $('#anp').val(),
		perc_glp: $('#perc_glp').val(),
		perc_gnn: $('#perc_gnn').val(),
		perc_gni: $('#perc_gni').val(),
		valor_partida: $('#valor_partida').val(),
		unidade_tributavel: $('#unidade_tributavel').val(),
		quantidade_tributavel: $('#quantidade_tributavel').val(),
		largura: $('#largura').val(),
		altura: $('#altura').val(),
		comprimento: $('#comprimento').val(),
		peso_liquido: $('#peso_liquido').val(),
		peso_bruto: $('#peso_bruto').val(),
		CST_CSOSN: $('#CST_CSOSN').val(),
		CST_PIS: $('#CST_PIS').val(),
		CST_COFINS: $('#CST_COFINS').val(),
		CST_IPI: $('#CST_IPI').val(),
		CST_CSOSN_EXP: $('#CST_CSOSN_EXP').val(),
		CFOP_saida_estadual: $('#CFOP_saida_estadual').val(),
		CFOP_saida_inter_estadual: $('#CFOP_saida_inter_estadual').val(),
		perc_icms: $('#perc_icms').val(),
		perc_pis: $('#perc_pis').val(),
		perc_cofins: $('#perc_cofins').val(),
		perc_ipi: $('#perc_ipi').val(),
		perc_iss: $('#perc_iss').val(),
		pRedBC: $('#pRedBC').val(),
		cBenef: $('#cBenef').val(),
		perc_icms_interestadual: $('#perc_icms_interestadual').val(),
		perc_icms_interno: $('#perc_icms_interno').val(),
		perc_fcp_interestadual: $('#perc_fcp_interestadual').val()
	}

	console.log(data)

	validaCampos(data, (msg) => {
		if(msg != ""){
			swal("Erro", msg, "error")
		}else{
			$.post(path + 'produtos/quickSave',
			{
				data: data,
				_token: $('#_token').val()
			})
			.done((success) => {
				swal("Sucesso", "Produto salvo", "success")
				.then(() => {
					$('#modal-produto').modal('hide')

					console.clear()
					PRODUTO = success
					PRODUTOS.push(success)

					$('#produto-search').val(success.nome)
					$('#quantidade').val('1')
					$('#valor').val(maskMoney(parseFloat(success.valor_compra)))
					$('#subtotal').val(maskMoney(parseFloat(success.valor_compra)))
					limpaCamposFormProduto()

				})

			})
			.fail((err) => {
				console.log(err)
				swal("Erro", "Erro ao salvar produto", "error")
			})
		}
	})
}

function maskMoney2(v){
	return v.toFixed(casas_decimais);
}

function limpaCamposFormProduto(){
	$('#nome').val('')
	$('#referencia').val('')
	$('#valor_compra').val('')
	$('#valor_venda').val('')
	$('#percentual_lucro').val('')
	$('#estoque').val('')
	$('#codBarras').val('')
	$('#estoque_minimo').val('')

	$('#limite_maximo_desconto').val('')
	$('#alerta_vencimento').val('')

	// $('#NCM').val('')
	$('#CEST').val('')
	$('#perc_glp').val('')
	$('#perc_gnn').val('')
	$('#perc_gni').val('')
	$('#valor_partida').val('')
	$('#unidade_tributavel').val('')
	$('#quantidade_tributavel').val('')
	$('#largura').val('')
	$('#altura').val('')
	$('#comprimento').val('')
	$('#peso_liquido').val('')
	$('#peso_bruto').val('')
}

function validaCampos(data, call){
	let msg = ""
	if(data.nome == ""){
		msg += "Nome obrigatório\n"
	}
	if(data.percentual_lucro == ""){
		msg += "% lucro obrigatório\n"
	}
	if(data.valor_venda == ""){
		msg += "valor de venda obrigatório\n"
	}
	if(data.valor_compra == ""){
		msg += "valor de compra obrigatório\n"
	}
	if(data.NCM == ""){
		msg += "NCM obrigatório\n"
	}
	if(data.CFOP_saida_estadual == ""){
		msg += "CFOP saída interno obrigatório\n"
	}
	if(data.CFOP_saida_inter_estadual == ""){
		msg += "CFOP saída externo obrigatório\n"
	}
	if(data.CFOP_saida_estadual.length < 4){
		msg += "Informe um CFOP saída interno válido\n"
	}
	if(data.CFOP_saida_inter_estadual.length < 4){
		msg += "Informe um CFOP saída externo válido\n"
	}
	if(data.NCM.length < 10){
		msg += "Informe um NCM válido\n"
	}
	call(msg)
}

$('#percentual_lucro').keyup(() => {
	let valorCompra = parseFloat($('#valor_compra').val().replace(',', '.'));
	let percentualLucro = parseFloat($('#percentual_lucro').val().replace(',', '.'));

	if(valorCompra > 0 && percentualLucro > 0){
		let valorVenda = valorCompra + (valorCompra * (percentualLucro/100));
		valorVenda = formatReal(valorVenda);
		valorVenda = valorVenda.replace('.', '')
		valorVenda = valorVenda.substring(3, valorVenda.length)

		$('#valor_venda').val(valorVenda)
	}else{
		$('#valor_venda').val('0')
	}
})

$('#valor_venda').keyup(() => {
	let valorCompra = parseFloat($('#valor_compra').val().replace(',', '.'));
	let valorVenda = parseFloat($('#valor_venda').val().replace(',', '.'));

	if(valorCompra > 0 && valorVenda > 0){
		let dif = (valorVenda - valorCompra)/valorCompra*100;

		$('#percentual_lucro').val(dif)
	}else{
		$('#percentual_lucro').val('0')
	}
})

