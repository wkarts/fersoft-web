
var MEDIDAS = [];
var COMPONENTES = [];
var TOTALQTD = 0;
var REMETENTE = null;
var DESTINATARIO = null;
var EXPEDIDOR = null;
var RECEBEDOR = null;
var xmlValido = false;
var SOMACOMPONENTES = 0;
var CTEDID = 0;
var CHAVESNFE = []

var CLIENTES = []
$(function () {

	CLIENTES = JSON.parse($('#clientes').val())
	CTEDID = $('#cte_id').val()
	var remetente = $('#kt_select2_1').val();
	if(remetente != 'null'){
		CLIENTES.map((c) => {
			if(c.id == remetente){
				REMETENTE = c

				$('#info-remetente').css('display', 'block');
				$('#nome-remetente').html(c.razao_social)
				$('#cnpj-remetente').html(c.cpf_cnpj)
				$('#ie-remetente').html(c.ie_rg)
				$('#rua-remetente').html(c.rua)
				$('#nro-remetente').html(c.numero)
				$('#bairro-remetente').html(c.bairro)
				$('#cidade-remetente').html(c.cidade.nome + "("+ c.cidade.uf + ")")
			}
		})
	}else{
		$('#kt_select2_1').val('null').change()
	}

	var destinatario = $('#kt_select2_2').val();
	if(destinatario != 'null'){

		CLIENTES.map((c) => {
			if(c.id == destinatario){
				DESTINATARIO = c

				$('#info-destinatario').css('display', 'block');
				$('#nome-destinatario').html(c.razao_social)
				$('#cnpj-destinatario').html(c.cpf_cnpj)
				$('#ie-destinatario').html(c.ie_rg)
				$('#rua-destinatario').html(c.rua)
				$('#nro-destinatario').html(c.numero)
				$('#bairro-destinatario').html(c.bairro)
				$('#cidade-destinatario').html(c.cidade.nome + "("+ c.cidade.uf + ")")
			}
		})
	}else{
		$('#kt_select2_2').val('null').change()
	}

	var expedidor = $('#kt_select2_10').val();
	if(expedidor != 'null'){

		CLIENTES.map((c) => {
			if(c.id == expedidor){
				EXPEDIDOR = c

				$('#info-expedidor').css('display', 'block');
				$('#nome-expedidor').html(c.razao_social)
				$('#cnpj-expedidor').html(c.cpf_cnpj)
				$('#ie-expedidor').html(c.ie_rg)
				$('#rua-expedidor').html(c.rua)
				$('#nro-expedidor').html(c.numero)
				$('#bairro-expedidor').html(c.bairro)
				$('#cidade-expedidor').html(c.cidade.nome + "("+ c.cidade.uf + ")")
			}
		})
	}else{
		$('#kt_select2_10').val('null').change()
	}

	var recebedor = $('#kt_select2_11').val();
	if(recebedor != 'null'){

		CLIENTES.map((c) => {
			if(c.id == recebedor){
				RECEBEDOR = c

				$('#info-recebedor').css('display', 'block');
				$('#nome-recebedor').html(c.razao_social)
				$('#cnpj-recebedor').html(c.cpf_cnpj)
				$('#ie-recebedor').html(c.ie_rg)
				$('#rua-recebedor').html(c.rua)
				$('#nro-recebedor').html(c.numero)
				$('#bairro-recebedor').html(c.bairro)
				$('#cidade-recebedor').html(c.cidade.nome + "("+ c.cidade.uf + ")")
			}
		})
	}else{
		$('#kt_select2_11').val('null').change()
	}

	if(CTEDID > 0){

		COMPONENTES = JSON.parse($('#componentes_cte').val())
		MEDIDAS = JSON.parse($('#medidas_cte').val())

		let t = montaTabelaComponentes();
		$('#componentes tbody').html(t)
		habilitaBtnSalarCTe();

		t = montaTabela2();
		$('#prod tbody').html(t)

		habilitaBtnSalarCTe()
	}

	let chaves = $('#chaves').val()
	if(chaves){
		if(chaves.length > 0){

			if(chaves.length == 44){
				CHAVESNFE.push(chaves)
			}else{
				chaves = chaves.split(";")
				chaves.map((ch) => {

					CHAVESNFE.push(ch)
				})
			}

			montaHtmlChaveNfe((html) => {
				$('#chaves_nfe').html(html)
			})

		}
	}

	// if(chave){
	// 	chaveNfeDuplicada(chave, (chRes) => {
	// 		if(chRes == false){
	// 			$.get(path+'cte/consultaChave', {chave: chave})
	// 			.done((data) => {
	// 				data = JSON.parse(data);
	// 				console.log(data)

	// 				if(data.xMotivo == 'Autorizado o uso da NF-e'){
	// 					xmlValido = true;
	// 					$('#chave_nfe').attr('disabled', true)
	// 				}else{
	// 					swal("Erro", data.xMotivo, "error")
	// 					xmlValido = false;

	// 				}
	// 				habilitaBtnSalarCTe();
	// 			})
	// 			.fail(function(err){
	// 				console.log(err)
	// 				xmlValido = false;
	// 			})
	// 		}else{
	// 			$('#chave_nfe').val('');
	// 			// $('#chave-referenciada').css('display', 'block')
	// 			swal('Erro', 'Esta chave ja esta referênciada em outra CT-e', 'error')

	// 		}
	// 	});
	// }

	let chave_import = $('#chave_import').val();
	if(chave_import){
		xmlValido = true;
		CHAVESNFE.push(chave_import)
		montaHtmlChaveNfe((html) => {
			$('#chaves_nfe').html(html)
		})
	}

});

