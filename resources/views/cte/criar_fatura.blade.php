@extends('default.layout')
@section('content')
<style type="text/css">
	input:read-only {
		background-color: #ccc !important;
	}
</style>
<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="content d-flex flex-column flex-column-fluid" id="kt_content" >

			<div class="col-lg-12" id="content">
				<!--begin::Portlet-->

				<form class="row" method="post" action="/cte/salvarFatura">
					@csrf
					<div class="col-xl-12">

						<div class="kt-section kt-section--first">
							<div class="kt-section__body">

								<div class="row">
									<div class="col-lg-12 col-md-12 col-xl-12 col-12">
										<h4>Fatura CTe</h4>
										<h5>Total de documentos selecionados: <strong>{{ sizeof($data) }}</strong></h5>

										<div class="row">

											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label" id="">Número da fatura</label>
												<div class="">
													<input readonly required name="numero_fatura" value="{{ $ultimoNumeroFatura+1 }}" type="tel" id="" class="form-control" value="">

												</div>
											</div>
											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label" id="">Vencimento</label>
												<div class="">
													<input required name="vencimento" type="date" id="" class="form-control" value="">

												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-2">
												<label class="col-form-label" id="">Gerar conta receber</label>
												<select class="form-control form-select" name="gerar_conta_receber">
													<option value="0">Não</option>
													<option value="1">Sim</option>
												</select>
											</div>
										</div>

										<div class="row">
											<table class="table">
												<thead>
													<tr>
														<th>Remetente</th>
														<th>Valor da mercadoria</th>
														<th>Peso</th>
														<th>Frete</th>
														<th>Unidade</th>
														<th>Chave NFe</th>
														<th>Número da CTe</th>
													</tr>
												</thead>
												<tbody>
													@foreach($data as $item)
													<tr>
														<input type="hidden" value="{{$item->id}}" name="cte_id[]">
														<input type="hidden" value="{{$item->remetente_id}}" name="remetente_id[]">
														<td>
															<input required class="form-control" readonly type="text" name="remetente[]" value="{{ $item->remetente->razao_social }}">
														</td>
														<td>
															<input required class="form-control money valor_mercadoria" type="tel" name="valor_mercadoria[]" value="{{ moeda($item->valor_carga) }}">
														</td>
														<td>
															<input required class="form-control" type="tel" name="peso[]" value="{{ ($item->somaMedidas()) }}">
														</td>
														<td>
															<input required class="form-control money frete" type="tel" name="frete[]" value="{{ moeda($item->somaComponentes()) }}">
														</td>

														<td>
															<input class="form-control" type="text" name="unidade[]" value="">
														</td>

														<td>
															<input readonly class="form-control" type="text" name="chave_nfe[]" value="{{ $item->chave_nfe }}">
														</td>

														<td>
															<input readonly class="form-control" type="text" name="cte_numero[]" value="{{ $item->cte_numero }}">
														</td>
													</tr>
													@endforeach
												</tbody>
												
											</table>

										</div>

										<div class="row">

											<div class="form-group validated col-lg-2">
												<label class="col-form-label" id="">Valor total do frete/fatura</label>
												<div class="">
													<input required name="valor_total_frete" value="" type="tel" id="valor_total_frete" class="form-control money">

												</div>
											</div>
											<div class="form-group validated col-lg-2">
												<label class="col-form-label" id="">Desconto</label>
												<div class="">
													<input required name="desconto" value="0,00" type="tel" class="form-control money">

												</div>
											</div>
										</div>

										<div class="row">

											<div class="form-group validated col-12">
												<label class="col-form-label" id="">Observação</label>
												<div class="">
													<input name="observacao" value="" type="text" class="form-control">

												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>


					<div class="col-xl-3">
						<button id="update-devolucao" style="width: 100%" type="submit" class="btn btn-success">
							<i class="la la-check"></i>
							<span class="">Salvar Fatura</span>
						</button>
					</div>

				</form>

			</div>
		</div>
	</div>
</div>


@endsection
@section('javascript')
<script type="text/javascript">
	$(function(){
		setTimeout(() => {
			calcTotalFrete()
		}, 200)
	})
	$('body').on('blur', '.frete', function() {
		calcTotalFrete()
	})

	function calcTotalFrete(){
		let total = 0
		$('.frete').each(function() {

			total += parseFloat($(this).val().replace(",", "."));
		})
		$('#valor_total_frete').val(total.toFixed(2).replace(".", ","))
	}
</script>
@endsection