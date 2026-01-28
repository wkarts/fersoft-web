var ITENS = [];
var FATURA = [];
var VALORDOPRODUTO = 0;
var TOTALQTD = 0;
var TOTAL = 0;
var PRODUTOS = []
var FORMASPAGAMENTO = [];
var SENHADESBLOQUEADA = false

function convertData(data){
	let d = data.split('-');
	return d[2] + '/' + d[1] + '/' + d[0];
}

$('#kt_select2_3').change(() => {
	let cli = $('#kt_select2_3').val()
	if(cli){
		let c = $('#kt_select2_3 option:selected').text()
		c = c.split('(')
		c = c[0]
		c = c.substring(4, c.length)
		$('#cliente_nome').val(c)
	}
})

$(function(){
	FORMASPAGAMENTO = JSON.parse($('#formasPagamento').val())
	$('#salvar-venda').attr('disabled', 1)
	$('#produto-search').val('')
	$('#desconto').val('')
	$('#acrescimo').val('')
	$('#quantidade').val('')
	$('#subtotal').val('')
	$('#valor').val('')
	$('#kt_select2_3').val('').change()

	if($('#venda_edit').val()){
		VENDA = JSON.parse($('#venda_edit').val())
		VENDA.itens.map((rs) => {
			console.log(rs)
			let qtd = parseFloat(rs.quantidade).toFixed(casas_decimais_qtd)
			addItemTable(rs.produto.id, rs.produto.nome, qtd, rs.valor);
		})
		setTimeout(() => {
			habilitaBtnSalarVenda()
		}, 100)


		VENDA.fatura.map((rs) => {

			let v = parseFloat(rs.valor).toFixed(casas_decimais)
			v = v.replace(".", ",")

			addpagamento(convertData(rs.data_vencimento), v, rs.forma_pagamento, rs.entrada)
		})

	}
})

function limparCamposFormProd(){
	$('#autocomplete-produto').val('');
	$('#quantidade').val('0');
	$('#valor').val('0');
}

$('#lista_id').change(() => {
	let lista = $('#lista_id').val();
	$('#produto-search').val('')
	$('#valor').val('0,00')
	$('#quantidade').val('1')
})

$('#valor').blur(() => {

	let valor = $('#valor').val()
	let quantidade = $('#quantidade').val()
	$('#subtotal').val(parseFloat(valor*quantidade))
})

$('#produto-search').keyup(() => {

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

		p += ' | R$ ' + parseFloat(rs.valor_venda).toFixed(casas_decimais).replace(".", ",")
		html += '<label onclick="selectProd('+rs.id+')">'+p+'</label>'
	})
	call(html)
}

function selectProd(id){
	let filial_id = $('#filial_id').val()
	let lista_id = $('#lista_id').val();

	$.get(path + 'produtos/autocompleteProduto', {id: id, lista_id: lista_id, filial_id: filial_id})
	.done((res) => {
		PTEMP = PRODUTO = res

		TIPODIMENSAO = res.tipo_dimensao

		let p = PRODUTO.nome
		if(PRODUTO.referencia != ""){
			p += ' | REF: ' + PRODUTO.referencia
		}
		if(parseFloat(PRODUTO.estoqueAtual) > 0){
			p += ' | Estoque: ' + PRODUTO.estoqueAtual
		}

		if(PRODUTO.tipo_dimensao != ''){
			$('#modal-dimensao').modal('show')
			$('#valor').val(maskMoney(parseFloat(PRODUTO.valor_venda)))
			$('#acrescimo_perca-dim').val(PRODUTO.acrescimo_perca)
			TIPODIMENSAO = PRODUTO.tipo_dimensao
			montaTipoDimensao(PRODUTO.tipo_dimensao)
			if(PRODUTO.tipo_dimensao == 'area'){
				$('.dim-area').css('display', 'block')
				$('.dim-dimensao').css('display', 'none')
			}else{
				$('.dim-dimensao').css('display', 'block')
				$('.dim-area').css('display', 'none')
			}
		}

		$('#valor').val(parseFloat(PRODUTO.valor_venda).toFixed(casas_decimais))
		$('#quantidade').val(1)
		$('#subtotal').val(parseFloat(PRODUTO.valor_venda).toFixed(casas_decimais))
		$('#produto-search').val(p)
	})
	.fail((err) => {
		console.log(err)
		swal("Erro", "Erro ao encontrar produto", "error")
	})
	$('.search-prod').css('display', 'none')
}


