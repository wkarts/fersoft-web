
$(function(){
	validaVencimento()
})

function validaVencimento(){
	$(function(){

		$.get(path + 'compras/alerta-validade')
		.done((res) => {
			$('#modal-alerta-validade').modal('show')
			$('#modal-alerta-validade .modal-body').html(res)
		})
		.fail((err) => {
			console.log(err)
			validaEstoque()
		})
	})
}

function closeModalValidade(){
	setTimeout(() => {
		validaEstoque()
	}, 100)
}

function validaEstoque(){
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