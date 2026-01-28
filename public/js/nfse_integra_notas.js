var EMITINDO = false;

$(function(){
	checkSelect()
})

$('.check').click(() => {
	checkSelect()
})

function transmitir(id){
	$('.modal-loading').modal('show')
	let token = $('#_token').val();
	$.ajax
	({
		type: 'POST',
		data: {
			id: id,
			_token: token
		},
		url: path + 'nfse/enviar-integra-notas',
		dataType: 'json',
		success: function(e){
			$('.modal-loading').modal('hide')
			console.log(e)
			EMITINDO = false;
			$('#btn-enviar').removeClass('spinner');
			$('#btn-enviar').removeClass('disabled');

			swal("Sucesso", e.mensagem, "success")
			.then(() => {
				if(e.link_pdf){
					window.open(e.link_pdf)
				}
				setTimeout(() => {
					location.reload()
				}, 100)
			})

		}, error: function(e){
			$('.modal-loading').modal('hide')
			$('#btn-enviar').removeClass('spinner');
			$('#btn-enviar').removeClass('disabled');

			EMITINDO = false;
			console.log(e)

			if(e.status == 404){
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
			}else{
				swal("Algo deu errado", e.responseJSON, "error")
			}
		}
	});
}

function checkSelect(){
	let cont = 0
	$('.check').each(function(e){
		if($(this).is(':checked')){
			cont++
		}
	})

	setTimeout(() => {

		if(cont > 1){
			$('.btn').attr('disabled', 1)
		}else{
			$('.btn').removeAttr('disabled')
		}
	}, 100)
}

function enviar(){
	console.clear()

	if(!EMITINDO){
		EMITINDO = true;
		$('#btn-enviar').addClass('spinner');
		$('#btn-enviar').addClass('disabled');
		let id = 0
		$('#body tr').each(function(){
			if($(this).find('#checkbox input').is(':checked'))
				id = $(this).find('#id').html();
		})

		let token = $('#_token').val();
		$.ajax
		({
			type: 'POST',
			data: {
				id: id,
				_token: token
			},
			url: path + 'nfse/enviar-integra-notas',
			dataType: 'json',
			success: function(e){
				console.log(e)
				EMITINDO = false;
				$('#btn-enviar').removeClass('spinner');
				$('#btn-enviar').removeClass('disabled');

				swal("Sucesso", e.mensagem, "success")
				.then(() => {
					if(e.link_pdf){
						window.open(e.link_pdf)
					}
					setTimeout(() => {
						location.reload()
					}, 100)
				})

			}, error: function(e){
				$('#btn-enviar').removeClass('spinner');
				$('#btn-enviar').removeClass('disabled');

				EMITINDO = false;
				console.log(e)

				if(e.status == 404){
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
				}else{
					swal("Algo deu errado", e.responseJSON, "error")
					// location.reload()
					
				}

			}
		});
	}
	
}

function consultar(){
	console.clear()
	let id = 0;
	let cont = 0;
	$('#btn-consultar').addClass('spinner')
	$('#btn-consultar').addClass('disabled')
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			id = $(this).find('#id').html();
			cont++;
		}
	})

	if(cont > 1){
		swal("Erro", "Selecione apenas um documento para consultar!", "error")

	}else{
		let token = $('#_token').val();
		$.ajax
		({
			type: 'POST',
			data: {
				id: id,
				_token: token
			},
			url: path + 'nfse/consultar-integra-notas',
			dataType: 'json',
			success: function(e){

				console.log(e)
				if(e.codigo == 5023){
					swal("Sucesso", e.mensagem, "success")
				}else{
					swal("Sucesso", e.mensagem, "success").then(() => {
						window.open(path+"nfse/imprimir/"+id)
						location.reload()
					})
				}

				$('#btn-consultar').removeClass('spinner')
				$('#btn-consultar').removeClass('disabled')
				
			}, error: function(e){
				$('#btn-consultar').removeClass('spinner')
				$('#btn-consultar').removeClass('disabled')
				console.log(e)
				try{
					swal("Erro", e.responseJSON.mensagem, "error").then(() => {
						location.reload()
					})
				}catch{
					swal("Erro", e.responseJSON, "error")
				}
				
			}
		});
	}
}

function imprimir(){
	let id = 0;
	let cont = 0;
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			id = $(this).find('#id').html();
			cont++
		}
	})
	if(cont == 0){
		swal("Erro", "Selecione um documento para impressão!", "error")
		return;
	}
	if(cont > 1){
		swal("Erro", "Selecione apenas um documento para impressão!", "error")

	}else{
		window.open(path+"nfse/imprimir/"+id, "_blank");
	}
}

function cancelar(){
	console.clear()
	$('#btn-cancelar-2').addClass('spinner')
	$('#btn-cancelar-2').addClass('disabled')
	let id = 0;
	let cont = 0;
	let motivo = $('#motivo').val();
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			id = $(this).find('#id').html();
			cont++;
		}
	})

	if(cont > 1){
		swal("Erro", 'Selecione apenas um documento para cancelar!', "error")
	}else{
		let token = $('#_token').val();
		$.ajax
		({
			type: 'POST',
			data: {
				id: id,
				motivo: motivo,
				_token: token
			},
			url: path + 'nfse/cancelar-integra-notas',
			dataType: 'json',
			success: function(e){
				$('#btn-cancelar-2').removeClass('spinner')
				$('#btn-cancelar-2').removeClass('disabled')
				console.log(e)
				swal("Sucesso", e.mensagem, "success")
				
			}, error: function(e){
				console.log(e)
				$('#btn-cancelar-2').removeClass('spinner')
				$('#btn-cancelar-2').removeClass('disabled')
				swal("Erro", e.responseJSON.mensagem, "error")
			}
		});
	}
}

