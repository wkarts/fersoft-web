<div class="col-12">
	<div class="row">

		<h5 class="col-12">Pedido #{{$pedido->id}}</h5>

		<div class="col-lg-6 col-12">
			<h6>Cliente: <strong>{{$pedido->cliente->nome}} {{$pedido->cliente->sobre_nome}}</strong></h6>
			<h6>Forma de pagamento: <strong>{{$pedido->forma_pagamento}}</strong></h6>
			@if($pedido->transacao_id != '' && $pedido->status_pagamento == 'approved')
			<h6 class="text-success">PAGAMENTO APROVADO ID TRANSAÇÃO: <strong>{{$pedido->transacao_id}}</strong></h6>
			@endif

			<h6>Telefone: <strong>{{$pedido->telefone}}</strong></h6>
			@if($pedido->observacao != '')
			<h6>Observação: <strong>{{$pedido->observacao}}</strong></h6>
			@endif
		</div>
		<div class="col-lg-6 col-12">
			<h6>Valor total do pedido: <strong class="text-success">R$ {{ number_format($pedido->valor_total + $pedido->valor_entrega, 2, ',', '.') }}</strong></h6>
			<h6>Valor da entrega: <strong>R$ {{ number_format($pedido->valor_entrega, 2, ',', '.') }}</strong></h6>
			<h6>Troco para: <strong>R$ {{ number_format($pedido->troco_para, 2, ',', '.') }}</strong></h6>
		</div>

		<input type="hidden" name="pedido_id" value="{{$pedido->id}}" id="pedido_id">
		<input type="hidden" name="estado" value="" id="estado">
		<div class="col-12" style="border-top: 1px solid #888; margin-top: 10px;">

			@if($pedido->endereco)
			<h6 class="mt-3 text-danger">Endereço de entrega</h6>
			<p>{{ $pedido->endereco->rua }}, {{ $pedido->endereco->numero }} - {{ $pedido->endereco->_bairro->nome }} | {{ $pedido->endereco->cidade->nome }} ({{ $pedido->endereco->cidade->uf }})</p>

			<p>CEP: <strong>{{ $pedido->endereco->cep }}</strong></p>
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
											@if(sizeof($item->produto->galeria) > 0)
											<img src="/imagens_produtos/{{$item->produto->galeria[0]->path}}" alt="image">
											@else
											<img src="imgs/no_image.png" alt="image">
											@endif
										</div>
										<div class="symbol symbol-lg-75 symbol-circle symbol-primary d-none">
											<span class="font-size-h3 font-weight-boldest">JM</span>
										</div>
									</div>
									<div class="d-flex flex-column">
										<a class="text-dark font-weight-bold text-hover-primary font-size-h4 mb-0">{{$item->produto->produto->nome}}</a>
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
									<a class="text-info text-left">R$ {{ number_format($item->valor, 2, ',', '.') }}</a>
								</div>

								<div class="d-flex align-items-center">
									<span class="text-dark-75 font-weight-bolder mr-2">Valor total:</span>
									<a class="text-info text-left">R$ {{ number_format($item->quantidade*$item->valor, 2, ',', '.') }}</a>
								</div>
								<div class="d-flex align-items-center">
									<span class="text-dark-75 font-weight-bolder mr-2">Categoria:</span>
									<a class="text-info text-left">{{$item->produto->categoria->nome}}</a>
								</div>

								<div class="d-flex align-items-center">
									<span class="text-dark-75 font-weight-bolder mr-2">Adicionais:</span>
									<a class="text-info text-left">
										@foreach($item->itensAdicionais as $a)
										{{$a->adicional->nome}} 
										@if(!$loop->last)
										|
										@endif
										@endforeach
									</a>
								</div>

								<div class="d-flex align-items-center">
									<span class="text-dark-75 font-weight-bolder mr-2">Tamanho:</span>
									<a class="text-info text-left">{{$item->tamanho ? $item->tamanho->nome : "--"}}</a>
								</div>
								<div class="d-flex align-items-center">
									<span class="text-dark-75 font-weight-bolder mr-2">Observação:</span>
									<a class="text-info text-left">{{$item->observacao != '' ? $item->observacao : "--"}}</a>
								</div>

								<div class="d-flex align-items-center">
									<span class="text-dark-75 font-weight-bolder mr-2">Sabores:</span>
									<a class="text-info text-left">
										@foreach($item->sabores as $s)
										{{$s->produto->produto->nome}} 
										@if(!$loop->last)
										|
										@endif
										@endforeach
									</a>
									@if(sizeof($item->sabores) == 0)
									--
									@endif
								</div>
							</div>
						</div>
					</div>
				</div>

				@endforeach
			</div>
		</div>
	</div>
</div>