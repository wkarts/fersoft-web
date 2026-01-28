$(function () {

	if ($('#pessoaFisica').is(':checked')) {
		$('#cpf_cnpj').mask('000.000.000-00', { reverse: true });
		$('#lbl_cpf_cnpj').html('CPF');
		$('#lbl_ie_rg').html('RG');
		$('#btn-consulta-cadastro').css('display', 'none')
	} else if($('#pessoaJuridica').is(':checked')){
		$('#cpf_cnpj').mask('00.000.000/0000-00', { reverse: true });
		$('#lbl_cpf_cnpj').html('CNPJ');
		$('#lbl_ie_rg').html('IE');
		$('#btn-consulta-cadastro').css('display', 'block');

	} else if($('#pessoaExt').is(':checked')){
		$('#cpf_cnpj').val('00.000.000/0000-00')
		$('#cpf_cnpj').attr('readonly', 'readonly')
		$('#btn-consulta-cadastro').css('display', 'none')
	}

});

$('#cpf_cnpj').keyup((target) => {
	let doc = target.target.value

	let tipo = $('#pessoaFisica').is(':checked') ? 'f' : 'j'
	if(tipo == 'f' && doc.length == 14){
		consultaCadastradoBD(doc)
	}else if(tipo == 'j' && doc.length == 18){
		consultaCadastradoBD(doc)
	}
})

function consultaCadastradoBD(doc){

	let uri = window.location.pathname;
	let url = '';

	if(uri.split('/')[1] == 'fornecedores'){
		url = 'fornecedores';
	}else if(uri.split('/')[1] == 'clientes' || uri.split('/')[1] == 'locacao'){
		url = 'clientes';
	}
	let documento = doc.replace("/", "_");

	$.get(path + url + '/consultaCadastrado/'+documento)
	.done((success) => {
		if(success.razao_social){
			swal("Alerta", "Já possui um registro com este documento: " + 
				success.razao_social, "warning")
		}
	})
	.fail((err) => {
		console.log(err)
	})
}

$('#pessoaFisica').click(function () {
	$('#lbl_cpf_cnpj').html('CPF');
	$('#lbl_ie_rg').html('RG');
	$('#cpf_cnpj').removeAttr('readonly')
	$('#cpf_cnpj').mask('000.000.000-00', { reverse: true });
	$('#btn-consulta-cadastro').css('display', 'none')
})

$('#pessoaJuridica').click(function () {
	$('#lbl_cpf_cnpj').html('CNPJ');
	$('#lbl_ie_rg').html('IE');
	$('#cpf_cnpj').removeAttr('readonly')
	$('#cpf_cnpj').mask('00.000.000/0000-00', { reverse: true });
	$('#btn-consulta-cadastro').css('display', 'block');
});

$('#pessoaExt').click(function () {
	$('#cpf_cnpj').val('00.000.000/0000-00')
	$('#cpf_cnpj').attr('readonly', 'readonly')
	$('#btn-consulta-cadastro').css('display', 'none')
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
				$('#razao_social').val(data.razao_social)
				$('#nome_fantasia').val(data.estabelecimento.nome_fantasia)
				$("#rua").val(data.estabelecimento.tipo_logradouro + " " + data.estabelecimento.logradouro)
				$('#numero').val(data.estabelecimento.numero)
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
		$('#kt_select2_1').val(res.id).change();
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

// 				if (e.infCons.infCad) {
// 					let info = e.infCons.infCad;
// 					// let info = e.infCons.infCad[0];
// 					console.clear()
// 					console.log("info", info)
// 					if(info.length > 1){
// 						info = info[0]
// 					}

// 					$('#ie_rg').val(info.IE)
// 					$('#razao_social').val(info.xNome)
// 					$('#nome_fantasia').val(info.xFant ? info.xFant : info.xNome)
					
// 					$('#rua').val(info.ender.xLgr)
// 					$('#numero').val(info.ender.nro)
// 					$('#bairro').val(info.ender.xBairro)
// 					let cep = info.ender.CEP;
// 					$('#cep').val(cep.substring(0, 5) + '-' + cep.substring(5, 9))
// 					findCidadeOne(info.ender.xMun, info.UF, (res) => {
// 						let jsCidade = JSON.parse(res);
// 						if (jsCidade) {

// 							$('#kt_select2_1').val(jsCidade.id).change();

// 						}
// 					})

// 				} else {

// 					// swal("Erro", e.infCons.xMotivo, "error")
// 					consultaAlternativa(cnpj, (data) => {

// 						if(data == false){
// 							swal("Alerta", "Nenhum retorno encontrado para este CNPJ, informe manualmente por gentileza", "warning")
// 						}else{
// 							$('#razao_social').val(data.nome)
// 							$('#nome_fantasia').val(data.nome)

// 							$('#rua').val(data.logradouro)
// 							$('#numero').val(data.numero)
// 							$('#bairro').val(data.bairro)
// 							let cep = data.cep;
// 							$('#cep').val(cep.replace(".", ""))

// 							findCidadeOne(data.municipio, data.uf, (res) => {
// 								let jsCidade = JSON.parse(res);
// 								if (jsCidade) {
// 									$('#kt_select2_1').val(jsCidade.id).change();

// 								}
// 							})
// 						}
// 					})

// 				}
// 			}, error: function (e) {

// 				consultaAlternativa(cnpj, (data) => {

// 					if(data == false){
// 						swal("Alerta", "Nenhum retorno encontrado para este CNPJ, informe manualmente por gentileza", "warning")
// 					}else{
// 						console.log("data", data)
// 						$('#razao_social').val(data.nome)
// 						$('#nome_fantasia').val(data.nome)

// 						$('#rua').val(data.logradouro)
// 						$('#numero').val(data.numero)
// 						$('#bairro').val(data.bairro)
// 						let cep = data.cep;

// 						$('#cep').val(cep.replace(".", ""))

// 						findCidadeOne(data.municipio, data.uf, (res) => {
// 							let jsCidade = JSON.parse(res);
// 							if (jsCidade) {
// 								$('#kt_select2_1').val(jsCidade.id).change();

// 							}
// 						})
// 					}
// 				})

// 				$('#btn-consulta-cadastro').removeClass('spinner')

// 			}

// 		});
// 	}
// }

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

function findCidadeOne(nome, uf, call) {
	$.get(path + 'cidades/findOne', {nome: nome, uf: uf})
	.done((success) => {
		call(success)
	})
	.fail((err) => {
		call(err)
	})
}