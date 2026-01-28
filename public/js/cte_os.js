

var EMITENTE = null;
var TOMADOR = null;
var CTEDID = 0;

var CLIENTES = []
$(function () {

	CLIENTES = JSON.parse($('#clientes').val())
	CTEDID = $('#cte_id').val()
	var emitente = $('#kt_select2_1').val();
	if(emitente != 'null'){
		CLIENTES.map((c) => {
			if(c.id == emitente){
				EMITENTE = c

				$('#info-emitente').css('display', 'block');
				$('#nome-emitente').html(c.razao_social)
				$('#cnpj-emitente').html(c.cpf_cnpj)
				$('#ie-emitente').html(c.ie_rg)
				$('#rua-emitente').html(c.rua)
				$('#nro-emitente').html(c.numero)
				$('#bairro-emitente').html(c.bairro)
				$('#cidade-emitente').html(c.cidade.nome + "("+ c.cidade.uf + ")")
			}
		})
	}else{
		$('#kt_select2_1').val('null').change()
	}

	var tomador = $('#kt_select2_2').val();
	if(tomador != 'null'){

		CLIENTES.map((c) => {
			if(c.id == tomador){
				TOMADOR = c

				$('#info-tomador').css('display', 'block');
				$('#nome-tomador').html(c.razao_social)
				$('#cnpj-tomador').html(c.cpf_cnpj)
				$('#ie-tomador').html(c.ie_rg)
				$('#rua-tomador').html(c.rua)
				$('#nro-tomador').html(c.numero)
				$('#bairro-tomador').html(c.bairro)
				$('#cidade-tomador').html(c.cidade.nome + "("+ c.cidade.uf + ")")
			}
		})
	}else{
		$('#kt_select2_2').val('null').change()
	}


	if(CTEDID > 0){
		habilitaBtnSalarCTe()
	}

});

$('#kt_select2_1').change(() => {
	let emitente = $('#kt_select2_1').val()
	CLIENTES.map((c) => {
		if(c.id == emitente){
			EMITENTE = c

			$('#info-emitente').css('display', 'block');
			$('#nome-emitente').html(c.razao_social)
			$('#cnpj-emitente').html(c.cpf_cnpj)
			$('#ie-emitente').html(c.ie_rg)
			$('#rua-emitente').html(c.rua)
			$('#nro-emitente').html(c.numero)
			$('#bairro-emitente').html(c.bairro)
			$('#cidade-emitente').html(c.cidade.nome + "("+ c.cidade.uf + ")")
		}
	})
})

$('#kt_select2_2').change(() => {
	let tomador = $('#kt_select2_2').val()
	CLIENTES.map((c) => {
		if(c.id == tomador){
			TOMADOR = c

			$('#info-tomador').css('display', 'block');
			$('#nome-tomador').html(c.razao_social)
			$('#cnpj-tomador').html(c.cpf_cnpj)
			$('#ie-tomador').html(c.ie_rg)
			$('#rua-tomador').html(c.rua)
			$('#nro-tomador').html(c.numero)
			$('#bairro-tomador').html(c.bairro)
			$('#cidade-tomador').html(c.cidade.nome + "("+ c.cidade.uf + ")")
		}
	})
})


$('.type-ref').on('keyup', () => {
	habilitaBtnSalarCTe();
})


