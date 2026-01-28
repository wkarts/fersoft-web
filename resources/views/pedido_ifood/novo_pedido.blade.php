<div class="col-12">
	<div class="row">

		<h5 class="col-12">Pedido <strong class="text-danger">#{{$pedido->pedido_id}}</strong></h5>

		<div class="col-12">
			<h6>Cliente: <strong>{{$pedido->nome_cliente}}</strong></h6>
			<h6>Forma de pagamento: 
				@foreach($pedido->payments as $p)
				<strong>{{$p->forma_pagamento}} - {{ $p->bandeira_cartao }} R$ {{ number_format($p->valor, 2, ',', '.') }}</strong>
				@endforeach
			</h6>
			<h6>Telefone: <strong>{{$pedido->telefone_cliente}}</strong></h6>
		</div>
		<br>
		<div class="col-12">
			<h6>Valor dos produtos: <strong>R$ {{ number_format($pedido->valor_produtos, 2, ',', '.') }}</strong></h6>
			<h6>Valor da entrega: <strong>R$ {{ number_format($pedido->valor_entrega, 2, ',', '.') }}</strong></h6>
			<h6>Valor adicional do pedido: <strong>R$ {{ number_format($pedido->taxas_adicionais, 2, ',', '.') }}</strong></h6>
			<h6>Valor total do pedido: <strong class="text-success">R$ {{ number_format($pedido->valor_total, 2, ',', '.') }}</strong></h6>
		</div>

		<input type="hidden" name="pedido_id" value="{{$pedido->id}}" id="pedido_id">
		<input type="hidden" name="status" value="" id="status">
		<input type="hidden" name="motivo" value="" id="motivo">
		<input type="hidden" name="codigo" value="" id="codigo">

		<div class="col-12" style="border-top: 1px solid #888; margin-top: 10px;">

			@if($pedido->endereco)
			<h6 class="mt-3 text-danger">Endereço de entrega</h6>
			<p><strong>{{ $pedido->endereco }} {{ $pedido->bairro }}</strong></p>

			<p>CEP: <strong>{{ $pedido->cep }}</strong></p>
			@else
			<h6 class="mt-3 text-danger">Retirada no balcão</h6>
			@endif
		</div>

		<div class="col-12" style="border-top: 1px solid #888; margin-top: 10px;">
			<div class="row">
				<h5 class="col-12 mt-2">Itens do pedido</h5>
				@foreach($pedido->itens as $item)
				<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6">
					<div class="card card-custom gutter-b card-stretch">
						<div class="card-body pt-4">
							<div class="d-flex justify-content-end">
								<div class="dropdown dropdown-inline" data-toggle="tooltip" title="" data-placement="left" >
									
								</div>
							</div>
							<div class="d-flex align-items-end mb-7">
								<div class="d-flex align-items-center">
									<div class="flex-shrink-0 mr-4 mt-lg-0 mt-3">
										<div class="symbol symbol-circle symbol-lg-75">
											@if($item->image_url != "")
											<img src="{{$item->image_url}}" alt="image">
											@else
											<img src="/imgs/no_image.png" alt="image">
											@endif
										</div>
										<div class="symbol symbol-lg-75 symbol-circle symbol-primary d-none">
											<span class="font-size-h3 font-weight-boldest">JM</span>
										</div>
									</div>
									<div class="d-flex flex-column">
										<a class="text-dark font-weight-bold text-hover-primary font-size-h4 mb-0">{{$item->nome_produto}}</a>
									</div>
								</div>
							</div>

							<div class="mb-7">
								<div class="d-flex align-items-center">
									<span class="text-dark-75 font-weight-bolder mr-2">Quantidade:</span>
									<a class="text-info text-left">{{$item->quantidade}}</a>
								</div>

								<div class="d-flex align-items-center">
									<span class="text-dark-75 font-weight-bolder mr-2">Valor unitário:</span>
									<a class="text-info text-left">R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</a>
								</div>

								<div class="d-flex align-items-center">
									<span class="text-dark-75 font-weight-bolder mr-2">Valor total:</span>
									<a class="text-info text-left">R$ {{ number_format($item->total, 2, ',', '.') }}</a>
								</div>
								

								<div class="d-flex align-items-center">
									<span class="text-dark-75 font-weight-bolder mr-2">Adicionais:</span>
									<a class="text-info text-left">
										@foreach($item->adicionais as $a)
										{{$a->quantidade}}x {{$a->nome}} R$ {{ moeda($a->valor_unitario) }}
										@if(!$loop->last)
										|
										@endif
										@endforeach
									</a>
								</div>

								<!-- <div class="d-flex align-items-center">
									<span class="text-dark-75 font-weight-bolder mr-2">Tamanho:</span>
									<a class="text-info text-left">{{$item->tamanho ? $item->tamanho->nome : "--"}}</a>
								</div> -->
							</div>
						</div>
					</div>
				</div>

				@endforeach
			</div>
		</div>
		
	</div>
</div>