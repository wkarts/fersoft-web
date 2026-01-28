<div class="row">
	@foreach($item->duplicatas as $d)
	<div class="col-lg-4 col-12">
		<div class="card">
			<div class="card-body">
				<h4>{{ $d->tipo_pagamento }}</h4>
				<h4>R${{ moeda($d->valor_integral) }}</h4>
			</div>
		</div>
	</div>
	@endforeach
</div>