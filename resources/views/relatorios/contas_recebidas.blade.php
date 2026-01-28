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
		<th width="15%" class="text-left">Cliente</th>
		<th width="15%" class="text-left">Valor Integral</th>
		<th width="15%" class="text-left">Valor Recebido</th>
		<th width="10%" class="text-left">Dt. Cadastro</th>
		<th width="10%" class="text-left">Dt. Recebimento</th>
		<th width="30%" class="text-left">Tipo de Pagamento</th>
	</tr>
</thead>

<tbody>
	@foreach($data as $key => $item)
	<tr class="@if($key%2 == 0) pure-table-odd @endif">
		<td>{{ $item->cliente ? $item->cliente->razao_social : '--' }}</td>
		<td>{{ moeda($item->valor_integral) }}</td>
		<td>{{ moeda($item->valor_recebido) }}</td>
		<td>{{ __date($item->created_at) }}</td>
		<td>{{ __date($item->data_recebimento, 0) }}</td>
		<td>{{ $item->tipo_pagamento }}</td>
	</tr>
	@endforeach
</tbody>

<tfoot>

	<td colspan="2"></td>
	<td>R$ {{ moeda($data->sum('valor_recebido')) }}</td>
	<td colspan="3"></td>

</tfoot>

</table>


@endsection
