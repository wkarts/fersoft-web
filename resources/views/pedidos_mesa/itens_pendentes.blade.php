@foreach($data as $item)
<div class="col-lg-6 mt-2">
	<div class="card">
		<div class="card-header">
			<h5 class="text-white">{{ $item->produto->produto->nome }}</h5>
		</div>
		<div class="card-body">
			<h5>Quantidade: <strong>{{ $item->quantidade }}</strong></h5>
			<h5>Valor: <strong>R$ {{ moeda($item->valor) }}</strong></h5>
			<h5>Subtotal: <strong>R$ {{ moeda($item->valor*$item->quantidade) }}</strong></h5>
			<h5>Adicionais: 
				<strong>
					@if(sizeof($item->itensAdicionais) > 0)
					@foreach($item->itensAdicionais as $add)
					<b>{{ $add->adicional->nome }} @if(!$loop->last) | @endif</b>
					@endforeach
					@else
					<b>--</b>
					@endif
				</strong>
			</h5>
			<h5>Tamanho: <strong>{{ $item->tamanho ? $item->tamanho->nome : '--' }}</strong></h5>
			<h5>Sabores: 
				<strong>
					@if(sizeof($item->sabores) > 0)
					@foreach($item->sabores as $s)
					{{$s->produto->produto->nome}} @if(!$loop->last) | @endif
					@endforeach
					@else
					<b>--</b>
					@endif
				</strong>
			</h5>
		</div>
		<div class="card-footer">
			<a class="btn btn-info w-100" href="/pedidosMesa/entregue/{{$item->id}}">
				<i class="la la-check"></i>
				Marcar como entregue
			</a>
		</div>
	</div>
</div>
@endforeach