$('#kt_select2_1').change(() => {
	let remetente = $('#kt_select2_1').val()
	CLIENTES.map((c) => {
		if(c.id == remetente){
			REMETENTE = c

			$('#info-remetente').css('display', 'block');
			$('#nome-remetente').html(c.razao_social)
			$('#cnpj-remetente').html(c.cpf_cnpj)
			$('#ie-remetente').html(c.ie_rg)
			$('#rua-remetente').html(c.rua)
			$('#nro-remetente').html(c.numero)
			$('#bairro-remetente').html(c.bairro)
			$('#cidade-remetente').html(c.cidade.nome + "("+ c.cidade.uf + ")")
		}
	})
})

$('#kt_select2_2').change(() => {
	let dest = $('#kt_select2_2').val()
	CLIENTES.map((c) => {
		if(c.id == dest){
			DESTINATARIO = c

			$('#info-destinatario').css('display', 'block');
			$('#nome-destinatario').html(c.razao_social)
			$('#cnpj-destinatario').html(c.cpf_cnpj)
			$('#ie-destinatario').html(c.ie_rg)
			$('#rua-destinatario').html(c.rua)
			$('#nro-destinatario').html(c.numero)
			$('#bairro-destinatario').html(c.bairro)
			$('#cidade-destinatario').html(c.cidade.nome + "("+ c.cidade.uf + ")")
		}
	})
})

$('#kt_select2_10').change(() => {
	let expedidor = $('#kt_select2_10').val()

	if(expedidor != 'null'){
		CLIENTES.map((c) => {
			if(c.id == expedidor){
				EXPEDIDOR = c

				$('#info-expedidor').css('display', 'block');
				$('#nome-expedidor').html(c.razao_social)
				$('#cnpj-expedidor').html(c.cpf_cnpj)
				$('#ie-expedidor').html(c.ie_rg)
				$('#rua-expedidor').html(c.rua)
				$('#nro-expedidor').html(c.numero)
				$('#bairro-expedidor').html(c.bairro)
				$('#cidade-expedidor').html(c.cidade.nome + "("+ c.cidade.uf + ")")
			}
		})
	}else{
		EXPEDIDOR = null
		$('#info-expedidor').css('display', 'none');
	}
})

$('#kt_select2_11').change(() => {
	let recebedor = $('#kt_select2_11').val()
	if(recebedor != 'null'){

		CLIENTES.map((c) => {
			if(c.id == recebedor){
				RECEBEDOR = c

				$('#info-recebedor').css('display', 'block');
				$('#nome-recebedor').html(c.razao_social)
				$('#cnpj-recebedor').html(c.cpf_cnpj)
				$('#ie-recebedor').html(c.ie_rg)
				$('#rua-recebedor').html(c.rua)
				$('#nro-recebedor').html(c.numero)
				$('#bairro-recebedor').html(c.bairro)
				$('#cidade-recebedor').html(c.cidade.nome + "("+ c.cidade.uf + ")")
			}
		})
	}else{
		RECEBEDOR = null
		$('#info-recebedor').css('display', 'none');
	}
})

function removeEspacoChave(){
	let chave = $('#chave_nfe').val();
	return chave.replace(' ', '').replace(' ', '').replace(' ', '')
	.replace(' ', '').replace(' ', '').replace(' ', '').replace(' ', '')
	.replace(' ', '').replace(' ', '').replace(' ', '');
}

$('.type-ref').on('keyup', () => {
	habilitaBtnSalarCTe();
})

$('#file').change(function() {
	$('#form-import').submit();
});

