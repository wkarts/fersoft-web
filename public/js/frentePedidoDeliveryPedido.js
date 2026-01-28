var CATEGORIAS = []
var DIVIDEPIZZA = 1
$(function(){
	// $('#btn-itens-pedido').trigger('click')
	// $('#btn-finalizar-pedido').trigger('click')
	$('#endereco').change()
	CATEGORIAS = JSON.parse($('#categorias').val())
	DIVIDEPIZZA = $('#divide_pizza').val()
})

function categoria(cat){
	console.clear()
	desmarcarCategorias(() => {
		setTimeout(() => {
			$('#cat_' + cat).addClass('ativo')
		}, 100)
	})

	produtosDaCategoria(cat, (res) => {

	})

}
var VALORENTREGA = 0
$('#endereco').change(() => {
	console.clear()
	let endereco = $('#endereco').val()
	let pedido_id = $('#pedido_id').val()
	if(pedido_id){
		$.post(path + 'pedidosDelivery/setEndereco', {endereco: endereco, pedido_id: pedido_id, _token: $('#_token').val()})
		.done((res) => {
			if(endereco){

				if(res._bairro){
					$('.vl_entrega').text('R$ ' + res._bairro.valor_entrega.replace('.', ','))
					VALORENTREGA = res._bairro.valor_entrega
					$('#valor_entrega').val(VALORENTREGA)
				}
			}else{

				$('.vl_entrega').text('R$ 0,00')
				$('#valor_entrega').val(0)

			}
		}).fail((err) => {
			$('#valor_entrega').val(VALORENTREGA)

			console.log(err)
			swal("Erro", "Erro ao setar endereço do pedido", "error")
		})
	}
})

$('#pesquisa').keyup(() => {
	console.clear()
	let pesquisa = $('#pesquisa').val()
	if(pesquisa.length > 1){
		$('.prods').html('')
		console.log("buscando ....")

		$.get(path + 'produtosDelivery/search', {pesquisa: pesquisa})
		.done((res) => {
			montaProdutos(res)
		})
		.fail((err) => {
			console.log("erro", err)
		})
	}else{
		$.get(path + 'produtosDelivery/search', {pesquisa: ''})
		.done((res) => {
			montaProdutos(res)
		})
		.fail((err) => {
			console.log("erro", err)
		})
	}
})

function desmarcarCategorias(call){
	CATEGORIAS.map((v) => {
		$('#cat_' + v.id).removeClass('ativo')
		$('#cat_' + v.id).removeClass('desativo')
	})
	$('#cat_todos').removeClass('ativo')
	$('#cat_todos').removeClass('desativo')

	call(true)
}

function produtosDaCategoria(categoria, call){
	let produtos = []
	$('.prods').html('')
	$.get(path + 'produtosDelivery/byCategoria/'+categoria)
	.done((res) => {
		montaProdutos(res)
	})
	.fail((err) => {
		console.log("erro", err)
	})
}

function montaProdutos(produtos){
	let html = ''
	produtos.map((p) => {
		console.log(p)
		html += '<div class="col-md-4 bd2" onclick="addItem('+p.id+')">'
		html += '<div class="card-xl-stretch me-md-6">'
		html += '<a class="d-block bgi-no-repeat bgi-size-cover bgi-position-center card-rounded position-relative min-h-175px mb-5"' 
		html += 'style="background-image:url(\''+p.img+'\')" data-fslightbox="lightbox-video-tutorials"></a>'

		html += '<div class="m-0">'
		html += '<a style="font-size: 20px" class="fs-2 text-dark fw-bold text-hover-primary text-dark lh-base">'
		html += p.produto.nome
		html += '</a><div class="fw-semibold fs-5 text-gray-600 text-dark my-4" style="height: 50px;">'
		html += p.descricao
		html += '</div><div class="fs-6 fw-bold">'
		html += '<a class="text-gray-700 text-hover-primary">'+p.categoria.nome+'</a>'
		if(p.categoria.tipo_pizza == false){
			html += '<span style="font-size: 20px" class="text-danger float-right">R$ '+p.valor.replace('.', ',')+'</span>'
		}else{
			html += '<span style="font-size: 13.5px" class="text-danger float-right">R$ '+
			montaValoresPizza(p)
			+'</span>'
		}
		html += '</div></div></div></div>'

	})
	if(produtos.length == 0){
		$('.prods').html('<h3 class="ml-3">Nenhum produto encontrado!</h3>')
	}else{
		$('.prods').html(html)
	}
}