$('#addProd').click(() => {
	$('#formaPagamento').val('--').change();
	let quantidade = $('#quantidade').val();
	let valor = parseFloat($('#valor').val())

	let p_id = $('#kt_select2_1').val();
	

	if(PRODUTO != null){
		let p = PRODUTO
		somaQuantidadeProdutoAdicionado(p, quantidade, (adicionar) => {

			if(!adicionar){
				swal("Cuidado", "Estoque insuficiente!", "warning")
			}else{
				let codigo = p.id;
				let nome = p.nome;
				nome += p.str_grade
				let valor = $('#valor').val();

				if(codigo != null && nome.length > 0 && quantidade > 0 && parseFloat(valor.replace(',','.')) > 0) {
					valor = valor.replace(",", ".");
					addItemTable(codigo, nome, quantidade, valor);
				}else{
					swal("Erro", "Informe corretamente os campos para continuar!", "error")
				}

				PRODUTOS.push(PRODUTO)

				PRODUTO = null

				$('#subtotal').val('')
				$('#produto-search').val('')
				habilitaBtnSalarVenda()
			}
		})

	}


})

function somaQuantidadeProdutoAdicionado(produto, quantidadeAdicionar, call){
	console.clear()
	

	let quantidade = 0;
	ITENS.map((p) => {
		if(p.codigo == produto.id){
			quantidade += parseFloat(p.quantidade)
		}
	})

	quantidade += parseFloat(quantidadeAdicionar);

	if(produto.gerenciar_estoque == 1 && (!produto.estoque || produto.estoque.quantidade < quantidade)){
		call(false)
	}else{
		call(true)
	}
}

function addItemTable(codigo, nome, quantidade, valor){
	if(!verificaProdutoIncluso(codigo)) {
		limparDadosFatura();
		if(quantidade == 1){
			quantidade = '1.00'
		}

		TOTALQTD += parseFloat(quantidade.replace(',','.'));

		ITENS.push({
			id: (ITENS.length+1), 
			codigo: codigo, 
			nome: nome, 
			quantidade: quantidade, 
			valor: valor,

		})

		$('#prod tbody').html("");
		refatoreItens();

		atualizaTotal();

		let t = montaTabela();
		$('#prod tbody').html(t)
	}
}


function montaTabela(){
	let t = ""; 
	ITENS.map((v) => {

		t += '<tr class="datatable-row">'
		t += '<td class="datatable-cell">'
		t += '<span class="codigo" style="width: 70px;">'
		t += v.id + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span class="codigo" style="width: 70px;">'
		t += v.codigo + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span class="codigo" style="width: 300px;">'
		t += v.nome + '</span>'
		t += '</td>'


		t += '<td class="datatable-cell">'
		t += '<span class="codigo" style="width: 100px;">'
		
		t += v.quantidade

		t += '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span class="codigo" style="width: 100px;"> R$ '
		t += parseFloat(v.valor).toFixed(casas_decimais)
		
		t += '</span></td>'

		t += '<td class="datatable-cell">'
		t += '<span class="codigo" style="width: 100px;">'
		t += formatRealParcela(v.valor.replace(',','.')*v.quantidade.replace(',','.')) + '</span>'
		t += '</td>'

		t += "<td class='datatable-cell'><span class='codigo' style='width: 120px;'>"
		t += "<a href='#prod tbody' class='btn btn-danger btn-sm' onclick='deleteItem("+v.id+")'><i class='la la-trash'></i></a>"

		t += "</span></td>";
		t+= "</tr>";

	});

	
	return t
}

