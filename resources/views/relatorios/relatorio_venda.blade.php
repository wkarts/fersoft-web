@extends('relatorios.default')
@section('content')

@if($data_inicial && $data_final)
<h6>Período: {{$data_inicial}} - {{$data_final}}</h6>
@endif
<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th width="34%" class="text-left">Data</th>
		<th width="33%" class="text-left">Quantidade vendida</th>
		<th width="33%" class="text-left">Valor unitário médio</th>
	</tr>
</thead>

@php
$soma = 0;
$somaQuantidade = 0;
@endphp

<tbody>
	@foreach($vendas as $key => $v)
	<tr class="@if($key%2 == 0) pure-table-odd @endif">
		<td>{{\Carbon\Carbon::parse($v['data'])->format('d/m/Y')}}</td>
		<td>{{number_format($v['quantidade_vendida'], 2, ',', '.')}}</td>
		<td>
			@php
			$valorUnitarioMedio = $v['quantidade_vendida'] > 0 ? ($v['total'] / $v['quantidade_vendida']) : 0;
			@endphp
			R$ {{number_format($valorUnitarioMedio, 2, ',', '.')}}
		</td>

	</tr>
	@php
	$soma += $v['total'];
	$somaQuantidade += $v['quantidade_vendida'];
	@endphp
	@endforeach
</tbody>
</table>

</table>

<table style="width: 100%;">
	<tbody>
		<tr class="text-left">
			<th width="34%">Total vendido (R$)</th>
			<th width="33%">Quantidade total</th>
			<th width="33%">Valor unitário médio geral</th>
		</tr>
		<tr class="text-left">
			<th width="34%"><strong>R$ {{number_format($soma, 2, ',', '.')}}</strong></th>
			<th width="33%"><strong>{{number_format($somaQuantidade, 2, ',', '.')}}</strong></th>
			<th width="33%">
				<strong>
					R$ {{number_format($somaQuantidade > 0 ? ($soma/$somaQuantidade) : 0, 2, ',', '.')}}
				</strong>
			</th>
		</tr>
	</tbody>
</table>


@endsection
