@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<form class="card-body" method="post" action="/ordemServico/store_venda">
		<input type="hidden" name="ordem_id" value="{{ $ordem->id }}">
		@csrf
		<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
			<h4>Cliente: <strong>{{ $ordem->cliente->razao_social }} - {{ $ordem->cliente->cpf_cnpj }}</strong></h4>
			<h4>Valor total de produtos: <strong class="total-produtos">{{ moeda($ordem->produtos->sum('sub_total')) }} </strong></h4>

			<div class="row">
				<table class="table">
					<thead>
						<tr>
							<th>Produto</th>
							<th>Quantidade</th>
							<th>Valor unitário</th>
							<th>Subtotal</th>
						</tr>
					</thead>
					<tbody>
						@foreach($ordem->produtos as $p)
						<tr>
							<td>{{ $p->produto->nome }}</td>
							<td>{{ moeda($p->quantidade) }}</td>
							<td>{{ moeda($p->valor_unitario) }}</td>
							<td>{{ moeda($p->sub_total) }}</td>
						</tr>
						@endforeach
					</tbody>
				</table>
			</div>
			<hr>

			<h4 class="col-12">Fatura</h4>
			<div class="row">
				<table class="table table-dynamic">
					<thead>
						<tr>
							<th></th>
							<th>Valor da parcela</th>
							<th>Data de vencimento</th>
							<th>Forma de pagamento</th>
						</tr>
					</thead>

					<tbody>
						<tr class="dynamic-form">
							<td>
								<button type="button" class="btn btn-sm btn-danger btn-line-delete">
									<i class="la la-trash"></i>
								</button>
							</td>
							<td>
								<input required name="valor_parcela[]" placeholder="Valor da parcela" type="tel" class="form-control money valor_parcela" data-mask="000000,00" data-mask-reverse="true">
							</td>
							<td>
								<input required name="vencimento_parcela[]" placeholder="Vencimento da parcela" type="date" class="form-control">
							</td>
							<td>
								<select required class="custom-select" name="forma_pagamento_parcela[]">
									@foreach(App\Models\OrdemServico::tiposPagamento() as $key => $tp)
									<option value="{{$tp}}">{{$tp}}</option>
									@endforeach
								</select>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="row">
				<button type="button" class="btn btn-info btn-clone-tbl">
					<i class="la la-plus"></i> Adicionar parcela
				</button>
			</div>
			<hr>
			<div class="row">
				<div class="form-group col-lg-3 col-md-4 col-sm-6">
					<label class="col-form-label">Natureza de Operação</label>
					<div class="input-group date">
						<select class="custom-select form-control" name="natureza_id">
							@foreach($naturezas as $n)
							<option 
							@if($config->nat_op_padrao == $n->id)
							selected
							@endif
							value="{{$n->id}}">{{$n->natureza}}</option>
							@endforeach
						</select>
					</div>
				</div>

				<div class="form-group col-lg-6 col-12">
					<label class="col-form-label">Observação</label>
					<div class="input-group">
						<input class="form-control" type="text" name="observacao" value="{{ $ordem->observacao }}">
					</div>
				</div>
				
			</div>
		</div>
		<div class="col-12">
			<button type="submit" disabled class="btn btn-lg btn-success float-right btn-save">Gerar Venda</button>
		</div>
	</form>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
	$('.btn-clone-tbl').on("click", function() {
		console.clear()
		var $elem = $(this)
		.closest(".row")
		.prev()
		.find(".table-dynamic");

		console.log($elem)

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
		$("tbody .custom-select-prod").select2("destroy");
		var $tr = $elem.find(".dynamic-form").first();
		var $clone = $tr.clone();

		$clone.show();
		$clone.find("input,select").val("");

		$elem.append($clone);

	})

	$('body').on('blur', '.valor_parcela', function() {
		validateButtonSave()
	})


	function convertMoedaToFloat(value) {
		if (!value) {
			return 0;
		}

		var number_without_mask = value.replaceAll(".", "").replaceAll(",", ".");
		return parseFloat(number_without_mask.replace(/[^0-9\.]+/g, ""));
	}

	function convertFloatToMoeda(value) {
		value = parseFloat(value)
		return value.toLocaleString("pt-BR", {
			minimumFractionDigits: casas_decimais,
			maximumFractionDigits: casas_decimais
		});
	}

	function validateButtonSave(){
		$('.btn-save').attr("disabled", true)
		let total_produtos = convertMoedaToFloat($('.total-produtos').text())
		let total_parcela = 0
		$(".valor_parcela").each(function () {
			total_parcela += convertMoedaToFloat($(this).val())
		})

		if(total_parcela > total_produtos){
			swal(
				"Atenção",
				"Soma das parcelas ultrapassa o valor de produtos!",
				"warning"
				);

			$(".valor_parcela").each(function () {
			})
			return;
		}

		if(total_parcela == total_produtos){
			setTimeout(() => {
				$('.btn-save').removeAttr("disabled")
			}, 10)
		}
	}

	$(document).delegate(".btn-line-delete", "click", function(e) {
		e.preventDefault();
		swal({
			title: "Você esta certo?",
			text: "Deseja remover esse item mesmo?",
			icon: "warning",
			buttons: true
		}).then(willDelete => {
			if (willDelete) {
				var trLength = $(this)
				.closest("tr")
				.closest("tbody")
				.find("tr")
				.not(".dynamic-form-document").length;
				if (!trLength || trLength > 1) {
					$(this)
					.closest("tr")
					.remove();
					calcParcelas()
				} else {
					swal(
						"Atenção",
						"Você deve ter ao menos um item na lista",
						"warning"
						);
				}
			}
		});
	});
</script>
@endsection