function deleteItem(id){
	let temp = [];
	ITENS.map((v) => {
		if(v.id != id){
			temp.push(v)
		}else{
			TOTAL -= parseFloat(v.valor.replace(',','.'))*(v.quantidade.replace(',','.'));
			TOTALQTD -= parseFloat(v.quantidade.replace(',','.'));
		}
	});
	ITENS = temp;
	refatoreItens()
	let t = montaTabela(); // para remover
	$('#prod tbody').html(t)

	atualizaTotal();
	habilitaBtnSalarVenda()
}

function atualizaTotal(){
	console.clear()

	TOTAL = 0
	let vf = $('#valor_frete').val() ? $('#valor_frete').val() : '0'
	vf = parseFloat(vf.replace(',', '.'))
	console.log(vf)
	TOTAL += vf
	ITENS.map((v) => {
		TOTAL += (v.quantidade * v.valor)
	})

	let desconto = 0;
	let acrescimo = 0;
	if($('#desconto').val()){
		desconto = parseFloat($('#desconto').val().replace(',', '.'))
	}

	if($('#acrescimo').val()){
		acrescimo = parseFloat($('#acrescimo').val().replace(',', '.'))
	}
	console.log("total", (TOTAL))
	$('#soma-produtos').html(formatReal(TOTAL));
	$('#totalNF').html(formatReal(TOTAL+acrescimo-desconto));
	$('#soma-quantidade').html(TOTALQTD.toFixed(casas_decimais_qtd));
}

function formatReal(v){
	return v.toLocaleString('pt-br',{style: 'currency', currency: 'BRL', minimumFractionDigits: casas_decimais});
}

function formatRealParcela(v){
	return v.toLocaleString('pt-br',{style: 'currency', currency: 'BRL', minimumFractionDigits: 2});
}

function refatoreItens(){
	let cont = 1;
	let temp = [];
	ITENS.map((v) => {
		v.id = cont;
		temp.push(v)
		cont++;
	})

	ITENS = temp;
}

function maskMoney(v){
	return v.toFixed(casas_decimais);
}

function verificaProdutoIncluso(cod){
	if(ITENS.length == 0) return false;
	if($('#prod tbody tr').length == 0) return false;
	let duplicidade = false;

	ITENS.map((v) => {
		if(v.codigo == cod){
			duplicidade = true;
		}
	})
	return false;
	let c;
	if(duplicidade) c = !confirm('Produto já adicionado, deseja incluir novamente?');
	else c = false;

	return c;
}

function limparDadosFatura(){
	$('#fatura tbody').html('')
	$("#kt_datepicker_3").val("");
	$("#valor_parcela").val("");
	$('#add-pag').removeClass("disabled");
	FATURA = [];
	habilitaBtnSalarVenda()
}

function habilitaBtnSalarVenda(){

	let desconto = $('#desconto').val();
	if(desconto.length == 0) desconto = 0;
	else desconto = desconto.replace(',', '.');

	let acrescimo = $('#acrescimo').val();
	if(acrescimo.length == 0) acrescimo = 0;
	else acrescimo = acrescimo.replace(',', '.');

	let filial = true
	if($('#filial_id')){
		if($('#filial_id').val() == ""){
			filial = false
		}
	}

	if(ITENS.length > 0 && ((TOTAL - parseFloat(desconto) + parseFloat(acrescimo)) >= 0) && filial != false){
		$('#salvar-venda').removeAttr('disabled')
	}else{
		$('#salvar-venda').attr('disabled', 1)
	}
}

