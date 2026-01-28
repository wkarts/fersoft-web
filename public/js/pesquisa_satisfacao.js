var forcar_pesquisa = false
$(function () {

	let pesquisaId = $('#pesquisa_id').val()
	forcar_pesquisa = $('#forcar_pesquisa').val()
	if(pesquisaId){
		$.get(path + 'pesquisa/find/'+pesquisaId)
		.done((success) => {
			console.log(success)

			$('.p-titulo').html(success.titulo)
			$('.p-texto').html(success.texto)
			$('#modal-pesquisa').modal('show')

		})
		.fail((err) => {
			console.log(err)
		})
	}
});

jQuery(document).keyup(function(ev){
	if(ev.keyCode == 27) {
		fecharPesquisa()
	}
});

function fecharPesquisa(){

	if(forcar_pesquisa){
		swal("Alerta", "Por gentileza responda a pesquisa para continuar utilizando o sistema!", "warning")
		.then(() => {
			$('#modal-pesquisa').modal('show')
		})
	}
}

var pesquisaClick = false
var NOTA = 0
$('.ico-pesquisa').hover((v) => {
	if(!pesquisaClick){
		let nota = v.target.id
		for(let i=0; i <= nota; i++){
			$('.ico-'+i).addClass('ico-active')
		}
	}
}, () => {
	if(!pesquisaClick){
		$('.ico-pesquisa').removeClass('ico-active')
	}
})

$('.ico-pesquisa').click((v) => {
	NOTA = v.target.id
	pesquisaClick = true
})

$('#btn-salvar-pesquisa').click(() => {

	let pesquisaId = $('#pesquisa_id').val()

	if(NOTA == 0){
		swal("Alerta", "Informe a sua avaliação, selecionando a estrela correspondente!", "warning")

	}else{
	$('#btn-salvar-pesquisa').attr('disabled', 'disabled')
			
		let js = {
			nota: NOTA,
			id: pesquisaId,
			resposta: $('#resposta').val()
		}
		console.log(js)

		$.get(path + 'pesquisa/salvarNota', js)
		.done((success) => {
			console.log(success)
			swal("Tudo certo", "Obrigado por responder nossa pesquisa", "success")
			.then(() => {
				$('#modal-pesquisa').modal('hide')
			})
		})
		.fail((err) => {
			console.log(err)
		})
	}
})

