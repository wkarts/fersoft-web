<div class="table-responsive">
	<table class="table">
		<thead>
			<tr>
				<th>#</th>
				<th>Fornecedor</th>
				<th>Data</th>
				<th>Valor</th>
				<th>Lote</th>
				<th>Ações</th>
			</tr>
		</thead>
		<tbody>
			@foreach($compras as $item)
			<tr>
				<td>{{ $item->numero_sequencial }}</td>
				<td>{{ $item->fornecedor->razao_social }}</td>
				<td>{{ __date($item->created_at) }}</td>
				<td>{{ moeda($item->valor) }}</td>
				<td>{{ $item->lote }}</td>
				<td>
					<a href="/compras/setar-validade/{{ $item->id }}" class="btn btn-warning">
						Setar Validade
					</a>
				</td>
			</tr>
			@endforeach
		</tbody>
	</table>
</div>