function addNfeRef(){
	let chave = removeEspacoChave();

	if(chave.length == 44){
		chaveNfeDuplicada(chave, (chRes) => {
			if(chRes == false){
				// $.get(path+'cte/consultaChave', {chave: chave})
				// .done((data) => {
				// 	data = JSON.parse(data);

				// 	if(data.xMotivo == 'Autorizado o uso da NF-e'){
				// 		$('#chave_nfe').val('');
				// 		adicionarChaveArray(chave);
				// 	}else{
				// 		swal('Erro', data.xMotivo, 'error')
				// 	}
				// 	habilitaBtnSalarCTe();
				// })
				// .fail(function(err){
				// 	console.log(err)
				// 	swal('Erro', 'Erro ao consultar chave', 'error')

				// })
				adicionarChaveArray(chave);
				
			}else{
				$('#chave_nfe').val('');
				// $('#chave-referenciada').css('display', 'block')

				swal({
					title: "Cuidado",
					text: "Esta chave ja esta referênciada em outra CT-e",
					buttons: ["Cancelar", "Adicionar mesmo assim"],
					icon: "warning"
				}).then((btn) => {
					if(btn){
						$.get(path+'cte/consultaChave', {chave: chave})
						.done((data) => {
							data = JSON.parse(data);

							if(data.xMotivo == 'Autorizado o uso da NF-e'){
								$('#chave_nfe').val('');
								adicionarChaveArray(chave);
							}else{
								swal('Erro', data.xMotivo, 'error')
								adicionarChaveArray(chave);

							}
							habilitaBtnSalarCTe();
						})
						.fail(function(err){
							console.log(err)
							swal('Erro', 'Erro ao consultar chave', 'error')
						})
					}
				})


			}
		})
	}else{
		swal('Erro', 'Chave NFe possui 44 digitos!', 'error')

	}
}

function adicionarChaveArray(chave){
	if(!CHAVESNFE.includes(chave)){
		//insere
		CHAVESNFE.push(chave)
		montaHtmlChaveNfe((html) => {
			$('#chaves_nfe').html(html)
		})
		
	}else{
		swal('Erro', 'Esta chave ja esta na lista', 'error')


	}

	if(CHAVESNFE.length > 0) xmlValido = true
}

function montaHtmlChaveNfe(call){
	let html = '';
	CHAVESNFE.map((ch) => {
		html += '<div class="col-xl-12">';
		html += '<h3>'+ch+'<i onclick="deleteChave(\''+ch+'\')" class="la la-trash text-danger"></i></h3></div>';
	})
	call(html)
}

function deleteChave(chave){
	let temp = [];
	CHAVESNFE.map((ch) => {
		if(ch != chave) temp.push(ch)
	})

	CHAVESNFE = temp;
	montaHtmlChaveNfe((html) => {
		$('#chaves_nfe').html(html)
	})
}

// $('#chave_nfe').on('keyup', () => {
// 	console.log('passou');

// 	let chave = removeEspacoChave();
// 	console.log(xmlValido)
// 	if(chave.length == 44 && xmlValido == false){

// 		chaveNfeDuplicada(chave, (chRes) => {
// 			if(chRes == false){
// 				$.get(path+'cte/consultaChave', {chave: chave})
// 				.done((data) => {
// 					data = JSON.parse(data);
// 					console.log(data)

// 					if(data.xMotivo == 'Autorizado o uso da NF-e'){
// 						xmlValido = true;
// 						$('#chave_nfe').attr('disabled', true)
// 					}else{
// 						xmlValido = false;

// 					}
// 					habilitaBtnSalarCTe();
// 				})
// 				.fail(function(err){
// 					console.log(err)
// 					xmlValido = false;
// 				})
// 			}else{
// 				$('#chave_nfe').val('');
// 				// $('#chave-referenciada').css('display', 'block')
// 				swal('Erro', 'Esta chave ja esta referênciada em outra CT-e', 'error')


// 			}
// 		});
// 	}
// });

$('.ref-nfe').click(() => {
	$('#descOutros').val("")
	$('#nDoc').val("")
	$('#vDocFisc').val("")
})

$('.ref-out').click(() => {
	$('#chave_nfe').val("")

})

$('.select-mun').change(() => {
	habilitaBtnSalarCTe()
})

function chaveNfeDuplicada(chave, call){

	$.get(path+'cte/chaveNfeDuplicada', {chave: chave})
	.done((success) => {
		call(success)
	})
	.fail((err) => {
		console.log(err)
		call(err)
	})
}

$('input.autocomplete-remetente').on('keyup', () => {
	var cliente = $('#autocomplete-remetente').val().split('-');
	if(!cliente[0] || !cliente[1] && REMETENTE != null){
		$('input.autocomplete-remetente').val('')
	}
})


function getClientes(data){
	$.ajax
	({
		type: 'GET',
		url: path + 'clientes/all',
		dataType: 'json',
		success: function(e){
			data(e)
		}, error: function(e){
			console.log(e)
		}

	});
}

function getCliente(id, data){
	$.ajax
	({
		type: 'GET',
		url: path + 'clientes/find/'+id,
		dataType: 'json',
		success: function(e){
			data(e)

		}, error: function(e){
			console.log(e)
		}

	});
}

