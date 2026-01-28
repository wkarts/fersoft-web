$(function(){

	setTimeout(() => {
		$("#kt_select2_3").select2({
			minimumInputLength: 2,
			language: "pt-BR",
			placeholder: "Digite para buscar o produto",
			width: "100%",
			ajax: {
				cache: true,
				url: path + 'produtos/autocomplete',
				dataType: "json",
				data: function(params) {
					console.clear()
					let filial = $('#filial').val()
					console.log("filial", filial)
					var query = {
						pesquisa: params.term,
						filial_id: filial
					};
					return query;
				},
				processResults: function(response) {
					console.log("response", response)
					var results = [];

					$.each(response, function(i, v) {
						var o = {};
						o.id = v.id;


						o.text = v.nome + (v.grade ? " "+v.str_grade : "") + " | R$ " + parseFloat(v.valor_venda).toFixed(2).replace(".", ",")
						+ (v.referencia != "" ? " - Ref: " + v.referencia: "") + (parseFloat(v.estoqueAtual) > 0 ? " | Estoque: " + v.estoqueAtual : "");
						o.value = v.id;
						results.push(o);
					});
					return {
						results: results
					};
				}
			}
		});

		$('.select2-selection__arrow').addClass('select2-selection__arroww')
		$('.select2-selection__arrow').removeClass('select2-selection__arrow')
	}, 100);
})

$("#kt_select2_3").change(() => {

	let id = $("#kt_select2_3").val()
	if(id){
		$.get(path + 'produtos/autocompleteProduto', {id: id, lista_id: null})
		.done((res) => {

			$('#valor_prod').val(parseFloat(res.valor_venda).toFixed(casas_decimais).replace(".", ","))
			$('.qtd').val('1')
		})
		.fail((err) => {
			console.log(err)
			swal("Erro", "Erro ao encontrar produto", "error")
		})
	}
})

$("#kt_select2_1").change(function() {
	let opt = $(this).find(":selected")
	let vl = $(this).closest('div').next().find('input')
	let qtd = $(this).closest('div').next().next().find('input')
	let v = opt.data('value')+""
	vl.val(v.replace(".", ","))
	qtd.val(1)
})


