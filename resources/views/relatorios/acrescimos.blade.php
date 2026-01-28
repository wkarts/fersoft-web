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
		<th class="text-left">Cliente</th>
		<th class="text-left">Total da venda</th>
		<th class="text-left">Acréscimo</th>
		<th class="text-left">Data</th>
		<th class="text-left">Tipo</th>
		<th class="text-left">Venda ID</th>
	</tr>
</thead>

<tbody>
	@php 
	$soma = 0;
	$somaAcrescimo = 0;
	@endphp

	@foreach($data as $item)
	<tr>
		<td>{{ $item['cliente'] }}</td>
		<td>{{ moeda($item['total']) }}</td>
		<td>{{ moeda($item['acrescimo']) }}</td>
		<td>{{ $item['data'] }}</td>
		<td>{{ $item['tipo'] }}</td>
		<td>#{{ $item['venda_id'] }}</td>
	</tr>

	@php 
	$soma += $item['total'];
	$somaAcrescimo += $item['acrescimo'];
	@endphp
	@endforeach
</tbody>
<tfoot>
	<tr>
		<td></td>
		<td>{{ moeda($soma) }}</td>
		<td>{{ moeda($somaAcrescimo) }}</td>
		<td colspan="3"></td>
	</tr>
</tfoot>
</table>

@endsection