$('#desconto').on('blur', () => {
	limparDadosFatura()
	let senha = $('#pass').val()
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
						limparDadosFatura()
						let desconto = $('#desconto').val();
						if(TOTAL > 0){
							desconto = desconto.replace(",", ".");
							let t = parseFloat(TOTAL) - parseFloat(desconto)
							atualizaTotal()
						}else{
							alert("Adicione itens para despois informar o desconto")
							$('#desconto').val('')
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

		let desconto = $('#desconto').val();
		if(TOTAL > 0){
			desconto = desconto.replace(",", ".");
			let t = parseFloat(TOTAL) - parseFloat(desconto)
			atualizaTotal()
		}else{
			swal("Alerta", "Adicione itens para despois informar o desconto", "warning")
			$('#desconto').val('')
		}
	}
});

$('#acrescimo').on('blur', () => {
	limparDadosFatura()
	let acrescimo = $('#acrescimo').val();
	if(TOTAL > 0){
		acrescimo = acrescimo.replace(",", ".");
		let t = parseFloat(TOTAL) + parseFloat(acrescimo)
		atualizaTotal()
	}else{
		swal("Alerta", "Adicione itens para despois informar o acrescimo", "warning")
		$('#acrescimo').val('')
	}
});

$('#tipoPagamento').change(() => {

	let tipo = $('#tipoPagamento').val()
	if(tipo == '03' || tipo == '04'){
		$('#modal-cartao').modal('show')
	}

	if(tipo == '99'){
		$('#modal-pag-outros').modal('show')
	}

})

$('#formaPagamento').change(() => {
	$('#btn-modal-pagamentos').addClass('disabled')

	limparDadosFatura();
	let now = new Date();
	let data = (now.getDate() < 10 ? "0"+now.getDate() : now.getDate()) + 
	"/"+ ((now.getMonth()+1) < 10 ? "0" + (now.getMonth()+1) : (now.getMonth()+1)) + 
	"/" + now.getFullYear();

	var date = new Date(new Date().setDate(new Date().getDate() + 30));
	let data30 = (date.getDate() < 10 ? "0"+date.getDate() : date.getDate()) + 
	"/"+ ((date.getMonth()+1) < 10 ? "0" + (date.getMonth()+1) : (date.getMonth()+1)) + 
	"/" + date.getFullYear();

	let desconto = $('#desconto').val();
	desconto = desconto.replace(",", ".");
	if(desconto.length == 0) desconto = 0;

	let acrescimo = $('#acrescimo').val();
	acrescimo = acrescimo.replace(",", ".");
	if(acrescimo.length == 0) acrescimo = 0;

	$("#qtdParcelas").attr("disabled", true);
	$("#kt_datepicker_3").attr("disabled", true);
	$("#valor_parcela").attr("disabled", true);
	$("#qtdParcelas").val('1');
	if($('#formaPagamento').val() == 'a_vista'){
		$("#qtdParcelas").val(1)
		$('#valor_parcela').val(formatRealParcela((TOTAL - parseFloat(desconto) + parseFloat(acrescimo))));
		$('#kt_datepicker_3').val(data);
	}else if($('#formaPagamento').val() == '30_dias'){

		$("#qtdParcelas").val(1)
		$('#valor_parcela').val(formatRealParcela((TOTAL - parseFloat(desconto) + parseFloat(acrescimo))));
		$('#kt_datepicker_3').val(data30);
	}else if($('#formaPagamento').val() == 'personalizado'){
		$('#btn-modal-pagamentos').removeClass('disabled')
		$("#qtdParcelas").removeAttr("disabled");
		$("#kt_datepicker_3").removeAttr("disabled");
		$("#valor_parcela").removeAttr("disabled");
		$("#kt_datepicker_3").val("");
		$("#qtdParcelas").val(1)
		$("#valor_parcela").val(formatReal(TOTAL - parseFloat(desconto) + parseFloat(acrescimo)));
	}else{
		let chave = $('#formaPagamento').val()
		let fp = FORMASPAGAMENTO.filter((x) => { return x.chave == chave })
		if(fp.length > 0){
			fp = fp[0]
		}
		var date = new Date(new Date().setDate(new Date().getDate() + fp.prazo_dias));
		let datep = (date.getDate() < 10 ? "0"+date.getDate() : date.getDate()) + 
		"/"+ ((date.getMonth()+1) < 10 ? "0" + (date.getMonth()+1) : (date.getMonth()+1)) + 
		"/" + date.getFullYear();

		$('#btn-modal-pagamentos').removeClass('disabled')
		$("#qtdParcelas").removeAttr("disabled");
		$("#kt_datepicker_3").removeAttr("disabled");
		$("#valor_parcela").removeAttr("disabled");
		$("#kt_datepicker_3").val(datep);
		$("#qtdParcelas").val(1)
		$("#valor_parcela").val(formatReal(TOTAL - parseFloat(desconto) + parseFloat(acrescimo)));
	}
})

