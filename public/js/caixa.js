

function abrirCaixa(){
	let token = $('#_token').val();
	let valor = $('#valor').val();
	let filial_id = $('#filial_id') ? $('#filial_id').val() : null;
	if(filial_id == -1){
		filial_id = null
	}
	let conta_id = null
	if($('#conta_id').length){
		conta_id = $('#conta_id').val()
		if(!conta_id){
			swal("Alerta", "Selecione uma conta", "warning")
			return
		}
	}
	valor = valor.length >= 0 ? valor.replace(",", ".") : 0;
	if(parseFloat(valor) >= 0){
		$.ajax
		({
			type: 'POST',
			url: path + 'aberturaCaixa/abrir',
			dataType: 'json',
			data: {
				valor: $('#valor').val(),
				_token: token,
				filial_id: filial_id,
				conta_id: conta_id
			},
			success: function(e){
				caixaAberto = true;
				$('#modal1').modal('hide');
				swal("Sucesso", "Caixa aberto", "success").then(() => {
					location.reload()
				})

			}, error: function(e){
				$('#modal1').modal('hide');
				swal("Erro", "Erro ao abrir caixa", "error")
				console.log(e)
			}

		});
	}else{
		// alert('Insira um valor válido')
		swal("Erro", 'Insira um valor válido', "warning")

	}

}

function suprimentoCaixa(){
	let token = $('#_token').val();
	let conta_id = null
	if($('#conta_suprimento_id').length){
		conta_id = $('#conta_suprimento_id').val()
		if(!conta_id){
			swal("Alerta", "Selecione uma conta", "warning")
			return
		}
	}
	$.ajax
	({
		type: 'POST',
		url: path + 'suprimentoCaixa/save',
		dataType: 'json',
		data: {
			valor: $('#valor_suprimento').val(),
			obs: $('#obs_suprimento').val(),
			tipo: $('#tipo_suprimento').val(),
			_token: token,
			conta_id: conta_id
		},
		success: function(e){

			$('#modal-supri').modal('hide');
			$('#valor_suprimento').val('');
			$('#obs_suprimento').val('');
			swal("Sucesso", "suprimento realizado!", "success")
			.then(() => {
				window.open(path+'suprimentoCaixa/imprimir/'+e.id)
				location.reload()
			})

		}, error: function(e){
			$('#valor_suprimento').val('')
			$('#obs_suprimento').val('')
			swal("Erro", "Erro ao realizar suprimento de caixa!", "error")

		}

	});
}

function sangriaCaixa(){
	let token = $('#_token').val();
	let conta_id = null
	if($('#conta_sagria_id').length){
		conta_id = $('#conta_sagria_id').val()
		if(!conta_id){
			swal("Alerta", "Selecione uma conta", "warning")
			return
		}
	}

	$.ajax
	({
		type: 'POST',
		url: path + 'sangriaCaixa/save',
		dataType: 'json',
		data: {
			valor: $('#valor_sangria').val(),
			observacao: $('#obs_sangria').val(),
			_token: token,
			conta_id: conta_id
		},
		success: function(e){

			caixaAberto = true;
			$('#modal-sangria').modal('hide');
			$('#valor_sangria').val('');
			$('#obs_sangria').val('');
			swal("Sucesso", "Sangria realizada!", "success")
			.then(() => {
				window.open(path+'sangriaCaixa/imprimir/'+e.id)
				location.reload();
			})


		}, error: function(e){
			$('#valor_sangria').val('');
			$('#obs_sangria').val('');
			try{
				swal("Erro", e.responseJSON, "error")
				.then(() => {
					$('#modal-sangria').modal('hide');
				})
			}catch{
				swal("Erro", "Erro ao realizar sangria!", "error")
				.then(() => {
					$('#modal-sangria').modal('hide');
				})
			}

		}

	});
}

