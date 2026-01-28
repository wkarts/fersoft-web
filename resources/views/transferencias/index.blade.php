@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<input type="hidden" id="pass" value="{{ $config->senha_remover ?? '' }}">
	<div class="card-body @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
		<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
			<div class="card card-custom gutter-b example example-compact">
				<div class="card-body">

					<div class="col-xl-12">
						<form class="row" method="post" action="/transferencia/store">
							@csrf

							{!! __view_locais_select_transfencia("Origem", 'saida') !!}

							{!! __view_locais_select_transfencia("Destino", 'entrada') !!}

							
							<div class="form-group validated col-12 col-lg-8">
								<label class="col-form-label" id="lbl_i_rg">Observação</label>
								<div class="">
									<input type="text" class="form-control" name="observacao">
								</div>
							</div>

							<div class="col-xl-12">
								<div class="table-responsive">
									<table class="table table-dynamic">
										<thead>
											<tr>
												<th>Produto</th>
												<th>Quantidade</th>
											</tr>
										</thead>
										<tbody>
											<tr class="dynamic-form">
												<td>
													<select required class="form-control custom-select-prod" style="width: 100%" id="kt_select2_1" name="produto[]">
														<option value="">Digite para buscar o produto</option>
													</select>
												</td>
												<td>
													<input required placeholder="Quantidade" type="text" class="form-control quantidade" name="quantidade[]">
												</td>
											</tr>
										</tbody>
									</table>
								</div>
								<div class="row col-12">
									<button type="button" class="btn btn-info btn-clone-tbl ml-3">
										<i class="la la-plus"></i> Adicionar produto
									</button>
								</div>
							</div>

							<div class="col-md-12 col-12">
								<button class="btn btn-success float-right">Salvar transferência</button>
							</div>
						</form>
					</div>

				</div>

			</div>
			<a class="btn btn-dark" href="/transferencia/list">
				<i class="la la-refresh"></i>
				Ver histórico de transferências
			</a>

		</div>
	</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">

	$(function(){
		setTimeout(() => {
			$(".custom-select-prod").select2({
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
						var query = {
							pesquisa: params.term,
							filial_id: $('#saida').val(),
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
							+ (v.referencia != "" ? " - ref: " + v.referencia : "")
							+ " - estoque: " + v.estoqueAtual;
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

		}, 200);
	});

	$('.btn-clone-tbl').on("click", function() {
		console.clear()
		var $elem = $(this)
		.closest(".row")
		.prev()
		.find(".table-dynamic");

		var hasEmpty = false;

		$elem.find("input, select").each(function() {
			if (($(this).val() == "" || $(this).val() == null) && $(this).attr("type") != "hidden" && $(this).attr("type") != "file" && !$(this).hasClass("ignore")) {
				hasEmpty = true;
			}
		});

		if (hasEmpty) {
			swal(
				"Atenção",
				"Preencha todos os campos antes de adicionar novos.",
				"warning"
				);
			return;
		}

		try{
			$("tbody .custom-select-prod").select2("destroy");
		}catch{

		}
		var $tr = $elem.find(".dynamic-form").first();
		var $clone = $tr.clone();

		$clone.show();
		$clone.find("input,select").val("");

		$elem.append($clone);

		setTimeout(() => {
			$("tbody .custom-select-prod").select2({
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

						var query = {
							pesquisa: params.term,
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
							+ (v.referencia != "" ? " - ref: " + v.referencia : "")
							+ " - estoque: " + v.estoqueAtual;
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

		}, 200);
	})
</script>
@endsection
