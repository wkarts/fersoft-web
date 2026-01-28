function dadosImportacao(id){
	$('#modal-importacao').modal('show')
	$.get(path + 'compras/item-compra', {id: id})
	.done((res) => {
		console.log(res)
		$('.produto_nome').text(res.produto.nome)
		$('#item_id').val(id)
		$('#nDI').val(res.nDI)
		$('#dDI').val(res.dDI)
		$('#cidade_desembarque_id').val(res.cidade_desembarque_id).change()
		$('#dDesemb').val(res.dDesemb)
		$('#tpViaTransp').val(res.tpViaTransp).change()
		$('#tpIntermedio').val(res.tpIntermedio).change()
		$('#vAFRMM').val(res.vAFRMM)
		$('#documento').val(res.documento)
		$('#UFTerceiro').val(res.UFTerceiro)
		$('#cExportador').val(res.cExportador)
		$('#nAdicao').val(res.nAdicao)
		$('#cFabricante').val(res.cFabricante)
		
	})	
	.fail((err) => {
		console.log(err)
	})
}