function habilitaBtnSalarCTe(){
	console.log("testando")
	let tipoDocumento = false;
	let inputs = false;

	if(!xmlValido && $('#descOutros').val() != "" && $('#nDoc').val() != "" && $('#vDocFisc').val() != ""){
		tipoDocumento = true;
	}else if(xmlValido && $('#descOutros').val() == "" && $('#nDoc').val() == "" && 
		$('#vDocFisc').val() == ""){
		tipoDocumento = true
	}

	console.log(tipoDocumento)
	console.log(xmlValido)

	if($('#prod_predominante').val() != "" && $('#valor_carga').val() != "" && $('#valor_transporte').val() != "" && $('#valor_receber').val() != "" && $('#kt_select2_5').val() != 'null' && $('#kt_select2_8').val() != 'null' && $('#kt_select2_7').val() != 'null'){
		inputs = true;
	}

	console.log(tipoDocumento)
	console.log(inputs)

	let filial = true
	if($('#filial_id')){
		if($('#filial_id').val() == ""){
			filial = false
		}
	}

	if(MEDIDAS.length > 0 && COMPONENTES.length > 0 && DESTINATARIO != null && REMETENTE != null && tipoDocumento && inputs && filial){
		$('#finalizar').removeClass('disabled')

	}
}

$('#endereco-destinatario').click(() => {
	let v = $('#endereco-destinatario').is(':checked');
	$('#endereco-remetente').prop('checked', false);
	if(v){
		if(DESTINATARIO){
			$('#rua_tomador').val(DESTINATARIO.rua)
			$('#numero_tomador').val(DESTINATARIO.numero)
			$('#bairro_tomador').val(DESTINATARIO.bairro)
			$('#cep_tomador').val(DESTINATARIO.cep)
			$('#kt_select2_4').val(DESTINATARIO.cidade.id).change()
			$('#kt_select2_5').val(REMETENTE.cidade.id).change()
			$('#kt_select2_8').val(REMETENTE.cidade.id).change()
			$('#kt_select2_7').val(DESTINATARIO.cidade.id).change()

			habilitaCampos();

		}else{
			// alert('Destinatário não selecionado!');
			swal("Erro!", "Destinatário não selecionado!", "warning")

			$('#endereco-destinatario').prop('checked', false); 
			
		}
	}else{
		desabilitaCampos();
	}
})

$('#endereco-remetente').click(() => {
	let v = $('#endereco-remetente').is(':checked');
	$('#endereco-destinatario').prop('checked', false);
	if(v){
		if(REMETENTE){
			$('#rua_tomador').val(REMETENTE.rua)
			$('#numero_tomador').val(REMETENTE.numero)
			$('#bairro_tomador').val(REMETENTE.bairro)
			$('#cep_tomador').val(REMETENTE.cep)
			$('#kt_select2_4').val(REMETENTE.cidade.id).change()

			$('#kt_select2_5').val(DESTINATARIO.cidade.id).change()
			$('#kt_select2_8').val(DESTINATARIO.cidade.id).change()
			$('#kt_select2_7').val(REMETENTE.cidade.id).change()
			
			habilitaCampos();

		}else{
			// alert('Remetente não selecionado!');
			swal("Erro!", "Remetente não selecionado!", "warning")

			$('#endereco-remetente').prop('checked', false); 
		}
	}else{
		desabilitaCampos();
	}
})

function habilitaCampos(){
	$('#rua_tomador').prop('disabled', true)
	$('#numero_tomador').prop('disabled', true)
	$('#bairro_tomador').prop('disabled', true)
	$('#cep_tomador').prop('disabled', true)
	$('#autocomplete-cidade-tomador').prop('disabled', true)
}

function desabilitaCampos(){
	$('#rua_tomador').removeAttr('disabled')
	$('#numero_tomador').removeAttr('disabled')
	$('#bairro_tomador').removeAttr('disabled')
	$('#cep_tomador').removeAttr('disabled')
	$('#autocomplete-cidade-tomador').removeAttr('disabled')
}

function getCidades(data){
	$.ajax
	({
		type: 'GET',
		url: path + 'cidades/all',
		dataType: 'json',
		success: function(e){
			data(e)

		}, error: function(e){
			console.log(e)
		}

	});
}

// $('#addComponente').click(() => {
// 	let nome_componente = $('#nome_componente').val();
// 	let valor_componente = $('#valor_componente').val();
// 	COMPONENTES.push({id: (COMPONENTES.length+1), valor: valor_componente,
// 		nome: nome_componente});
// 	let t = montaTabelaComponentes();
// 	$('#componentes tbody').html(t)
// 	habilitaBtnSalarCTe();
// });

