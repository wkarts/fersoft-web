$(function(){

	$.get(path + 'compras/sem-validade')
	.done((res) => {
		$('#modal-validade').modal('show')
		console.log(res)
		$('#modal-validade .modal-body').html(res)
	})
	.fail((err) => {
		// console.log(err)
	})
})