function montaValoresPizza(p){
	let valores = ""
	p.pizza.map((pz) => {
		valores += pz.valor.replace('.', ',') + " | "
	})
	return valores.substr(0, valores.length-2)
}

var PIZZAID = 0;
function addItem(id){
	VALORADICIONAL = 0
	console.clear()
	let pedido_id = $('#pedido_id').val()
	if(pedido_id){
		PRODUTO = null
		$('#qtd').val('1')
		$.get(path + 'produtosDelivery/find/'+id)
		.done((res) => {
			console.log(res)
			PRODUTO = res

			if(res.categoria.tipo_pizza == 0){
				$('#modal-adicionais').modal('show')

				getAdicionais()

				$('.vl-unit').html("R$ " + res.valor.replace('.', ','))
				$('.vl-item').html("R$ " + res.valor.replace('.', ','))
			}else{
				PIZZAID = id
				$('#tamanho_pizza').val('').change()
				$('#modal-pizzas').modal('show')

			}

		}).fail((err) => {
			console.log(err)
		})
	}else{
		swal("Alerta", "Informe um cliente para adicionar produtos!", "warning")
	}
}

function getAdicionais(){
	$.get(path + 'produtosDelivery/adicionais')
	.done((res) => {
		montaAdicionais(res)
	}).fail((err) => {
		console.log(err)
	})
}

$('#pesquisa-adicional').keyup(() => {
	let pesquisa = $('#pesquisa-adicional').val()
	$.get(path + 'produtosDelivery/adicionais', {pesquisa: pesquisa})
	.done((res) => {
		montaAdicionais(res)
		percorreArrayAdicionais()
	}).fail((err) => {
		console.log(err)
	})
})


function montaAdicionais(adicionais){
	let html = ''

	VALORITEM = PRODUTO.valor
	adicionais.map((p) => {
		html += '<div class="col-md-4 bd div_adicional_'+p.id+'" onclick="select_add('+p.id+', '+p.valor+')">'
		html += '<a style="font-size: 20px" class="fs-2 text-dark fw-bold text-hover-primary text-dark lh-base">'
		html += p.nome
		html += '</a>'
		html += '<span style="font-size: 20px" class="text-danger ml-4">R$ '+p.valor.replace('.', ',')+'</span>'
		html += '</div>'

	})
	if(adicionais.length == 0){
		$('.adicionais').html('<h3 class="ml-3">Nenhum adicional encontrado!</h3>')
	}else{
		$('.adicionais').html(html)
	}
}

var ADICIONAIS = [];
var VALORADICIONAL = 0;
var VALORITEM = 0;
var PRODUTO = null;
function select_add(id, valor){

	if(validaInserido(id)){
		ADICIONAIS.push(id)
		VALORADICIONAL += valor
	}else{
		let temp = ADICIONAIS.filter((x) => { return x != id })
		console.log(temp)
		ADICIONAIS = temp
		VALORADICIONAL -= valor
	}
	percorreArrayAdicionais()
	atualizaTotal()
}

function validaInserido(id){
	let t = ADICIONAIS.find((x) => { return x == id })
	return t ? false : true
}

function percorreArrayAdicionais(){
	$('.bd').removeClass('ativo')

	ADICIONAIS.map((a) => {
		console.log(a)
		$('.div_adicional_'+a).addClass('ativo')
	})
}

function atualizaTotal(){
	let qtd = $('#qtd').val().replace(',', '.')
	let qtd2 = $('#qtd2').val().replace(',', '.')
	let total = 0
	if(parseFloat(PRODUTO.valor) > 0){
		total = (parseFloat(PRODUTO.valor) + VALORADICIONAL) * qtd
	}else{
		total = (parseFloat(VALORPIZZA) + VALORADICIONAL) * qtd2
	}

	$('.vl-item').html("R$ " + total.toFixed(2).replace('.', ','))
}

