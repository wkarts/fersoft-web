$(function(){
	// console.clear()
	setInterval(() => {
		pedidosNaoLidos()
	}, 10000)
})

function pedidosNaoLidos(){
	if($('#modal-pedido-delivery').is(':visible')){

	}else{
		$.get(path + 'pedidosDelivery/pedidosNaoLidos')
		.done((success) => {

			if(success){
				// console.log(success)
				
				$('#estado').val('')
				if(success.substring(0, 20) == '<div class="col-12">'){

					$('#modal-pedido-delivery').modal('show')

					$('#modal-pedido-delivery .modal-body').html(success)

					var audio = new Audio('/audio/delivery_1.mp3');
					audio.addEventListener('canplaythrough', function() {
						audio.play();
					});
				}
				
			}
		}).fail((err) => {
			// console.log("err", err)
		})
	}
}

$('#btn-confirmar-pedido').click(() => {

	swal({
		buttons: ["Cancelar", "Confirmar"],
		title: "Deseja confirmar este pedido?",
		icon: "success"
	}).then((x) => {
		if(x){
			$('#estado').val("aprovado")
			$('#form-pedido-delivery').submit()
		}
	})
})

$('#btn-cancelar-pedido').click(() => {
	swal({
		buttons: ["Cancelar", "Recusar"],
		title: "Deseja recusar este pedido?",
		icon: "error"
	}).then((x) => {
		if(x){
			$('#estado').val("cancelado")
			$('#form-pedido-delivery').submit()
		}
	})
})