var intervalVar = null
$(function(){
	// console.clear()

	intervalVar = setInterval(() => {
		getAlertas()
	}, 5000)
})

function getAlertas(){

	if($('#modal-alerta-super').is(':visible')){

	}else{

		$.get(path + 'super-admin/alertas')
		.done((success) => {

			if(success){

				$('#modal-alerta-super').modal('show')

				$('#modal-alerta-super .modal-body').html(success)

				var audio = new Audio('/audio/delivery_1.mp3');
				audio.addEventListener('canplaythrough', function() {
					audio.play();
				});
				
			}
		}).fail((err) => {
			// console.log("err", err)
		})
	}
}