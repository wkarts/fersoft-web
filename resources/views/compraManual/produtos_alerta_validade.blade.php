<div class="table-responsive">
	<table class="table">
		<thead>
			<tr>
				<th>Produto</th>
				<th>Validade</th>
				<th>Lote</th>
				<th>Dias para vencer</th>
				<th>Valor</th>
				<th>Compra ID</th>
				<th>Fornecedor</th>
			</tr>
		</thead>
		<tbody>
			@foreach($data as $item)
			<tr>
				<td>{{ $item->produto->nome }}</td>
				<td>{{ __date($item->validade, 0) }}</td>
				<td>{{ $item->compra->lote }}</td>
				<td>{{ $item->dif >= 1 ? $item->dif : ('vencido a ' . ($item->dif*-1) . ' dias') }}</td>
				<td>{{ moeda($item->valor_unitario) }}</td>
				<td>{{ $item->compra->numero_sequencial }}</td>
				<td>{{ $item->compra->fornecedor->razao_social }}</td>
				
			</tr>
			@endforeach
		</tbody>
	</table>
</div>