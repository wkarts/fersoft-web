
$('#salvar').click(() => {
	swal({
		title: "NFSe",
		text: "Deseja Enviar Nota Fiscal de Serviço?",
		icon: "info",
		buttons: ["Somente salvar", 'Sim'],
		dangerMode: true,
	}).then((v) => {
		if (v) {
			// salvar e tranmitir
			console.log("salvando...")
			let data = {}
			$("#form-servico input, #form-servico select, #form-servico textarea").each(function() {

				let indice = $(this).attr('id')
				if (indice) {
					data[indice] = $(this).val()
				}

			});
			console.log(data)

			$.post(path + 'nfse/storeAjax', {_token: $('#_token').val(), data: data})
			.done((success) => {
				console.log("success", success)
				swal("Sucesso", "NFSe salva!", "success")
				.then(() => {
					$('.modal-loading').css('display', 'block')
					transmitir(success.id)
				})

			}).fail((err) => {
				console.log(err)
				swal("Ops", "Algo deu errado ao salvar NFSe!", "error")
			})

		}else{
			$('#form-servico').submit()
		}
	})
})

function transmitir(id){
	let token = $('#_token').val();
	$.ajax
	({
		type: 'POST',
		data: {
			id: id,
			_token: token
		},
		url: path + url,
		dataType: 'json',
		success: function(e){
			console.log(e)
			EMITINDO = false;

			swal("Sucesso", e.mensagem, "success")
			.then(() => {
					// window.open(e.pdf_nfse)
					// setTimeout(() => {
					// 	location.reload()
					// }, 100)
				})
		}, error: function(e){

			EMITINDO = false;
			console.log(e)


			let json = e.responseJSON


			let motivo = json.mensagem
			let erros = json.erros

			if(erros){
				erros.map((e) => {
					motivo += e.erro
				})
			}

			let icon = "error"
			let title = "Algo deu errado"

			swal({
				title: title,
				text: motivo,
				icon: icon,
				buttons: ["Fechar"],
				dangerMode: true,
			})

		}
	})

}
