@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<div class="row justify-content-center py-8 px-8 py-md-27 px-md-0">
				<div class="col-md-10">
					<div class="d-flex justify-content-between pb-10 pb-md-20 flex-column flex-md-row">
						<h1 class="display-4 font-weight-boldest mb-10">DETALHES DO PEDIDO</h1>
						@if($item->status == 'CAN')
						<h3 class="text-danger">Pedido cancelado!</h3>
						@endif
						<div class="d-flex flex-column align-items-md-end px-0">
							<!--begin::Logo-->
							<a href="#" class="mb-5">
								<img src="/metronic/theme/html/demo1/dist/assets/media/logos/logo-dark.png" alt="">
							</a>
							<!--end::Logo-->
							<span class="d-flex flex-column align-items-md-end opacity-70">
								<span>Pedido ID: <strong class="text-info">{{$item->pedido_id}}</strong></span>
							</span>
							@if($item->status != 'CAN')
							<button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modal-cancelar">
								Cancelar pedido
							</button>
							@endif
						</div>
					</div>


					<div class="border-bottom w-100"></div>
					<div class="d-flex justify-content-between pt-6">
						<div class="d-flex flex-column flex-root">
							<span class="font-weight-bolder mb-2">DATA</span>
							<span class="opacity-70">
								{{ $item->data_pedido }}
							</span>
						</div>
						<div class="d-flex flex-column flex-root">
							<span class="font-weight-bolder mb-2">Cliente</span>
							<span class="opacity-70">
								{{ $item->nome_cliente }}
							</span>
						</div>
						<div class="d-flex flex-column flex-root">
							<span class="font-weight-bolder mb-2">Endereço</span>
							<span class="opacity-70">
								{{ $item->endereco }} - {{ $item->bairro }}
							</span>
							<span class="opacity-70">
								{{ $item->ccep }}
							</span>
						</div>
						
					</div>

				</div>
			</div>

			<div class="row justify-content-center py-8 px-8 py-md-10 px-md-0">
				<div class="col-md-10">
					<div class="table-responsive">
						<table class="table">
							<thead>
								<tr>
									<th class="pl-0 font-weight-bold text-muted text-uppercase">Produto</th>
									<th class="text-right font-weight-bold text-muted text-uppercase">Quantidade</th>
									<th class="text-right font-weight-bold text-muted text-uppercase">Valor unitário</th>
									<th class="text-right pr-0 font-weight-bold text-muted text-uppercase">Total</th>
								</tr>
							</thead>
							<tbody>
								
								@foreach($item->itens as $i)

								<tr class="font-weight-boldest border-bottom-0">
									<td class="border-top-0 pl-0 py-4 d-flex align-items-center">
										<!--begin::Symbol-->
										<div class="symbol symbol-40 flex-shrink-0 mr-4 bg-light">
											@if($i->image_url != "")
											<div class="symbol-label" style="background-image: url('{{$i->image_url}}')"></div>
											@else
											<div class="symbol-label" style="background-image: url('/imgs/no_image.png')"></div>
											@endif
										</div>
										<!--end::Symbol-->

										{{ $i->nome_produto }}
									</td>
									<td class="border-top-0 text-right py-4 align-middle">
										{{ $i->quantidade }}
									</td>
									<td class="border-top-0 text-right py-4 align-middle">R$ {{ number_format($i->valor_unitario, 2, ',', '.') }}</td>
									<td class="text-primary border-top-0 pr-0 py-4 text-right align-middle">R$ {{ number_format($i->total, 2, ',', '.') }}</td>
								</tr>
								@if(sizeof($i->adicionais) > 0)
								<tr>
									<td colspan="4" class="text-primary">
										@foreach($i->adicionais as $a)
										{{ $a->quantidade }}x {{ $a->nome }} - R$ {{ moeda($a->valor_unitario) }}<br>
										@endforeach
									</td>
								</tr>
								@endif
								@endforeach

							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="row justify-content-center bg-gray-100 py-8 px-8 py-md-10 px-md-0 mx-0">
				<div class="col-md-10">
					<div class="table-responsive">
						<table class="table">
							<thead>
								<tr>
									<th class="font-weight-bold text-muted text-uppercase">STATUS</th>
									<th class="font-weight-bold text-muted text-uppercase">VALOR ENTREGA</th>
									<th class="font-weight-bold text-muted text-uppercase">VALOR PRODUTOS</th>
									<th class="font-weight-bold text-muted text-uppercase text-right">TOTAL</th>
								</tr>
							</thead>
							<tbody>
								<tr class="font-weight-bolder">
									
									<td>
										@if($item->status == 'CAN')
										<strong class="text-danger">{{$item->status}}</strong>
										@else
										<strong class="text-info">{{$item->status}}</strong>
										@endif
									</td>
									<td>R$ {{ number_format($item->valor_entrega, 2, ',', '.')}}</td>
									<td>R$ {{ number_format($item->valor_produtos, 2, ',', '.')}}</td>

									<td class="text-primary font-size-h3 font-weight-boldest text-right">R$ {{ number_format($item->valor_total, 2, ',', '.')}}</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>

			@if(sizeof($item->payments) > 0)
			<br>
			<div class="row justify-content-center bg-gray-100 py-8 px-8 py-md-10 px-md-0 mx-0">
				<div class="col-md-10">
					<h4>Pagamentos</h4>
					<div class="table-responsive">
						<table class="table">
							<thead>
								<tr>
									<th class="font-weight-bold text-muted text-uppercase">FORMA PAGAMENTO</th>
									<th class="font-weight-bold text-muted text-uppercase">TIPO PAGAMENTO</th>
									<th class="font-weight-bold text-muted text-uppercase text-right">VALOR</th>
								</tr>
							</thead>
							<tbody>
								@foreach($item->payments as $p)
								<tr class="font-weight-bolder">
									
									<td>
										<strong class="text-info">{{  \App\Models\PagamentoPedidoIfood::getFormPay($p->forma_pagamento) }}</strong>
									</td>
									<td>
										<strong class="text-info">{{$p->tipo_pagamento}}</strong>
									</td>

									<td class="text-info font-size-h3 font-weight-boldest text-right">R$ {{ number_format($p->valor, 2, ',', '.')}}</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
			</div>
			@endif

			<br>
			@if(!$item->venda)
			@if($item->status != 'CAN')
			<a class="btn btn-success float-right" href="/ifood/pdv/{{$item->id}}">
				<i class="la la-file"></i>
				Ir Para o PDV
			</a>
			@endif
			@else

			@if($item->venda->NFcNumero > 0)
			<a class="btn btn-light-success" target="_blank" href="/nfce/imprimir/{{$item->venda->id}}">
				<i class="la la-print"></i>
				Imprimir Cupom Fiscal
			</a>
			@endif
			<a class="btn btn-light-info" href="/nfce/detalhes/{{$item->venda->id}}">
				<i class="la la-file-alt"></i>
				Ver Venda
			</a>

			<a target="_blank" class="btn btn-light-primary" href="/nfce/imprimirNaoFiscal/{{$item->venda->id}}">
				<i class="la la-print"></i>
				Imprimir Cupom Não Fiscal
			</a>

			@endif

			<a class="btn btn-light-success" target="_blank" href="/ifood/imprimirPedido/{{$item->id}}">
				<i class="la la-print"></i>
				Imprimir Pedido
			</a>

		</div>
	</div>
</div>

<div class="modal fade" id="modal-cancelar" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<form class="modal-content" method="post" action="/ifood/cancel">
			@csrf
			<div class="modal-header">
				<h5 class="modal-title">Cancelando Pedido #{{$item->pedido_id}}</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<input type="hidden" name="pedido_id" value="{{ $item->id }}">
			<div class="modal-body">
				<div class="form-group col-lg-6 col-12">
					<label>Código cancelamento</label>
					<select required name="codigo" class="custom-select form-control">
						<option value="">selecione</option>
						@foreach(\App\Models\IfoodConfig::getStatusErros() as $key => $c)
						<option value="{{ $key }}">{{ $key }} - {{ $c }}</option>
						@endforeach
					</select>
				</div>
				<div class="form-group col-lg-12 col-12">
					<label>Descrição de cancelamento</label>
					<input type="text" name="motivo" class="form-control">
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="submit" id="btn-send-cliente" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Cancelar</button>
			</div>

		</form>
	</div>
</div>
@endsection