$('#add-pag').click(() => {
	let qtdParcelas = $('#qtdParcelas').val();
	let desconto = $('#desconto').val();
	let acrescimo = $('#acrescimo').val();

	if(desconto.length == 0) desconto = 0;
	else desconto = desconto.replace(",", ".");

	if(acrescimo.length == 0) acrescimo = 0;
	else acrescimo = acrescimo.replace(",", ".");

	if(!verificaValorMaiorQueTotal()){
		let data = $('#kt_datepicker_3').val();
		let valor = $('#valor_parcela').val();
		let cifrao = valor.substring(0, 2);
		if(cifrao == 'R$'){
			valor = valor.substring(3, valor.length)
		}
		if(data.length >= 0 && valor.length >= 0 && parseFloat(valor.replace(',','.')) >= 0) {
			let tipoPagamento = $('#tipoPagamento').val()
			let tipo = getTipoPagamento(tipoPagamento)
			addpagamento(data, valor, tipo, 0);

			if(qtdParcelas == FATURA.length+1){
				somaParcelas((v) => {
					let dif = (TOTAL - parseFloat(desconto) + parseFloat(acrescimo)) - v;
					$('#valor_parcela').val(formatRealParcela(dif))
				})
			}
		}else{
			swal("Erro", "Informe corretamente os campos para continuar!", "error")

		}
	}
})

function addpagamento(data, valor, tipo, entrada){

	if(ITENS.length > 0){

		if(valor.length > 6){
			valor = valor.replace(".", "");
		}
		try{
			valor = valor.replace(",", ".");
		}catch{
			valor = valor.toFixed(2)
			valor = String(valor);
			
		}

		FATURA.push({data: data, valor: valor, numero: (FATURA.length + 1), tipo: tipo, entrada: entrada})
		montaFatura()
	}
	habilitaBtnSalarVenda();
}

function montaFatura(){

	$('#fatura tbody').html(""); 
	let t = ""; 
	FATURA.map((v) => {

		t += '<tr class="datatable-row" style="left: 0px;">'
		t += '<td class="datatable-cell">'
		t += '<span class="codigo" style="width: 120px;">'
		t += v.numero + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span class="codigo" style="width: 120px;">'
		t += v.data + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span class="codigo" style="width: 120px;">'
		t += v.valor.replace('.',',') + '</span>'
		t += '</td>'

		t += '<td class="datatable-cell">'
		t += '<span class="tipo" style="width: 120px;">'
		t += v.tipo + (v.entrada == 1 ? " - entrada" : "") +'</span>'
		t += '</td>'

		t+= "</tr>";
	});

	$('#fatura tbody').html(t)
	verificaValor();
}

$('#delete-parcelas').click(() => {
	limparDadosFatura();
})

function verificaValor(){

	let soma = 0;
	FATURA.map((v) => {
		soma += parseFloat(v.valor.replace(',','.'));
	})

	let desconto = $('#desconto').val();
	if(desconto.length == 0) desconto = 0;
	else desconto = desconto.replace(",", ".");

	let acrescimo = $('#acrescimo').val();
	if(acrescimo.length == 0) acrescimo = 0;
	else acrescimo = acrescimo.replace(",", ".");

	if(soma >= (TOTAL - parseFloat(desconto) + parseFloat(acrescimo))){
		$('#add-pag').addClass("disabled");
	}
}

