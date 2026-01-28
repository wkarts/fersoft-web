$(function () {

	$('#modal-loading').modal('show')
	atualizaIbpt();
});

function atualizaIbpt(){
	console.clear()
	$.get(path + 'ibpt/atualizaApi')
	.done((success) => {
		swal("Sucesso", success, "success");
		$('#modal-loading').modal('hide')

	})
	.fail((err) => {
		err.responseJSON
		swal("Erro", "Algo deu errado ao atualizar tabela ibpt: " + err.responseJSON, "warning");
		$('#modal-loading').modal('hide')

	})
}