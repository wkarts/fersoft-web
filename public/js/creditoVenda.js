
function receber(){
	let arr = []
	$('#body tr').each(function(){
		let checked = $(this).find('input').is(':checked');
		if(checked){
			let id = $(this).find('#id').html();
			arr.push(id)
		}
	});

	let param = { 
		arr: arr 
	};
	location.href=path+'vendasEmCredito/receber?arr='+arr;

}

$('.check').click(() => {
	$('#btn-receber').removeClass('disabled');
	percorreTabela();
})

function removerVenda(id){
	let senha = $('#pass').val()
	if(senha != ""){

		swal({
			title: 'Cancelamento de venda',
			text: 'Informe a senha!',
			content: {
				element: "input",
				attributes: {
					placeholder: "Digite a senha",
					type: "password",
				},
			},
			button: {
				text: "Cancelar!",
				closeModal: false,
				type: 'error'
			},
			confirmButtonColor: "#DD6B55",
		}).then(v => {
			if(v.length > 0){
				$.get(path+'configNF/verificaSenha', {senha: v})
				.then(
					res => {
						location.href="/vendasEmCredito/delete/"+id;
					},
					err => {
						swal("Erro", "Senha incorreta", "error")
						.then(() => {
							location.reload()
						});
					}
					)
			}else{
				location.reload()
			}
		})
	}else{
		location.href="/vendasEmCredito/delete/"+id;
	}
}

function formatReal(v){
	return v.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'});;
}

function percorreTabela(){
	let temp = 0;

	$('#body tr').each(function(){
		if($(this).find('#checkbox input').is(':checked')){
			let v = $(this).find('#valor').html();
			v = v.replace(",", ".")
			temp += parseFloat(v);
		}
	});
	$('#total-select').html(formatReal(temp))


}