function getTipoPagamento(indice){
	let tipos = {
		'01': 'Dinheiro',
		'02': 'Cheque',
		'03': 'Cartão de Crédito',
		'04': 'Cartão de Débito',
		'05': 'Crédito Loja',
		'06': 'Crediário',
		'10': 'Vale Alimentação',
		'11': 'Vale Refeição',
		'12': 'Vale Presente',
		'13': 'Vale Combustível',
		'14': 'Duplicata Mercantil',
		'15': 'Boleto Bancário',
		'16': 'Depósito Bancário',
		'17': 'Pagamento Instantâneo (PIX)',
		'90': 'Sem Pagamento',
		'99': 'Outros',
	}

	return tipos[indice]
}

function verificaValorMaiorQueTotal(data){
	let retorno;
	let valorParcela = $('#valor_parcela').val();
	let qtdParcelas = $('#qtdParcelas').val();
	let desconto = $('#desconto').val();
	let acrescimo = $('#acrescimo').val();
	
	if(desconto.length == 0) desconto = 0;
	else desconto = desconto.replace(',', '.');

	if(acrescimo.length == 0) acrescimo = 0;
	else acrescimo = acrescimo.replace(',', '.');
	let tipoPagamento = $('#tipoPagamento').val()

	let cifrao = valorParcela.substring(0, 2);
	if(cifrao == 'R$'){
		valorParcela = valorParcela.substring(3, valorParcela.length)
	}
	if(valorParcela.length > 6){
		valorParcela = valorParcela.replace(".", "");
	}
	valorParcela = valorParcela.replace(",", ".");
	valorParcela = parseFloat(valorParcela)
	valorParcela = valorParcela.toFixed(2)

	let totalComDesconto = (TOTAL - parseFloat(desconto) + parseFloat(acrescimo)).toFixed(2)
	totalComDesconto = parseFloat(totalComDesconto)
	totalComDesconto = totalComDesconto.toFixed(2)

	console.log(parseFloat(valorParcela))
	if(valorParcela <= 0 && tipoPagamento != 90){
		swal("Erro", "Valor da parcela deve ser maior que 0!", "error")
		retorno = true;

	}
	else if(parseFloat(valorParcela) > parseFloat(totalComDesconto)){
		swal("Erro", "Valor da parcela maior que o total da venda!", "error")
		retorno = true;

	}

	else if(qtdParcelas > 1){
		somaParcelas((v) => {
			valorParcela = valorParcela.replace(',', '.')

			let parcelaMaisSoma = parseFloat((v+parseFloat(valorParcela)).toFixed(2));

			console.log(parcelaMaisSoma)
			let totalAux = (TOTAL - parseFloat(desconto) + parseFloat(acrescimo))
			totalAux = parseFloat(totalAux.toFixed(2))

			if(parcelaMaisSoma > totalAux){
				swal("Erro", "Valor ultrapassaou o total!", "error")
				retorno = true;
			}
			else if(parcelaMaisSoma == (TOTAL  - parseFloat(desconto) + parseFloat(acrescimo)) && (FATURA.length+1) < parseInt(qtdParcelas)){
				swal("Erro", "Respeite a quantidade de parcelas pré definido!", "error")
				retorno = true;

			}
			else if(parcelaMaisSoma < (TOTAL  - parseFloat(desconto) + parseFloat(acrescimo)) && (FATURA.length+1) == parseInt(qtdParcelas)){

				swal("Erro", "Somátoria incorreta!", "error")
				let dif = (TOTAL - parseFloat(desconto) + parseFloat(acrescimo)) - v;
				$('#valor_parcela').val(formatRealParcela(dif))
				retorno = true;

			}
			else{
				retorno = false;	
			}

		})
	}
	else{
		retorno = false;
	}

	return retorno;
}

