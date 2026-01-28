
$('#testar').click(() => {
	$('#testar').addClass('spinner')
	$.ajax
	({
		type: 'GET',
		url: path + 'configNF/teste',
		dataType: 'json',
		success: function(e){
			$('#testar').removeClass('spinner')
			
			console.log(e)
			
			swal("Sucesso", 'Ambiente ok', "success")
			.then((v) => {
				alert(e)
			})


		}, error: function(e){
			if(e.status == 200){
				$('#testar').removeClass('spinner')
				swal("Sucesso", 'Ambiente ok', "success")
				.then((v) => {
					alert(e.responseText)
				})

			}else{
				$('#testar').removeClass('spinner')
				swal("Erro", 'Algo esta errado, verifique o console do navegador!', "warning")
				.then((v) => {
					alert(e.responseText)
				})

				console.log(e)
			}

		}
	});
})

$('#testarEmail').click(() => {

	$('#preloaderEmail').css('display', 'block')

	$.get(path + 'configNF/testeEmail')
	.done((success) => {
		$('#preloaderEmail').css('display', 'none')
		swal("Sucesso", 'Config de email OK', "success")
	}).fail((e) => {
		let err = e.responseJSON
		$('#preloaderEmail').css('display', 'none')
		console.log(err)

		swal("Erro", err, "error")
	})
})

function getUF(uf, call){

	let js = {
		'RO': '11',
		'AC': '12',
		'AM': '13',
		'RR': '14',
		'PA': '15',
		'AP': '16',
		'TO': '17',
		'MA': '21',
		'PI': '22',
		'CE': '23',
		'RN': '24',
		'PB': '25',
		'PE': '26',
		'AL': '27',
		'SE': '28',
		'BA': '29',
		'MG': '31',
		'ES': '32',
		'RJ': '33',
		'SP': '35',
		'PR': '41',
		'SC': '42',
		'RS': '43',
		'MS': '50',
		'MT': '51',
		'GO': '52',
		'DF': '53'
	};

	call(js[uf])
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

function consultaCNPJ(){

	let cnpj = $('#cnpj').val();

	cnpj = cnpj.replace(/[^0-9]/g,'')

	if(cnpj.length != 14){
		swal("Erro", "CNPJ inválido", "error")
	}else{
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

				$('#tipo').val('j').change()

				$('#cnpj2').val($('#cnpj').val())
				$('#ie').val(ie)
				$('#razao_social').val(data.razao_social)
				$('#nome_fantasia').val(data.estabelecimento.nome_fantasia)
				$("#logradouro").val(data.estabelecimento.tipo_logradouro + " " + data.estabelecimento.logradouro)
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
			swal("Erro", "Erro na consulta", "error")
		})
	}
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