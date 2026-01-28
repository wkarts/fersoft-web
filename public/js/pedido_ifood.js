

var intervalVar = null
$(function(){
	// console.clear()
	intervalVar = setInterval(() => {
		pedidosNaoLidosIfood()
	}, 10000)
})

function pedidosNaoLidosIfood(){

	if($('#modal-pedido-ifood').is(':visible')){

	}else{

		$.get(path + 'ifood/getNewOrdersAsync')
		.done((success) => {
			if(success){
				
				$('#estado').val('')
				if(success.substring(0, 20) == '<div class="col-12">'){

					$('#modal-pedido-ifood').modal('show')

					$('#modal-pedido-ifood .modal-body').html(success)

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

$('#btn-confirmar-pedido-ifood').click(() => {

	swal({
		buttons: ["Cancelar", "Sim"],
		title: "Deseja confirmar este pedido?",
		icon: "success"
	}).then((x) => {
		if(x){
			$('#status').val("CFM")
			$('#form-pedido-ifood').submit()
		}
	})
})

$('#btn-cancelar-pedido-ifood').click(() => {
	clearInterval(intervalVar)
	$('#modal-pedido-ifood').modal('hide')
	$('#modal-cancelar-pedido-ifood').modal('show')
	// swal({
	// 	buttons: ["Cancelar", "Sim"],
	// 	title: "Deseja cancelar este pedido?",
	// 	icon: "error",
	// 	content: "input",
	// 	text: 'Informe o motivo do cancelamento',

	// }).then((x) => {
	// 	if(x){
	// 		$('#status').val("CAN")
	// 		$('#motivo').val(x)
	// 		$('#form-pedido-ifood').submit()
	// 	}
	// })
})

function cancelarPedidoIfood(){
	let codigo = $('#codigo-ifood').val()
	let motivo = $('#motivo-ifood').val()
	$('#status').val("CAN")
	$('#motivo').val(motivo)
	$('#codigo').val(codigo)
	$('#form-pedido-ifood').submit()
}