$('#qtd').blur(() => {
	atualizaTotal()
})

$('#qtd2').blur(() => {
	atualizaTotal()
})

$('#btn-add-item').click(() => {
	if(PRODUTO != null){
		let vl_item = $('.vl-item').html()
		vl_item = vl_item.replace('R$ ', '')
		vl_item = vl_item.replace(',', '.')
		// alert(vl_item)
		let js = {
			produto: PRODUTO.id,
			adicionais: ADICIONAIS,
			qtd: $('#qtd').val(),
			observacao: $('#observacao').val(),
			pedido_id: $('#pedido_id').val(),
			valor: VALORITEM
			// valor: vl_item
		}
		console.log(js)
		$.post(path + 'pedidosDelivery/store', {data : js, _token: $('#_token').val()})
		.done((res) => {
			console.log(res)
			swal("Sucesso", "Item Adicionado", "success")
			.then(() => {
				location.reload()
			})
		})
		.fail((err) => {
			console.log(err)
			swal("Erro", "Algo deu errado!", "error")
		})
	}else{
		swal("Alerta", "Informe o produto", "warning")
	}
})

$('#btn-itens-pedido').click(() => {
	console.clear()
	let pedido_id = $('#pedido_id').val()

	$('.prods-pedido').html('')
	$.get(path + 'pedidosDelivery/find/'+pedido_id)
	.done((res) => {
		console.log(res)
		montaProdutosDoPedido(res.itens)
	})
	.fail((err) => {
		console.log(err)

	})
})

function montaProdutosDoPedido(itens){
	let html = ''
	let total = 0
	itens.map((i) => {
		console.log(i)

		html += '<div class="col-lg-4 col-12 mt-5 border-card" style="height: 330px">'
		html += '<div class="card-xl-stretch me-md-6">'
		html += '<a class="d-block bgi-no-repeat bgi-size-cover bgi-position-center card-rounded position-relative min-h-155px mb-5"' 
		html += 'style="background-image:url(\''+i.produto.img+'\')" data-fslightbox="lightbox-video-tutorials"></a>'

		html += '<div class="m-0">'
		html += '<a style="font-size: 20px" class="fs-2 text-dark fw-bold text-hover-primary text-dark lh-base">'
		html += i.produto.produto.nome
		html += '</a><div class="fw-semibold fs-5 text-gray-600 text-dark my-4">'

		if(i.itens_adicionais.length == 0){
			html += "<br>"
		}else{
			html += "Adicionais: "
		}
		i.valor = parseFloat(i.valor)
		i.itens_adicionais.map((add) => {
			html += add.adicional.nome + " |"
			i.valor += parseFloat(add.adicional.valor)
		})

		if(i.itens_adicionais.length > 0){

			html = html.substr(0, html.length-2)
		}

		html += '</div><div class="fs-6 fw-bold mt-6">'
		html += '<a class="text-gray-700 text-hover-primary">Quantidade: '+i.quantidade+'</a>'
		if(i.tamanho_id != null){
			html += '<a class="text-gray-700 text-hover-primary ml-5">Tamanho: '+i.tamanho.nome+'</a>'
		}
		html += '<span style="font-size: 16px" class="text-danger float-right"> valor R$ '+i.valor.toFixed(2).replace('.', ',')+'</span>'
		html += '</div>'
		html += '<button class="btn btn-danger w-100" onclick="deleteItem('+i.id+')">Remover Item</button>'
		html += '</div></div></div>'
		total += parseFloat(i.valor)
	})
	if(itens.length == 0){
		$('.prods-pedido').html('<h3 class="ml-3">Nenhum produto encontrado!</h3>')
	}else{
		$('.prods-pedido').html(html)
		$('.total-parcial').html('R$ ' + total.toFixed(2).replace('.', ','))
	}
}

function deleteItem(id){
	swal({
		title: "",
		text: "Deseja remover este item?",
		icon: "warning",
		buttons: ["Não", 'Sim'],
		dangerMode: true,
	}).then((v) => {
		if (v) {
			location.href = '/pedidosDelivery/deleteItem/'+id
		} 
	});
}