$('#addComponente').click(() => {
	let nome_componente = $('#nome_componente').val();
	if(nome_componente.length <= 15){
		let valor_componente = $('#valor_componente').val();
		COMPONENTES.push({id: (COMPONENTES.length+1), valor: valor_componente,
			nome: nome_componente});
		let t = montaTabelaComponentes();
		$('#componentes tbody').html(t)
		habilitaBtnSalarCTe();
	}else{
		swal("", "Informe no máximo 15 caracteres", "warning")
	}
});

$('#addMedida').click(() => {
	let unidade_medida = $('#unidade_medida').val();
	let tipo_medida = $('#tipo_medida').val();
	let quantidade = $('#quantidade_carga').val();
	if(quantidade.includes(',')){
		MEDIDAS.push({id: (MEDIDAS.length+1), unidade_medida: unidade_medida,
			tipo_medida: tipo_medida, quantidade: quantidade});

		console.log(MEDIDAS)
		let t = montaTabela();
		$('#prod tbody').html(t)

		habilitaBtnSalarCTe()
	}else{
		// alert('Quantidade inválida, utilize 4 casas decimais exemplo: 1,0000')
		swal("Erro!", "Quantidade inválida, utilize 4 casas decimais exemplo: 1,0000", "warning")


	}
});

function montaTabela(){
	let t = ""; 
	MEDIDAS.map((v) => {
		console.log(v)
		t += '<tr class="datatable-row">'
		t += '<td class="datatable-cell"><span class="codigo" style="width: 150px;" id="id">'
		t += v.id
		t += '</span></td>'

		t += '<td class="datatable-cell"><span class="codigo" style="width: 120px;" id="id">'
		t += v.unidade_medida
		t += '</span></td>'

		t += '<td class="datatable-cell"><span class="codigo" style="width: 120px;" id="id">'
		t += v.tipo_medida
		t += '</span></td>'

		t += '<td class="datatable-cell"><span class="codigo" style="width: 120px;" id="id">'
		t += v.quantidade
		t += '</span></td>'

		t += '<td class="datatable-cell"><span class="codigo" style="width: 120px;" id="id">'
		t += '<a onclick="deleteItem('+v.id+')" class="btn btn-sm btn-danger"><i class="la la-trash"></i></a>'
		t += '</span></td>'

		t+= "</tr>";
	});
	return t;
}

function montaTabela2(){
	let t = ""; 

	let temp = MEDIDAS
	MEDIDAS = []
	temp.map((m) => {
		console.log(m)
		MEDIDAS.push({id: (MEDIDAS.length+1), unidade_medida: m.cod_unidade,
			tipo_medida: m.tipo_medida, quantidade: m.quantidade_carga});
	})

	console.log("MEDIDAS", MEDIDAS)

	return montaTabela()
}

$('#autocomplete-remetente').focus(() => {
	$('#info-remetente').css('display', 'none');
	REMETENTE = null;
})

$('#autocomplete-destinatario').focus(() => {
	$('#info-destinatario').css('display', 'none');
	DESTINATARIO = null;
})

function montaTabelaComponentes(){
	let t = ""; 
	SOMACOMPONENTES = 0;
	COMPONENTES.map((v) => {

		t += '<tr class="datatable-row">'
		t += '<td class="datatable-cell"><span class="codigo" style="width: 150px;" id="id">'
		t += v.id
		t += '</span></td>'

		t += '<td class="datatable-cell"><span class="codigo" style="width: 120px;" id="id">'
		t += v.nome
		t += '</span></td>'

		t += '<td class="datatable-cell"><span class="codigo" style="width: 120px;" id="id">'
		t += v.valor
		t += '</span></td>'

		t += '<td class="datatable-cell"><span class="codigo" style="width: 120px;" id="id">'
		t += '<a onclick="deleteComponente('+v.id+')" class="btn btn-sm btn-danger"><i class="la la-trash"></i></a>'
		t += '</span></td>'

		t+= "</tr>";



		SOMACOMPONENTES += parseFloat(v.valor.replace(',', '.'));
	});
	$('#valor_receber').val(SOMACOMPONENTES.toFixed(2));
	$('#valor_transporte').val(SOMACOMPONENTES.toFixed(2));
	return t;
}

function deleteItem(id){
	let temp = [];
	MEDIDAS.map((v) => {
		if(v.id != id){
			temp.push(v)
		}
	});
	MEDIDAS = temp;
	refatoreItens()
	let t = montaTabela(); // para remover
	$('#prod tbody').html(t)

}

function refatoreItens(){
	let cont = 1;
	let temp = [];
	MEDIDAS.map((v) => {
		v.id = cont;
		temp.push(v)
		cont++;
	})
	MEDIDAS = temp;
}

function deleteComponente(id){
	let temp = [];
	COMPONENTES.map((v) => {
		if(v.id != id){
			temp.push(v)
		}
	});
	COMPONENTES = temp;
	refatoreComponentes()
	let t = montaTabelaComponentes(); // para remover
	$('#componentes tbody').html(t)

}

