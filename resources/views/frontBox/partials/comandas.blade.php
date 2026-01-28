<div class="row" style="max-height: 500px; overflow: scroll;">
	<h4 class="col-12">Mesas em vermelha estão fechadas</h4>
	@foreach($data as $mesa)
	<div class="col-md-4 col-6 mt-1">
		<div class="card @if($mesa->pedido == null) bg-light-danger @else bg-success @endif">
			<div class="card-header">
				<h4><strong class="text-info">{{ $mesa->nome }}</strong></h4>
			</div>
			<div class="card-body" style="height: 200px">
				@if($mesa->pedido)
				<h4>Total: <strong class="text-info">R$ {{ moeda($mesa->pedido->somaItems()) }}</strong></h4>
				<h5>Horário Abertura: <strong class="text-info">{{ \Carbon\Carbon::parse($mesa->pedido->created_at)->format('d/m/Y H:i')}}</strong></h5>
				<h5>Total de itens: <strong class="text-info">{{ sizeof($mesa->pedido->itens) }}</strong></h5>
				<h5>Itens Pendentes: <strong class="text-info">{{ $mesa->pedido->itensPendentes() }}</strong></h5>
				<button onclick="setarComanda('{{ $mesa->pedido->comanda }}')" class="btn w-100 btn-info">
					<i class="la la-check"></i>
					Apontar
				</button>
				@else
				<h4>Total: <strong class="text-info">R$ {{ moeda(0) }}</strong></h4>

				<h3 class="text-danger">Mesa fechada</h3>
				@endif
			</div>
		</div>
		
	</div>
	@endforeach
</div>