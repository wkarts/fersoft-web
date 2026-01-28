@extends('relatorios.default')
@section('content')

<style type="text/css">
	tfoot td{
		border-top: 1px solid #999;
		font-weight: bold;
	}
</style>
<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th class="text-left">Produto ID</th>
		<th class="text-left">Produto Nome</th>
		<th class="text-left">Quantidade</th>
		<th class="text-left">Valor unitário</th>
		<th class="text-left">Subtotal</th>
		<th class="text-left">Percentual</th>
	</tr>
</thead>

<tbody>

	@foreach($data as $item)
	<tr>
		<td>{{ $item['produto_id'] }}</td>
		<td>{{ $item['produto_nome'] }}</td>
		<td>{{ $item['quantidade'] }}</td>
		<td>R$ {{ moeda($item['valor']) }}</td>
		<td>R$ {{ moeda($item['sub_total']) }}</td>
		<td>{{ $item['percentual'] }}%</td>
	</tr>

	
	@endforeach
</tbody>

<tfoot>
	<tr>
		<td colspan="4">Soma</td>
		<td colspan="2">R$ {{ moeda($soma) }}</td>
	</tr>
</tfoot>

</table>

@endsection