function refatoreComponentes(){
	let cont = 1;
	let temp = [];
	COMPONENTES.map((v) => {
		v.id = cont;
		temp.push(v)
		cont++;
	})
	COMPONENTES = temp;
}

function getChavesNfeRef(call){
	let temp = ""
	CHAVESNFE.map((ch, index) => {
		console.log(index)
		temp += ch + (index+1 < CHAVESNFE.length ? ";" : "")
	})
	call(temp)
}

$('#filial_id').change(() => {
	habilitaBtnSalarCTe()
})

function salvarCTe(){
	let msg = "";

	let valorTransporte = $('#valor_transporte').val();
	let valorCarga = $('#valor_carga').val();
	let valorReceber = $('#valor_receber').val();
	let data = $('#kt_datepicker_3').val();
	if(valorTransporte == 0 || valorTransporte.length == 0){
		msg += "\nInforme o valor de transporte";
	}

	if(valorCarga == 0 || valorCarga.length == 0){
		msg += "\nInforme o valor da carga";
	}

	if(valorReceber == 0 || valorReceber.length == 0){
		msg += "\nInforme o valor a receber";
	}

	if(data == "" || valorReceber.length == 0){
		msg += "\nInforme a data de entrega";
	}

	if(msg == ""){

		getChavesNfeRef((chaves) => {
			let js = {
				cte_id: CTEDID,
				chave_nfe: chaves,
				remetente: parseInt(REMETENTE.id),
				destinatario: parseInt(DESTINATARIO.id),
				expedidor: EXPEDIDOR != null ? parseInt(EXPEDIDOR.id) : null,
				recebedor: RECEBEDOR != null ? parseInt(RECEBEDOR.id) : null,
				tomador: $('#tomador').val(),
				municipio_envio: $('#kt_select2_5').val(),
				municipio_inicio: $('#kt_select2_8').val(),
				municipio_fim: $('#kt_select2_7').val(),
				numero_tomador: $('#numero_tomador').val(),
				bairro_tomador: $('#bairro_tomador').val(),
				municipio_tomador: $('#kt_select2_4').val(),
				logradouro_tomador: $('#rua_tomador').val(),
				cep_tomador: $('#cep_tomador').val(),
				filial_id: $('#filial_id') ? $('#filial_id').val() : -1,
				medidias: MEDIDAS,
				componentes: COMPONENTES,
				valor_carga: valorCarga,
				valor_receber: $('#valor_receber').val(),
				valor_transporte: valorTransporte,
				produto_predominante: $('#prod_predominante').val(),
				data_prevista_entrega: $('#kt_datepicker_3').val(),
				natureza: $('#natureza').val(),
				obs: $('#obs').val(),
				retira: $('#retira').val(),
				detalhes_retira: $('#detalhes_retira').val(),
				modal: $('#modal-transp').val(),
				veiculo_id: $('#veiculo_id').val(),

				tpDoc: $('#tpDoc').val(),
				descOutros: $('#descOutros').val(),
				nDoc: $('#nDoc').val(),
				vDocFisc: $('#vDocFisc').val(),
				globalizado: $('#globalizado').val(),
				tipo_servico: $('#tipo_servico').val(),
				cst: $('#cst').val(),
				perc_icms: $('#perc_icms').val(),
				pRedBC: $('#pRedBC').val(),

			}
			console.log(js)

			let url = 'cte/salvar'
			if(CTEDID > 0) url = 'cte/update'
				$.post(path+url, {data: js, _token: $('#_token').val()})
			.done(function(v){
				console.log(v)
				sucesso();
			})
			.fail(function(err){
				console.log(err)
				swal("Ops!!", "Erro ao salvar documento!!", "error")
			})
		})
	}else{
		// alert("Informe corretamente os campos para continuar!"+msg)
		swal("Erro!", "Informe corretamente os campos para continuar!"+msg, "warning")
	}
}

function sucesso(){
	audioSuccess()
	$('#content').css('display', 'none');
	$('#anime').css('display', 'block');
	setTimeout(() => {
		location.href = path+'cte';
	}, 4500)
}

function novoRemetente(){
	$('#remetente_destinatario').val('remetente')
	$('#modal-cliente').modal('show')
}

function novoDestinatario(){
	$('#remetente_destinatario').val('destinatario')
	$('#modal-cliente').modal('show')
}

function novoExpedidor(){
	$('#remetente_destinatario').val('expedidor')
	$('#modal-cliente').modal('show')
}

function novoRecebedor(){
	$('#remetente_destinatario').val('recebedor')
	$('#modal-cliente').modal('show')
}

