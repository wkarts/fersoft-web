var menu = [];
$(function () {
	menu = JSON.parse($('#menus').val())
	validaCategoriaCompleta()
});
function marcarTudo(titulo){
	titulo = titulo.replace(" ", "_")
	let marked = $('#todos_'+titulo).is(':checked')
	if(!marked){
		acaoCheck(false, titulo)
	}else{
		acaoCheck(true, titulo)
	}
}

function acaoCheck(acao, titulo){
	menu.map((m) => {
		let t = m.titulo.replace(" ", "_")

		if(titulo == t){
			m.subs.map((sub) => {
				let rt = sub.rota.replaceAll("/", "")

				if(acao){
					$('#sub_'+rt).attr('checked', true);
				}else{
					$('#sub_'+rt).removeAttr('checked');
				}
			})
		}
	})
}

function validaCategoriaCompleta(){
	let temp = true;
	menu.map((m) => {
		temp = true;
		m.subs.map((sub) => {
			let rt = sub.rota.replaceAll("/", "")
			let marked = $('#sub_'+rt).is(':checked')
			if(!marked) temp = false;

		})
		let titulo = m.titulo.replace(" ", "_")

		if(temp){
			$('#todos_'+titulo).prop('checked', true);
		}else{

			$('#todos_'+titulo).prop('checked',false)
		}
	});
}

$('#adm').click(() =>{

	if($('#adm').is(':checked')){

		marcarTodos(true);
	}else{
		desmarcarTodos();
	}
})

function marcarTodos(){
	menu.map((m) => {
		temp = true;
		m.subs.map((sub) => {
			let rt = sub.rota.replaceAll("/", "")
			$('#sub_'+rt).attr('checked', true);
		});
	});
	setTimeout(() => {
		validaCategoriaCompleta()
	}, 200)
}

function desmarcarTodos(){
	menu.map((m) => {
		temp = true;
		m.subs.map((sub) => {
			let rt = sub.rota.replaceAll("/", "")
			$('#sub_'+rt).removeAttr('checked');
		});
	});
	setTimeout(() => {
		validaCategoriaCompleta()
	}, 200)
}

$('.check-sub').click(() => {
	validaCategoriaCompleta()
})


