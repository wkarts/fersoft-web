$(function () {

})


function encerrar(){
	
	let docs = []
	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			chave = $(this).find('#chave').html();
			protocolo = $(this).find('#protocolo').html();
			local = $(this).find('#local').html().trim();
			docs.push({
				chave: chave,
				protocolo: protocolo,
				local: local
			})
			
		}
	})

	if(docs.length > 0){
		$('#btn-encerrar').addClass('spinner');
		$('#btn-encerrar').addClass('disabled');

		$.post(path + 'mdfeSefaz/encerrar', {data: docs, _token: $('#token').val()})
		.done((success) => {
			console.log(success)
			$('#btn-encerrar').removeClass('spinner');
			$('#btn-encerrar').removeClass('disabled');
			swal("Sucesso", "Documento(s) encerrados!", "success")
			.then(() => {
				location.href = path + "mdfe"
			})

		})
		.fail((err) => {
			console.log(err)
			swal("Erro", err.responseJSON, "error")
			$('#btn-encerrar').removeClass('spinner');
			$('#btn-encerrar').removeClass('disabled');
		})
	}
}