function salvarCliente(){
	let js = {
		razao_social: $('#razao_social2').val(),
		nome_fantasia: $('#nome_fantasia2').val() ? $('#nome_fantasia2').val() : '',
		rua: $('#rua').val() ? $('#rua').val() : '',
		cpf_cnpj: $('#cpf_cnpj').val() ? $('#cpf_cnpj').val() : '',
		ie_rg: $('#ie_rg').val() ? $('#ie_rg').val() : '',
		bairro: $('#bairro').val() ? $('#bairro').val() : '',
		cep: $('#cep').val() ? $('#cep').val() : '',
		consumidor_final: $('#consumidor_final').val() ? $('#consumidor_final').val() : '',
		contribuinte: $('#contribuinte').val() ? $('#contribuinte').val() : '',
		limite_venda: $('#limite_venda').val() ? $('#limite_venda').val() : '',
		cidade_id: $('#kt_select2_9').val() ? $('#kt_select2_9').val() : NULL,
		telefone: $('#telefone').val() ? $('#telefone').val() : '',
		celular: $('#celular').val() ? $('#celular').val() : '',
		numero: $('#numero2').val() ? $('#numero2').val() : '',
	}

	if(js.razao_social == ''){
		swal("Erro", "Informe a razão social", "warning")
	}

	if(js.cpf_cnpj == ''){
		swal("Erro", "Informe o CPF/CNPJ", "warning")
	}else{
		swal({
			title: "Cuidado",
			text: "Ao salvar o cliente com os dados incompletos não será possível emitir CTe até que edite o seu cadstro?",
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
					CLIENTE = res;
					console.log(res)
					let tipo = $('#remetente_destinatario').val()

					if(tipo == 'remetente'){
						$('#kt_select2_1').append('<option value="'+res.id+'">'+ 
							res.razao_social+' ('+res.cpf_cnpj+')</option>')

						$('#info-remetente').css('display', 'block');
						$('#nome-remetente').html(res.razao_social)
						$('#cnpj-remetente').html(res.cpf_cnpj)
						$('#ie-remetente').html(res.ie_rg)
						$('#rua-remetente').html(res.rua)
						$('#nro-remetente').html(res.numero)
						$('#bairro-remetente').html(res.bairro)
						$('#cidade-remetente').html(res.cidade.nome + "("+ res.cidade.uf + ")")

						$('#kt_select2_1').val(res.id).change();

						REMETENTE = res

					}else if(tipo == 'destinatario'){
						$('#kt_select2_2').append('<option value="'+res.id+'">'+ 
							res.razao_social+' ('+res.cpf_cnpj+')</option>')

						$('#info-destinatario').css('display', 'block');
						$('#nome-destinatario').html(res.razao_social)
						$('#cnpj-destinatario').html(res.cpf_cnpj)
						$('#ie-destinatario').html(res.ie_rg)
						$('#rua-destinatario').html(res.rua)
						$('#nro-destinatario').html(res.numero)
						$('#bairro-destinatario').html(res.bairro)
						$('#cidade-destinatario').html(res.cidade.nome + "("+ res.cidade.uf + ")")
						
						$('#kt_select2_2').val(res.id).change();

						DESTINATARIO = res

					}else if(tipo == 'expedidor'){
						$('#kt_select2_10').append('<option value="'+res.id+'">'+ 
							res.razao_social+' ('+res.cpf_cnpj+')</option>')

						$('#info-expedidor').css('display', 'block');
						$('#nome-expedidor').html(res.razao_social)
						$('#cnpj-expedidor').html(res.cpf_cnpj)
						$('#ie-expedidor').html(res.ie_rg)
						$('#rua-expedidor').html(res.rua)
						$('#nro-expedidor').html(res.numero)
						$('#bairro-expedidor').html(res.bairro)
						$('#cidade-expedidor').html(res.cidade.nome + "("+ res.cidade.uf + ")")
						
						$('#kt_select2_10').val(res.id).change();

					}else if(tipo == 'recebedor'){
						$('#kt_select2_11').append('<option value="'+res.id+'">'+ 
							res.razao_social+' ('+res.cpf_cnpj+')</option>')

						$('#info-recebedor').css('display', 'block');
						$('#nome-recebedor').html(res.razao_social)
						$('#cnpj-recebedor').html(res.cpf_cnpj)
						$('#ie-recebedor').html(res.ie_rg)
						$('#rua-recebedor').html(res.rua)
						$('#nro-recebedor').html(res.numero)
						$('#bairro-recebedor').html(res.bairro)
						$('#cidade-recebedor').html(res.cidade.nome + "("+ res.cidade.uf + ")")
						
						$('#kt_select2_11').val(res.id).change();

					}

					let strTipo = ''
					if(tipo == 'remetente'){
						strTipo = 'Remetente'
					}else if(tipo == 'destinatario'){
						strTipo = 'Destinatário'
					}else if(tipo == 'expedidor'){
						strTipo = 'Expedidor'
					}else{
						strTipo = 'Recebedor'
					}
					swal("Sucesso", strTipo + " adicionado!!", 'success')
					.then(() => {
						$('#modal-cliente').modal('hide')
					})
				})
				.fail((err) => {
					console.log(err)
					swal("Alerta", err.responseJSON, "warning")
				})
			}
		})
	}

	console.log(js)
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