function renderizarPagamento(){

	simulaParcelas((res) => {

		let html = '';
		res.map((rs) => {
			html += '<option value="'+rs.indice+'">';
			html += rs.indice + 'x R$' +  rs.valor;
			html += '</option>';
		})

		$('#qtd_parcelas').html(html)
		$('#modal-pagamentos').modal('show')

	});
}

function simulaParcelas(call){
	let parcelamento_maximo = parseInt($('#parcelamento_maximo').val())

	let desconto = $('#desconto').val();
	if(desconto.length == 0) desconto = 0;
	else desconto = desconto.replace(",", ".");

	let acrescimo = $('#acrescimo').val();
	if(acrescimo.length == 0) acrescimo = 0;
	else acrescimo = acrescimo.replace(",", ".");

	let total = TOTAL - parseFloat(desconto) + parseFloat(acrescimo);
	let temp = [];
	for(let i = 1; i <= parcelamento_maximo; i++){
		let vp = total/i;
		js = {
			'indice': i,
			'valor': vp.toFixed(2)
		}
		temp.push(js)
	}
	call(temp)
}

$('#gerarPagamentos').click(() => {
	limparDadosFatura()
	let desconto = $('#desconto').val();
	if(desconto.length == 0) desconto = 0;
	else desconto = desconto.replace(",", ".");

	let acrescimo = $('#acrescimo').val();
	if(acrescimo.length == 0) acrescimo = 0;
	else acrescimo = acrescimo.replace(",", ".");

	let total = TOTAL - parseFloat(desconto) + parseFloat(acrescimo);
	let quantidade = $('#qtd_parcelas').val();
	let intervalo = parseInt($('#intervalo').val());

	let now = new Date
	let mes = now.getMonth()+1
	if(mes < 10) mes = "0"+mes;

	let dia = now.getDate();
	if(dia < 10) dia = "0"+dia;

	let hoje = now.getFullYear() + '-' + mes + '-' + dia
	let data = new Date(hoje+'T01:00:00');

	let soma = 0;
	let vp = parseFloat(parseFloat(total/quantidade).toFixed(2));
	let valor = 0;

	for(let i = 1; i <= quantidade; i++){

		data.setDate(data.getDate() + intervalo);

		if(i == quantidade){
			valor = total - soma
		}else{
			valor = vp;
		}


		soma += vp;

		let d = (data.getDate() < 10 ? '0'+data.getDate() : data.getDate()) + '/' + (data.getMonth() < 9 ? '0' + 
			(data.getMonth()+1) : (data.getMonth()+1)) + '/' + data.getFullYear();

		let tipoPagamento = $('#tipoPagamento').val()
		let tipo = getTipoPagamento(tipoPagamento)
		addpagamento(d, valor, tipo, 0)

	}

	$('#modal-pagamentos').modal('hide');

})

