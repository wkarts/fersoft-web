
$(function(){
	produtosParaVencer()
})

function produtosParaVencer(){
	$(function(){

		$.get(path + 'compras/alerta-estoque')
		.done((res) => {
			$('#modal-alerta-estoque').modal('show')
			$('#modal-alerta-estoque .modal-body').html(res)
		})
		.fail((err) => {
			console.log(err)
		})
	})
}