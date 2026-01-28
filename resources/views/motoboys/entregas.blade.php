@extends('default.layout')
@section('css')
<style type="text/css">
	
	.btn-fab {
		position: fixed;
		bottom: 20px;
		right: 30px;
		z-index: 9999;
		border: none;
		outline: none;
		background-color: red;
		color: white;
		cursor: pointer;
		padding: 15px;
		border-radius: 10px;
	}  
</style>
@endsection
@section('content')

<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<h4>Entregas <strong>{{ $item->nome }}</strong></h4>


			<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

				<div class="pb-5" data-wizard-type="step-content">

					<!-- Inicio da tabela -->

					<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
						<form class="row" method="get" action="/motoboys/updatEntregas">
							<div class="col-xl-12">

								<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

									<table class="datatable-table" style="max-width: 100%; overflow: scroll">
										<thead class="datatable-head">
											<tr class="datatable-row" style="left: 0px;">

												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 60px;">#</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Data</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Pedido ID</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor pedido</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor entrega</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Status Pagamento</span></th>
											</tr>
										</thead>
										<tbody id="body" class="datatable-body">
											@foreach($entregas as $e)
											<tr class="datatable-row">
												<td class="datatable-cell">
													<span class="codigo" style="width: 60px;">
														<input class="check" value="{{ $e->id }}" type="checkbox" name="check[]">
														<input type="hidden" name="" class="valor-hidden" value="{{ $e->valor }}">
													</span>
												</td>
												<td class="datatable-cell">
													<span class="codigo" style="width: 150px;">
														{{ __date($e->created_at) }}
													</span>
												</td>
												<td class="datatable-cell">
													<span class="codigo" style="width: 100px;">
														{{ $e->pedido->id }}
													</span>
												</td>

												<td class="datatable-cell">
													<span class="codigo" style="width: 100px;">
														{{ moeda($e->pedido->valor_total) }}
													</span>
												</td>
												<td class="datatable-cell">
													<span class="codigo" style="width: 100px;">
														{{ moeda($e->valor) }}
													</span>
												</td>

												<td class="datatable-cell">
													<span class="codigo" style="width: 100px;">
														@if($e->status_pagamento)
														<i class="text-success la la-check"></i>
														@else
														<i class="text-danger la la-close"></i>
														@endif
													</span>
												</td>
											</tr>
											@endforeach
										</tbody>
									</table>
								</div>

								<h4 class="mt-2">Soma total: <strong>R${{ moeda($entregas->sum('valor')) }}</strong></h4>
								<h4 class="mt-2">Soma finalizadas: <strong class="text-success">R${{ moeda($entregas->where('status_pagamento',1)->sum('valor')) }}</strong></h4>
								<h4 class="mt-2">Soma pendentes: <strong class="text-danger">R${{ moeda($entregas->where('status_pagamento',0)->sum('valor')) }}</strong></h4>

								<button type="submit" class="btn btn-fab btn-success btn-pronto d-none">
									<i class="la la-check"></i> Finalizar <strong class="total-pagar">R$ 0,00</strong>
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">
	$(function(){
		validCheck()
	})

	$('.check').click(() => {
		validCheck()
	})

	function validCheck(){
		$('.btn-pronto').addClass('d-none')
		let total = 0;
		$('.check').each(function(i,x){
			if(x.checked){
				$inpValor = $(this).next();
				total += parseFloat($inpValor.val())
				$('.btn-pronto').removeClass('d-none')
			}
		})
		console.log("total", total)
		$('.total-pagar').text("R$"+total.toFixed(2).replace(".", ","))
	}

</script>
@endsection