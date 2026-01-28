var menu = [];
$(function () {
	if($('#menus').val()){
		menu = JSON.parse($('#menus').val())
		validaCategoriaCompleta()
	}

});
function marcarTudo(titulo){
	titulo = titulo.replace(" ", "_")
	let marked = $('#todos_'+titulo).is(':checked')
	if(!marked){
		acaoCheck(false, titulo)
	}else{
		acaoCheck(true, titulo)
	}
}

function acaoCheck(acao, titulo){
	console.clear()
	menu.map((m) => {
		let t = m.titulo.replace(" ", "_")
		if(titulo == t){
			m.subs.map((sub) => {
				let rt = sub.rota.replaceAll("/", "")
				rt = rt.replaceAll(".", "_")
				rt = rt.replaceAll(":", "_")
				if(acao){
					$('#sub_'+rt).attr('checked', true);
				}else{
					$('#sub_'+rt).removeAttr('checked');
				}
			})
		}
	})
}

function validaCategoriaCompleta(){
	let temp = true;

	menu.map((m) => {
		temp = true;
		m.subs.map((sub) => {
			let rt = sub.rota.replaceAll("/", "")
			rt = rt.replaceAll(".", "_")
			rt = rt.replaceAll(":", "_")

			let marked = $('#sub_'+rt).is(':checked')
			if(!marked && sub.nome != "NFS-e") temp = false;
		})
		let t = m.titulo.replace(" ", "_")
		if(temp){
			$('#todos_'+t).attr('checked', true);
		}else{
			$('#todos_'+t).removeAttr('checked')
		}
	});
}

$('.check-sub').click(() => {
	validaCategoriaCompleta()
})

$('#perfil-select').change(() => {
	desmarcarTudo((cl) => {
		let perfil = $('#perfil-select').val();
		if(perfil != '0'){
			perfil = JSON.parse(perfil)
			let permissao = JSON.parse(perfil.permissao)
			permissao.map((p) => {
				menu.map((m) => {
					m.subs.map((sub) => {
						// console.log(p)
						if(sub.rota == p){
							let rt = sub.rota.replaceAll("/", "")
							rt = rt.replaceAll(".", "_")
							rt = rt.replaceAll(":", "_")
							$('#sub_'+rt).attr('checked', true);
						}

						if(p.length > 60){
							let tr = sub.rota.replaceAll(".", "_")


							if(tr == p){

								let rt = sub.rota.replaceAll("/", "")
								rt = rt.replaceAll(".", "_")
								rt = rt.replaceAll(":", "_")
								$('#sub_'+rt).attr('checked', true);
							}

						}
					})
				})
			})

			validaCategoriaCompleta();
		}
	})

})

function desmarcarTudo(call){
	console.clear();
	menu.map((m) => {
		let t = m.titulo.replace(" ", "_")

		$('#todos'+t).removeAttr('checked');
		m.subs.map((sub) => {
			let rt = sub.rota.replaceAll("/", "")
			rt = rt.replaceAll(".", "_")
			rt = rt.replaceAll(":", "_")
			// $('#sub_'+rt).attr('checked', false);
			$('#sub_'+rt).removeAttr('checked');
		})
	})
	call(true)
}

$('#consulta').click(() => {
	$('#consulta').addClass('spinner');
	let cnpj = $('#cnpj').val();

	cnpj = cnpj.replace(/[^0-9]/g,'')

	if(cnpj.length == 14){

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
				}else{
					console.log(data)

					$('#nome').val(data.nome)
					$('#nome_fantasia').val(data.fantasia)
					$('#rua').val(data.logradouro)
					$('#numero').val(data.numero)
					$('#bairro').val(data.bairro)
					$('#email').val(data.email)
					let fone = data.telefone.replace("(", "").replace(")", "").replace("/", "")
					fone = fone.substring(0, 13)
					$('#telefone').val(fone)
					$('#cidade').val(data.municipio)
					$('#email').val(data.email)
					$('#uf2').val(data.uf).change()

					let cep = data.cep;
					$('#cep').val(cep.replace(".", ""))

				}

			}, 
			error: function(e) { 
				$('#consulta').removeClass('spinner');
				console.log(e)
				swal("Alerta", "Nenhum retorno encontrado para este CNPJ, informe manualmente por gentileza", "warning")

			},
		});
	}else{
		swal("Alerta", "Informe corretamente o CNPJ", "warning")
	}
})
