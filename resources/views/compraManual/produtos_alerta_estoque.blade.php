<div class="table-responsive">
	<table class="table">
		<thead>
			<tr>
				<th>Produto</th>
				<th>Estoque atual</th>
				<th>Valor de venda</th>
				<th>Valor de compra</th>
				<th>Data de cadastro</th>
			</tr>
		</thead>
		<tbody>
			@foreach($data as $item)
			<tr>
				<td>{{ $item->nome }}</td>
				<td>
					@if($item->estoque)
					{{ $item->estoque->quantidade }}
					@else
					0
					@endif
				</td>
				<td>{{ moeda($item->valor_venda) }}</td>
				<td>{{ moeda($item->valor_compra) }}</td>
				<td>{{ __date($item->created_at, 1) }}</td>
				
			</tr>
			@endforeach
		</tbody>
	</table>
</div>