// addItem('4')

var MAXSABOR = 1;
var PIZZAS = []
$('#tamanho_pizza').change(() => {
	console.clear()
	$('.pizzas').html('')
	let tamanho_id = $('#tamanho_pizza').val()
	if(tamanho_id){
		let qtdsabores = $('#tamanho_pizza option:selected').data('qtdsabores')
		MAXSABOR = qtdsabores
		$('.qtd_sabores').html(qtdsabores)
		$.get(path + 'produtosDelivery/searchPizzas', {tamanho_id: tamanho_id})
		.done((data) => {
			console.log(data)
			PIZZAS = data
			montaPizzas(data, tamanho_id)

			setTimeout(() => {
				calculaValorPizza()
			}, 100)
		})
		.fail((err) => {
			console.log(err)
			swal("Erro", "Algo saiu errado ao buscar pizzas", "error")
		})
	}
})

function montaPizzas(pizzas, tamanho_id){
	let html = ''
	pizzas.map((p) => {
		console.log(p)
		if(p.id == PIZZAID){
			SABORESPIZZA.push(p.id)
		}
		html += '<div class="col-md-4 bd2 pbg_'+p.id+' '+(p.id == PIZZAID ? 'bg-light-success' : '')+'" onclick="selectPizza('+p.id+')">'
		html += '<div class="card-xl-stretch me-md-6">'
		html += '<a class="d-block bgi-no-repeat bgi-size-cover bgi-position-center card-rounded position-relative min-h-175px mb-5"' 
		html += 'style="background-image:url(\''+p.img+'\')" data-fslightbox="lightbox-video-tutorials"></a>'

		html += '<div class="m-0">'
		html += '<a style="font-size: 20px" class="fs-2 text-dark fw-bold text-hover-primary text-dark lh-base">'
		html += p.produto.nome
		html += '</a><div class="fw-semibold fs-5 text-gray-600 text-dark my-4" style="height: 50px;">'
		html += p.descricao
		html += '</div><div class="fs-6 fw-bold">'
		html += '<a class="text-gray-700 text-hover-primary">'+p.categoria.nome+'</a>'
		html += '<span style="font-size: 20px" class="text-danger float-right">R$ '+ getValorDaPizza(p, tamanho_id)+'</span>'
		html += '</div></div></div></div>'

	})
	if(pizzas.length == 0){
		$('.pizzas').html('<h3 class="ml-3">Nenhuma pizza encontrada!</h3>')
	}else{
		$('.pizzas').html(html)
	}
}

function getValorDaPizza(p, tamanho_id){
	let t = p.pizza.find((x) => { return x.tamanho_id == tamanho_id })
	if(t){
		return t.valor.replace(".", ",")
	}else{
		return p.valor.replace(".", ",")
	}
}

var SABORESPIZZA = []
function selectPizza(id){
	VALORADICIONAL = 0
	inarray(id, (res) => {
		console.log("res", res)
		if(!res){
			if(MAXSABOR > SABORESPIZZA.length){

				$('.pbg_'+id).addClass('bg-light-success')
				SABORESPIZZA.push(id)
			}else{
				swal("Alerta", "Máximo de sabores para este tamanho é de: " + MAXSABOR, "warning")
			}
		}else{
			$('.pbg_'+id).removeClass('bg-light-success')
			let temp = SABORESPIZZA.filter((x) => {
				return x != id
			})
			SABORESPIZZA = temp
		}
	})
	
	calculaValorPizza()
	
}

var VALORPIZZA = 0
function calculaValorPizza(){
	let tamanho_id = $('#tamanho_pizza').val()
	VALORITEM = 0
	setTimeout(() => {
		console.log("canculando valor da pizza")

		SABORESPIZZA.map((sabor) => {
			let pz = PIZZAS.find((x) => { return x.id == sabor })

			let valor = getValorDaPizza(pz, tamanho_id)
			valor = parseFloat(valor.replace(",", "."))
			if(DIVIDEPIZZA == 0){
				if(VALORITEM < valor) VALORITEM = valor
			}else{
				VALORITEM += valor
			}
		})

		if(DIVIDEPIZZA == 1){
			VALORITEM = VALORITEM/SABORESPIZZA.length
		}

		VALORPIZZA = VALORITEM
		$('.vl-item').html("R$ " + VALORITEM.toFixed(2).replace('.', ','))

	}, 100)
}

