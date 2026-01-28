@extends('relatorios.default')
@section('content')

<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th class="text-left">Cliente</th>
		<th class="text-left">Tipo de pag.</th>
		<th class="text-left">Total venda</th>
		<th class="text-left">% Taxa</th>
		<th class="text-left">R$ Taxa</th>
		<th class="text-left">Data</th>
		<th class="text-left">Tipo</th>
		<th class="text-left">ID</th>
	</tr>
</thead>

<tbody>
	@php 
	$somaTotal = 0;
	$somaTaxa = 0;
	@endphp
	@foreach($data as $item)
	<tr>
		<td>{{ $item['cliente'] }}</td>
		<td>{{ $item['tipo_pagamento'] }}</td>
		<td>{{ moeda($item['total']) }}</td>
		<td>{{ moeda($item['taxa_perc']) }}</td>
		<td>{{ moeda($item['taxa']) }}</td>
		<td>{{ $item['data'] }}</td>
		<td>{{ $item['tipo'] }}</td>
		<td>#{{ $item['venda_id'] }}</td>
		
	</tr>
	@php 
	$somaTotal += $item['total'];
	$somaTaxa += $item['taxa'];
	@endphp
	@endforeach

</tbody>
<tfoot>
	<tr>
		<td colspan="2" style="border-top: 1px solid #999;"></td>
		<td style="font-weight: bold; border-top: 1px solid #999;">{{ moeda($somaTotal) }}</td>
		<td style="border-top: 1px solid #999;"></td>
		<td style="font-weight: bold; border-top: 1px solid #999;">{{ moeda($somaTaxa) }}</td>
		<td colspan="3" style="border-top: 1px solid #999;"></td>
	</tr>
</tfoot>
</table>


@endsection
