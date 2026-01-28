var PRODUTOS = []
$(function () {

	var w = window.innerWidth
	if(w < 900){
		$('#grade').trigger('click')
	}

	setTimeout(() => {

		$("#kt_select2_1").select2({
			minimumInputLength: 2,
			language: "pt-BR",
			placeholder: "Digite para buscar o produto",
			width: "90%",
			ajax: {
				cache: true,
				url: path + 'produtos/autocomplete',
				dataType: "json",
				data: function(params) {
					console.clear()
					let filial = $('#filial').val()
					console.log("filial", filial)
					var query = {
						pesquisa: params.term,
						filial_id: filial
					};
					return query;
				},
				processResults: function(response) {
					console.log("response", response)
					var results = [];

					$.each(response, function(i, v) {
						var o = {};
						o.id = v.id;

					// if(rs.grade){
					// 	p += ' ' + rs.str_grade
					// }

					// if(rs.referencia != ""){
					// 	p += ' | REF: ' + rs.referencia
					// }

					// if(parseFloat(rs.estoqueAtual) > 0){
					// 	p += ' | Estoque: ' + rs.estoqueAtual
					// }
					o.text = v.nome + (v.grade ? " "+v.str_grade : "") + " | R$ " + parseFloat(v.valor_venda).toFixed(2).replace(".", ",")
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

	}, 300)
})


$("#kt_select2_1").change(() => {
	let lista_id = $('#lista_id').val();
	let id = $("#kt_select2_1").val()
	if(id){
		$.get(path + 'produtos/autocompleteProduto', {id: id, lista_id: lista_id})
		.done((res) => {
			PTEMP = PRODUTO = res

			LIMITEDESCONTO = parseFloat(PRODUTO.limite_maximo_desconto);
			VALORDOPRODUTO = parseFloat(PRODUTO.valor_venda);
			let p = PRODUTO.nome
			// if(PRODUTO.referencia != ""){
			// 	p += ' | REF: ' + PRODUTO.referencia
			// }
			// if(parseFloat(PRODUTO.estoqueAtual) > 0){
			// 	p += ' | Estoque: ' + PRODUTO.estoqueAtual
			// }

			$('#valor').val(parseFloat(PRODUTO.valor_venda).toFixed(casas_decimais))
			$('#subtotal').val(parseFloat(PRODUTO.valor_venda).toFixed(casas_decimais))
			$('#quantidade').val(1)
		// $('#produto-search').val(p)
	})
		.fail((err) => {
			console.log(err)
			swal("Erro", "Erro ao encontrar produto", "error")
		})
	}
})


function removerOrcamento(id){
	let senha = $('#pass').val()
	if(senha != ""){

		swal({
			title: 'Cancelamento de orçamento',
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
						location.href="/orcamentoVenda/delete/"+id;
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
		location.href="/orcamentoVenda/delete/"+id;
	}
}

// $('#kt_select2_1').change(() => {
// 	let id = $('#kt_select2_1').val()
// 	PRODUTOS.map((p) => {
// 		if(id == p.id){
// 			$('#valor').val(p.valor_venda.replace(".", ','))
// 			$('#quantidade').val('1,000')
// 			calcSubtotal();
// 		}
// 	})
// })

$('#valor').on('keyup', () => {
	calcSubtotal()
})

function maskMoney(v){
	return v.toFixed(casas_decimais);
}

function calcSubtotal(){
	let quantidade = $('#quantidade').val();
	let valor = $('#valor').val();
	let subtotal = parseFloat(valor.replace(',','.'))*(quantidade.replace(',','.'));

	let sub = maskMoney(subtotal)
	$('#subtotal').val(sub)
}

function getProdutos(data){
	$.ajax
	({
		type: 'GET',
		url: path + 'produtos/all',
		dataType: 'json',
		success: function(e){
			data(e)

		}, error: function(e){
			console.log(e)
		}

	});
}

function setaEmail(){
	buscarDadosCliente();
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
		swal("Alerta", 'Selecione apenas um documento para continuar!', "warning")
	}else{
		$('#modal5').modal('show')
		$.get(path+'orcamentoVenda/consultar_cliente/'+id)
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

function getProduto(id, data){
	$.ajax
	({
		type: 'GET',
		url: path + 'produtos/getProduto/'+id,
		dataType: 'json',
		success: function(e){
			data(e)

		}, error: function(e){
			console.log(e)
		}

	});
}

function enviarEmail(){	

	$('#btn-send-email').addClass('spinner');
	$('#btn-send-email').addClass('disabled');

	let id = 0;
	let cont = 0;
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			id = $(this).find('#id').html();
			cont++
		}
	})

	let email = $('#email').val();
	if(id > 0){

		$.get(path+'orcamentoVenda/enviarEmail', {id: id, email: email})
		.done(function(data){
			$('#btn-send-email').removeClass('disabled');
			$('#btn-send-email').removeClass('spinner');

			swal("Sucesso", 'Email enviado com sucesso!', "success")
			.then(() => {
				location.reload()
			})

		})
		.fail(function(err){
			console.log(err)
			$('#btn-send-email').removeClass('disabled');
			$('#btn-send-email').removeClass('spinner');
			swal("Erro", 'Erro ao enviar email!', "warning")
		})
	}else{	
		$('#modal5').modal('hide')
		swal("Erro", "Escolha um orçamento na lista!!", "error")
	}
}

$('#btn-danfe').click(() => {
	let id = 0
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked'))
			id = $(this).find('#id').html();
	})

	if(id > 0){
		window.open(path + 'orcamentoVenda/rederizarDanfe/' + id);
	}else{
		swal("Erro", "Escolha um orçamento na lista!!", "error")
	}

})

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
		Materialize.toast('Selecione apenas um documento para impressão!', 5000)
	}else{
		if(id > 0){
			window.open(path+"orcamentoVenda/imprimir/"+id, "_blank");
		}else{
			swal("Erro", "Escolha um orçamento na lista!!", "error")
		}
	}
}

function imprimirCompleto(){
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
		window.open(path+"orcamentoVenda/imprimirCompleto/"+id, "_blank");
	}
}

function modalWhatsApp(){
	$('#modal-whatsApp').modal('show')
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

function enviarEmailGrid(id){
	$('#modal5-grid').modal('show')
	$.get(path+'nf/consultar_cliente/'+id)
	.done(function(data){
		data = JSON.parse(data)
		$('#email-grid').val(data.email)

		if(data.email){
			$('#info-email-grid').html('*Este é o email do cadastro');
		}else{
			$('#info-email-grid').html('*Este cliente não possui email cadastrado');
		}
	})
	.fail(function(err){
		console.log(err)
	})
}

function enviarEmail2(){

	$('#btn-send-email2').addClass('disabled');
	$('#btn-send-email2').addClass('spinner');

	let id = 0;
	let cont = 0;
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			id = $(this).find('#id').html();
			cont++
		}
	})

	let email = $('#email-grid').val();

	$.get(path+'orcamentoVenda/enviarEmail', {id: id, email: email})
	.done(function(data){
		$('#btn-send-email2').removeClass('disabled');
		$('#btn-send-email2').removeClass('spinner');
		// alert('Email enviado com sucesso!');
		swal("Sucesso", 'Email enviado com sucesso!', "success")
		.then(() => {
			location.reload()
		})

	})
	.fail(function(err){
		console.log(err)
		$('#btn-send-email2').removeClass('disabled');
		$('#btn-send-email2').removeClass('spinner');
		// alert('Erro ao enviar email!')
		swal("Erro", 'Erro ao enviar email!', "warning")

	})
}