function inarray(id, call){
	let temp = SABORESPIZZA.find((x) => { return x == id })
	if(temp){
		call(true)
	}else{
		call(false)
	}
}

$('#pesquisa-pizza').keyup(() => {
	let tamanho_id = $('#tamanho_pizza').val()
	if(tamanho_id){
		let pesquisa = $('#pesquisa-pizza').val()

		console.log("pesquisando pizza ...", pesquisa)

		$.get(path + 'produtosDelivery/searchPizzas', {pesquisa: pesquisa, tamanho_id: tamanho_id})
		.done((res) => {
			montaPizzas(res, tamanho_id)
		}).fail((err) => {
			console.log(err)
		})
	}else{
		$('#pesquisa-pizza').val('')
		swal("Alerta", "Informe o tamanho para depois pesquisar", "warning")
	}
})
var ITEMPIZZA = null
$('#btn-add-pizza').click(() => {
	if(SABORESPIZZA.length > 0){
		ITEMPIZZA = {
			sabores: SABORESPIZZA,
			adicionais: ADICIONAIS,
			qtd: 1,
			observacao: '',
			pedido_id: $('#pedido_id').val(),
			valor: VALORITEM
		}

		console.log(ITEMPIZZA)

		$('#modal-pizzas').modal('hide')
		$('#modal-adicionais-pizza').modal('show')

		getAdicionais()
		
	}else{
		swal("Alerta", "Informe a pizza", "warning")
	}
})

$('#btn-save-pizza').click(() => {
	if(PRODUTO != null){
		ITEMPIZZA.qtd = $('#qtd2').val()
		ITEMPIZZA.observacao = $('#observacao').val()
		ITEMPIZZA.valor += VALORADICIONAL
		ITEMPIZZA.tamanho_pizza_id = $('#tamanho_pizza').val()

		console.log(ITEMPIZZA)
		$.post(path + 'pedidosDelivery/store', {data : ITEMPIZZA, _token: $('#_token').val()})
		.done((res) => {
			console.log(res)
			swal("Sucesso", "Item Adicionado", "success")
			.then(() => {
				location.reload()
			})
		})
		.fail((err) => {
			console.log(err)
			swal("Erro", "Algo deu errado!", "error")
		})
	}else{
		swal("Alerta", "Informe o produto", "warning")
	}
})

$('#btn-finalizar-pedido').click(() => {
	$('#forma_pagamento').val('').change()
	let pedido_id = $('#pedido_id').val()

	$('.prods-pedido').html('')
	$.get(path + 'pedidosDelivery/find/'+pedido_id)
	.done((res) => {
		console.clear()
		console.log(res)
		$('#cliente-nome').html(res.cliente.nome + " " + res.cliente.sobre_nome)
		$('#cliente-celular').html(res.cliente.celular)
		if(res.endereco){
			$('#cliente-endereco').html(res.endereco.rua + ", " + res.endereco.numero + " - " + res.endereco._bairro.nome)
		}else{
			$('#cliente-endereco').html("Balcão")
		}
		let total = 0
		res.itens.map((i) => {
			console.log(i)

			total += parseFloat(i.valor)
			i.itens_adicionais.map((a) => {
				total += parseFloat(a.adicional.valor)
			})
		})
		setTimeout(() => {
			$('#total-pedido').html('R$ ' + ((total) + parseFloat(VALORENTREGA)).toFixed(2).replace('.', ','))
			$('#modal-finalizar-pedido').modal('show')
		}, 100)
	})
	.fail((err) => {
		console.log(err)

	})
})

$('#forma_pagamento').change(() => {
	let forma_pagamento = $('#forma_pagamento').val()
	if(forma_pagamento == '01'){
		$('.div-troco').removeClass('d-none')
	}else{
		$('.div-troco').addClass('d-none')
	}
})
