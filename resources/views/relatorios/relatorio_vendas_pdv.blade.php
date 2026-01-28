@extends('relatorios.default')
@section('content')

@if($data_inicial && $data_final)
<h6>Período: {{ __date($data_inicial) }} - {{ __date($data_final) }}</h6>
@endif
<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th width="50%" class="text-left">Data</th>
		<th width="50%" class="text-left">Total</th>
	</tr>
</thead>

@php
$soma = 0;
@endphp

<tbody>
	@foreach($vendas as $key => $v)
	<tr class="@if($key%2 == 0) pure-table-odd @endif">
		<td>{{ ($v->data) }}</td>
		<td>R$ {{ moeda($v->valor_total)}}</td>

	</tr>
	
	@endforeach
</tbody>
</table>

</table>

<table style="width: 100%;">
	<tbody>
		<tr class="text-left">
			<th width="50%">Soma</th>
			<th width="50%"><strong>R$ {{ moeda($vendas->sum('valor_total')) }}</strong></th>
		</tr>
	</tbody>
</table>


@endsection
