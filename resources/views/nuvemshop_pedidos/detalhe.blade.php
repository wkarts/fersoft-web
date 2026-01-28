@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<div class="row justify-content-center py-8 px-8 py-md-27 px-md-0">
				<div class="col-md-10">
					<div class="d-flex justify-content-between pb-10 pb-md-20 flex-column flex-md-row">
						<h1 class="display-4 font-weight-boldest mb-10">DETALHES DO PEDIDO</h1>
						<div class="d-flex flex-column align-items-md-end px-0">
							<!--begin::Logo-->
							<a href="#" class="mb-5">
								<img src="/metronic/theme/html/demo1/dist/assets/media/logos/logo-dark.png" alt="">
							</a>
							<!--end::Logo-->
							<span class="d-flex flex-column align-items-md-end opacity-70">
								<span>Transação ID Nuvem Shop: <strong class="text-info">{{$pedido->pedido_id}}</strong></span>

							</span>
						</div>
					</div>
					<div class="border-bottom w-100"></div>
					<div class="d-flex justify-content-between pt-6">
						<div class="d-flex flex-column flex-root">
							<span class="font-weight-bolder mb-2">DATA</span>
							<span class="opacity-70">
								{{ $pedido->getDate()}}
							</span>
						</div>
						<div class="d-flex flex-column flex-root">
							<span class="font-weight-bolder mb-2">Cliente</span>
							<span class="opacity-70">
								{{ $pedido->nome }}
							</span>
						</div>
						<div class="d-flex flex-column flex-root">
							<span class="font-weight-bolder mb-2">Endereço</span>
							<span class="opacity-70">
								{{$pedido->cliente->rua}}, {{$pedido->cliente->numero}} - {{$pedido->cliente->bairro}} - {{$pedido->cliente->complemento}}
							</span>
							<span class="opacity-70">
								{{$pedido->cliente->cidade->nome}} ({{$pedido->cliente->cidade->uf}}) | {{$pedido->cliente->cep}}
							</span>
						</div>
						@if($pedido->observacao != "")
						<div class="d-flex flex-column flex-root">
							<span class="font-weight-bolder mb-2">Observação</span>
							<span class="opacity-70">
								{{$pedido->observacao}}
							</span>
						</div>
						@endif
						
					</div>

					@foreach($erros as $e)
					<h5 class="text-danger">{{$e}}</h5>
					@endforeach


					<a target="_blank" class="btn btn-warning" href="/clientes/edit/{{$pedido->cliente->id}}">
						<i class="la la-edit"></i>Editar cliente
					</a>
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
								
								@foreach($pedido->itens as $i)

								<tr class="font-weight-boldest border-bottom-0">
									<td class="border-top-0 pl-0 py-4 d-flex align-items-center">
										<!--begin::Symbol-->
										<div class="symbol symbol-40 flex-shrink-0 mr-4 bg-light">
											<div class="symbol-label" style="background-image: url('{{$i->src}}')"></div>
										</div>
										<!--end::Symbol-->

										{{$i->produto->nome}} 
										@if($i->produto->grade)
										({{$i->produto->str_grade}})
										@endif
									</td>
									<td class="border-top-0 text-right py-4 align-middle">
										{{$i->quantidade}}
									</td>
									<td class="border-top-0 text-right py-4 align-middle">R$ {{ number_format($i->valor, 2, ',', '.') }}</td>
									<td class="text-primary border-top-0 pr-0 py-4 text-right align-middle">R$ {{ number_format($i->quantidade*$i->valor, 2, ',', '.') }}</td>
								</tr>

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
									
									<th class="font-weight-bold text-muted text-uppercase">PAGAMENTO STATUS</th>
									<th class="font-weight-bold text-muted text-uppercase">ENVIO STATUS</th>
									<th class="font-weight-bold text-muted text-uppercase">DESCONTO</th>
									<th class="font-weight-bold text-muted text-uppercase">FRETE</th>
									<th class="font-weight-bold text-muted text-uppercase text-right">TOTAL</th>
								</tr>
							</thead>
							<tbody>
								<tr class="font-weight-bolder">
									
									<td>{{$pedido->status_pagamento}}</td>
									<td>{{$pedido->status_envio}}</td>
									<td>R$ {{ number_format($pedido->desconto, 2, ',', '.')}}</td>
									<td>R$ {{ number_format($pedido->valor_frete, 2, ',', '.')}}</td>

									<td class="text-primary font-size-h3 font-weight-boldest text-right">R$ {{ number_format($pedido->total, 2, ',', '.')}}</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<br>

			@if(!$pedido->venda)
			<a class="btn btn-info" href="/nuvemshop/gerarNFe/{{$pedido->id}}">
				<i class="la la-file"></i>
				Gerar NFe
			</a>
			@else

			@if($pedido->numero_nfe > 0)
			<a class="btn btn-light-success" target="_blank" href="/nf/imprimir/{{$pedido->venda->id}}">
				<i class="la la-print"></i>
				Imprimir Danfe
			</a>
			@endif
			<a class="btn btn-light-info" href="/vendas/detalhar/{{$pedido->venda->id}}">
				<i class="la la-file-alt"></i>
				Ver Venda
			</a>

			@endif

			<a target="_blank" class="btn btn-light-primary" href="/nuvemshop/imprimir/{{$pedido->id}}">
				<i class="la la-print"></i>
				Imprimir Pedido
			</a>

		</div>
	</div>
</div>
@endsection