$('.select-mun').change(() => {
	habilitaBtnSalarCTe()
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
	let inputs = false;

	if($('#quantidade_carga').val() != "" && $('#valor_receber').val() != "" && $('#valor_transporte').val() != "" && $('#descricao_servico').val() != "" && $('#kt_select2_5').val() != 'null' && $('#kt_select2_8').val() != 'null' && $('#kt_select2_7').val() != 'null'){
		inputs = true;
	}


	if(EMITENTE != null && TOMADOR != null && inputs){
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
			$('#kt_select2_5').val(DESTINATARIO.cidade.id).change()
			$('#kt_select2_8').val(DESTINATARIO.cidade.id).change()
			$('#kt_select2_7').val(REMETENTE.cidade.id).change()

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

function salvarCTe(){
	let msg = "";

	let valorTransporte = $('#valor_transporte').val();
	let valorReceber = $('#valor_receber').val();
	let descServico = $('#descricao_servico').val();
	let qtdCarga = $('#quantidade_carga').val();

	if(valorTransporte == 0 || valorTransporte.length == 0){
		msg += "\nInforme o valor de transporte";
	}

	if(descServico == 0 || descServico.length == 0){
		msg += "\nInforme o descrição do serviço";
	}

	if(valorReceber == 0 || valorReceber.length == 0){
		msg += "\nInforme o valor a receber";
	}

	if(qtdCarga == 0 || qtdCarga.length == 0){
		msg += "\nInforme o quantidade da carga";
	}


	if(msg == ""){


		let js = {
			cte_id: CTEDID,
			emitente_id: parseInt(EMITENTE.id),
			tomador_id: parseInt(TOMADOR.id),
			tomador: $('#tomador').val(),
			municipio_envio: $('#kt_select2_5').val(),
			municipio_inicio: $('#kt_select2_8').val(),
			municipio_fim: $('#kt_select2_7').val(),

			descricao_servico: descServico,
			quantidade_carga: qtdCarga,
			valor_receber: valorReceber,
			valor_transporte: valorTransporte,
			natureza: $('#natureza').val(),
			obs: $('#obs').val(),
			modal: $('#modal-transp').val(),
			veiculo_id: $('#veiculo_id').val(),

			cst: $('#cst').val(),
			perc_icms: $('#perc_icms').val(),
			data_viagem: $('#data_viagem').val(),
			horario_viagem: $('#horario_viagem').val()

		}
		let url = 'cteos/salvar'
		if(CTEDID > 0) url = 'cteos/update'
			$.post(path+url, {data: js, _token: $('#_token').val()})
		.done(function(v){
			sucesso();
		})
		.fail(function(err){
			console.log(err)
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
		location.href = path+'cteos';
	}, 4500)
}

function novoEmitente(){
	$('#remetente_destinatario').val('emitente')
	$('#modal-cliente').modal('show')
}


function novoTomador(){
	$('#remetente_destinatario').val('tomador')
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
					let tipo = $('#remetente_destinatario').val()

					if(tipo == 'emitente'){
						$('#kt_select2_1').append('<option value="'+res.id+'">'+ 
							res.razao_social+' ('+res.cpf_cnpj+')</option>')

						$('#info-emitente').css('display', 'block');
						$('#nome-emitente').html(res.razao_social)
						$('#cnpj-emitente').html(res.cpf_cnpj)
						$('#ie-emitente').html(res.ie_rg)
						$('#rua-emitente').html(res.rua)
						$('#nro-emitente').html(res.numero)
						$('#bairro-emitente').html(res.bairro)
						$('#cidade-emitente').html(res.cidade.nome + "("+ res.cidade.uf + ")")

						$('#kt_select2_1').val(res.id).change();

					}else if(tipo == 'tomador'){
						$('#kt_select2_2').append('<option value="'+res.id+'">'+ 
							res.razao_social+' ('+res.cpf_cnpj+')</option>')

						$('#info-tomador').css('display', 'block');
						$('#nome-tomador').html(res.razao_social)
						$('#cnpj-tomador').html(res.cpf_cnpj)
						$('#ie-tomador').html(res.ie_rg)
						$('#rua-tomador').html(res.rua)
						$('#nro-tomador').html(res.numero)
						$('#bairro-tomador').html(res.bairro)
						$('#cidade-tomador').html(res.cidade.nome + "("+ res.cidade.uf + ")")
						
						$('#kt_select2_2').val(res.id).change();

					}

					let strTipo = ''
					if(tipo == 'emitente'){
						strTipo = 'Emitente'
					}else{
						strTipo = 'Tomador'
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
							$('#kt_select2_9').val(jsCidade.id).change();
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
								$('#kt_select2_9').val(jsCidade.id).change();
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
