@extends('relatorios.default')
@section('content')

<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th class="text-left">Produto</th>
		<th class="text-left">Quantidade</th>
		<th class="text-left">Valor de venda</th>
		<th class="text-left">Valor de compra</th>
		<th class="text-left">Data</th>
		<th class="text-left">Usuário</th>
		<th class="text-left">Motivo</th>
	</tr>
</thead>
@php
$somaVendas = 0;
$somaCompras = 0;
@endphp
<tbody>
	@foreach($data as $item)
	<tr>
		<td>{{ $item->produto->nome }} {{ $item->produto->str_grade }}</td>
		<td>{{ moeda($item->quantidade) }}</td>
		<td>{{ moeda($item->produto->valor_venda) }}</td>
		<td>{{ moeda($item->produto->valor_compra) }}</td>
		<td>{{ __date($item->created_at, 1) }}</td>
		<td>{{ $item->usuario->nome }}</td>
		<td>{{ $item->motivo }}</td>
		
	</tr>
	@php
	$somaVendas += $item->produto->valor_venda;
	$somaCompras += $item->produto->valor_compra;
	@endphp
	@endforeach
</tbody>
</table>

<table style="width: 100%;">
	<tbody>
		<tr class="text-left">
			<th width="15%">Quantidade de itens</th>
			<th width="15%"><strong>R$ {{ moeda($data->sum('quantidade')) }}</strong></th>
			<th width="15%">Soma valor de venda</th>
			<th width="15%"><strong>R$ {{ moeda($somaVendas) }}</strong></th>
			<th width="15%">Soma valor de compra</th>
			<th width="15%"><strong>R$ {{ moeda($somaCompras) }}</strong></th>
		</tr>
	</tbody>
</table>

@endsection
