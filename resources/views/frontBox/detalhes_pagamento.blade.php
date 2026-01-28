<div class="row">
	@foreach($item->fatura as $d)
	<div class="col-lg-4 col-12">
		<div class="card">
			<div class="card-body">
				<h4>{{ \App\Models\VendaCaixa::getTipoPagamento($d->forma_pagamento) }}</h4>
				<h4>R${{ moeda($d->valor) }}</h4>
			</div>
		</div>
	</div>
	@endforeach
</div>