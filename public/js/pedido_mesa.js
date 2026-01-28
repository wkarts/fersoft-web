$(function(){
	// console.clear()
	setInterval(() => {
		naoAutorizados()
	}, 12000)
})

function naoAutorizados(){
	if($('#modal-pedido-mesa').is(':visible')){

	}else{

		$.get(path + 'pedidosMesa/naoAutorizados')
		.done((success) => {

			if(success){
				
				$('#estado_mesa').val('')
				if(success.substring(0, 20) == '<div class="col-12">'){

					$('#modal-pedido-mesa').modal('show')

					$('#modal-pedido-mesa .modal-body').html(success)

					var audio = new Audio('/audio/delivery_1.mp3');
					audio.addEventListener('canplaythrough', function() {
						audio.play();
					});
				}
				
			}
		}).fail((err) => {
			console.log("err", err)
		})
	}
}

$('#btn-confirmar-pedido-mesa').click(() => {

	swal({
		buttons: ["Cancelar", "Confirmar"],
		title: "Deseja confirmar este pedido?",
		icon: "success"
	}).then((x) => {
		if(x){
			$('#estado_mesa').val("aberto")
			$('#form-pedido-mesa').submit()
		}
	})
})

$('#btn-cancelar-pedido-mesa').click(() => {
	swal({
		buttons: ["Cancelar", "Recusar"],
		title: "Deseja recusar este pedido?",
		icon: "error"
	}).then((x) => {
		if(x){
			$('#estado_mesa').val("recusado")
			$('#form-pedido-mesa').submit()
		}
	})
})