function salvarVenda(){
	console.clear()
	var transportadora_id = $('#kt_select2_2').val();
	var cliente_id = $('#kt_select2_3').val();

	let vol = {
		'especie': $('#especie').val(),
		'numeracaoVol': $('#numeracaoVol').val(),
		'qtdVol': $('#qtdVol').val(),
		'pesoL': $('#pesoL').val(),
		'pesoB': $('#pesoB').val(),
	}

	let js = {
		cliente_id: cliente_id,
		cliente_nome: $('#cliente_nome').val(),
		transportadora_id: transportadora_id,
		formaPagamento: $('#formaPagamento').val(),
		tipoPagamento: $('#tipoPagamento').val(),
		frete: $('#frete').val(),
		vendedor_id: $('#vendedor_id').val(),
		placaVeiculo: $('#placa').val(),
		ufPlaca: $('#uf_placa').val(),
		valorFrete: $('#valor_frete').val(),
		itens: ITENS,
		fatura: FATURA,
		volume: vol,
		total: TOTAL,
		observacao: $('#obs').val(),
		desconto: $('#desconto').val() ? $('#desconto').val() : 0,
		acrescimo: $('#acrescimo').val() ? $('#acrescimo').val() : 0,
		filial_id: $('#filial_id') ? $('#filial_id').val() : -1,
		bandeira_cartao: $('#bandeira_cartao').val() ? $('#bandeira_cartao').val() : '99',
		cAut_cartao: $('#cAut_cartao').val() ? $('#cAut_cartao').val() : '',
		cnpj_cartao: $('#cnpj_cartao').val() ? $('#cnpj_cartao').val() : '',
		descricao_pag_outros: $('#descricao_pag_outros').val() ? $('#descricao_pag_outros').val() : ''
	}
	console.log(js)
	$('.modal-loading').css('display', 'block')

	if($('#venda_id').val()){
		$.ajax
		({
			type: 'PUT',
			data: {
				data: js,
				_token: $('#_token').val()
			},
			url: path + 'vendas-balcao/'+$('#venda_id').val(),
			dataType: 'json',
			success: function(success){
				$('.modal-loading').css('display', 'none')
				swal("Sucesso", "Venda balcão finalizada " + success.codigo_venda, "success")
				.then(() => {
					location.href = '/vendas-balcao'
				})
			}, error: function(error){
				$('.modal-loading').css('display', 'none')
				console.log(error)
				swal("Erro", "Erro ao atualizar venda", "error")
			}
		})
	}else{
		$.ajax
		({
			type: 'POST',
			data: {
				data: js,
				_token: $('#_token').val()
			},
			url: path + 'vendas-balcao',
			dataType: 'json',
			success: function(success){
				$('.modal-loading').css('display', 'none')
				swal("Sucesso", "Venda balcão finalizada " + success.codigo_venda, "success")
				.then(() => {
					location.href = '/vendas-balcao/create'
				})
			}, error: function(error){
				$('.modal-loading').css('display', 'none')
				console.log(error)
				swal("Erro", "Erro ao salvar venda balcão", "error")
			}
		})
	}
}

function novoCliente(){
	$('#modal-cliente').modal('show')
}

function consultaCadastro() {
	let cnpj = $('#cpf_cnpj').val().replace(/[^0-9]/g,'')

	if (cnpj.length == 14){
		$('#btn-consulta-cadastro').addClass('spinner')
		$.get('https://publica.cnpj.ws/cnpj/' + cnpj)
		.done((data) => {
			$('#btn-consulta-cadastro').removeClass('spinner')
			console.log(data)
			if (data!= null) {
				let ie = ''
				if (data.estabelecimento.inscricoes_estaduais.length > 0) {
					ie = data.estabelecimento.inscricoes_estaduais[0].inscricao_estadual
				}
				$('#ie_rg').val(ie)
				$('#razao_social2').val(data.razao_social)
				$('#nome_fantasia2').val(data.estabelecimento.nome_fantasia)
				$("#rua").val(data.estabelecimento.tipo_logradouro + " " + data.estabelecimento.logradouro)
				$('#numero2').val(data.estabelecimento.numero)
				$("#bairro").val(data.estabelecimento.bairro);
				let cep = data.estabelecimento.cep.replace(/[^\d]+/g, '');
				$('#cep').val(cep.substring(0, 5) + '-' + cep.substring(5, 9))
				$('#email').val(data.estabelecimento.email)
				$('#telefone').val(data.estabelecimento.telefone1)

				findCidadeCodigo(data.estabelecimento.cidade.ibge_id)

			}
		})
		.fail((err) => {
			$('#btn-consulta-cadastro').removeClass('spinner')
			console.log(err)
			swal("Erro", err.responseJSON.titulo, "error")
		})
	}else{
		swal("Alerta", "Informe corretamente o CNPJ", "warning")
	}

}

function findCidadeCodigo(codigo_ibge){

	$.get(path + "cidades/cidadePorCodigoIbge/" + codigo_ibge)
	.done((res) => {
		console.log(res)
		$('#kt_select2_4').val(res.id).change();
	})
	.fail((err) => {
		console.log(err)
	})

}

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