function consultaCadastro(){
	let cpf_cnpj = $('#cpf_cnpj').val();
	cpf_cnpj = cpf_cnpj.replace(/[^0-9]/g,'')
	if(cpf_cnpj.length == 14){
		$('#btn-consulta-cadastro').addClass('spinner')
		$.get('https://publica.cnpj.ws/cnpj/' + cpf_cnpj)
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
		})
	}
}


function findCidadeCodigo(codigo_ibge){

	$.get(path + "cidades/cidadePorCodigoIbge/" + codigo_ibge)
	.done((res) => {
		console.log(res)
		$('#kt_select2_9').val(res.id).change();
	})
	.fail((err) => {
		console.log(err)
	})

}


// function consultaCadastro() {
// 	let cnpj = $('#cpf_cnpj').val();
// 	let uf = $('#sigla_uf').val();
// 	cnpj = cnpj.replace('.', '');
// 	cnpj = cnpj.replace('.', '');
// 	cnpj = cnpj.replace('-', '');
// 	cnpj = cnpj.replace('/', '');

// 	if (cnpj.length == 14 && uf.length != '--') {
// 		$('#btn-consulta-cadastro').addClass('spinner')

// 		$.ajax
// 		({
// 			type: 'GET',
// 			data: {
// 				cnpj: cnpj,
// 				uf: uf
// 			},
// 			url: path + 'nf/consultaCadastro',

// 			dataType: 'json',

// 			success: function (e) {
// 				$('#btn-consulta-cadastro').removeClass('spinner')

// 				console.log(e)
// 				if (e.infCons.infCad) {
// 					let info = e.infCons.infCad;
// 					console.log(info)

// 					$('#ie_rg').val(info.IE)
// 					$('#razao_social2').val(info.xNome)
// 					$('#nome_fantasia2').val(info.xFant ? info.xFant : info.xNome)

// 					$('#rua').val(info.ender.xLgr)
// 					$('#numero2').val(info.ender.nro)
// 					$('#bairro').val(info.ender.xBairro)
// 					let cep = info.ender.CEP;
// 					$('#cep').val(cep.substring(0, 5) + '-' + cep.substring(5, 9))

// 					findNomeCidade(info.ender.xMun, (res) => {
// 						console.log(res)
// 						let jsCidade = JSON.parse(res);
// 						console.log(jsCidade)
// 						if (jsCidade) {
// 							console.log(jsCidade.id + " - " + jsCidade.nome)
// 							$('#kt_select2_9').val(jsCidade.id).change();
// 						}
// 					})

// 				} else {
// 					swal("Erro", e.infCons.xMotivo, "error")

// 				}
// 			}, error: function (e) {
// 				consultaAlternativa(cnpj, (data) => {
// 					console.log(data)
// 					if(data == false){
// 						swal("Alerta", "Nenhum retorno encontrado para este CNPJ, informe manualmente por gentileza", "warning")
// 					}else{
// 						$('#razao_social2').val(data.nome)
// 						$('#nome_fantasia2').val(data.nome)

// 						$('#rua').val(data.logradouro)
// 						$('#numero2').val(data.numero)
// 						$('#bairro').val(data.bairro)
// 						let cep = data.cep;
// 						$('#cep').val(cep.replace(".", ""))

// 						findNomeCidade(data.municipio, (res) => {
// 							let jsCidade = JSON.parse(res);
// 							console.log(jsCidade)
// 							if (jsCidade) {
// 								console.log(jsCidade.id + " - " + jsCidade.nome)
// 								$('#kt_select2_9').val(jsCidade.id).change();
// 							}
// 						})
// 					}
// 				})
// 				$('#btn-consulta-cadastro').removeClass('spinner')
// 			}
// 		});
// 	}else{
// 		swal("Alerta", "Informe corretamente o CNPJ e UF", "warning")
// 	}
// }

function limparCamposCliente(){
	$('#razao_social2').val('')
	$('#nome_fantasia2').val('')

	$('#rua').val('')
	$('#numero2').val('')
	$('#bairro').val('')
	$('#cep').val('')
	$('#kt_select2_9').val('1').change();
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
			console.log(data);
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

function findNomeCidade(nomeCidade, call) {

	$.get(path + 'cidades/findNome/' + nomeCidade)
	.done((success) => {
		call(success)
	})
	.fail((err) => {
		call(err)
	})
}
