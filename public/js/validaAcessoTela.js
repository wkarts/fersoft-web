var senha = null
$(function(){
	$.get(path+'configNF/verifica-senha-acesso')
	.done((success) => {
		console.log(success)
		validaSenha()
	})
	.fail((err) => {
		$('.view-acesso').removeClass('d-none')
	})
})

function validaSenha(){
	swal({
		title: 'Acesso',
		text: 'Informe a senha!',
		content: {
			element: "input",
			attributes: {
				placeholder: "Digite a senha",
				type: "password",
			},
		},
		button: {
			text: "Acessar!",
			closeModal: false,
			type: 'error'
		},
		confirmButtonColor: "#DD6B55",
	}).then(v => {
		if(v.length > 0){
			$.get(path+'configNF/verificaSenha', {senha: v})
			.done((success) => {
				swal.close()
				$('.view-acesso').removeClass('d-none')
			})
			.fail((err) => {
				swal("Erro", "Senha incorreta", "error")
				.then(() => {
					history.back();
				})
			})
		}else{
			location.reload()
		}
	})
}

