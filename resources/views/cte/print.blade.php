@extends('relatorios.default')
@section('content')

<p>Período: {{$data_inicial}} - {{$data_final}}</p>

<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th width="50%" class="text-left">Destinatário</th>
		<th width="25%" class="text-left">Remetente</th>
		<th width="25%" class="text-left">Valor do serviço</th>
		<th width="25%" class="text-left">Estado</th>
		<th width="25%" class="text-left">Data</th>
		<th width="25%" class="text-left">Tomador</th>
		<th width="25%" class="text-left">Número Doc.</th>
	</tr>
</thead>
@php $soma = 0; @endphp
@foreach($ctes as $key => $c)
<tr class="@if($key%2 == 0) pure-table-odd @endif">

	<td>{{$c->destinatario->razao_social}}</td>
	<td>{{$c->remetente->razao_social}}</td>
	<td>
		{{ number_format($c->valor_transporte, 2, ',', '.') }}
	</td>
	<td>{{$c->estado}}</td>
	<td>{{ \Carbon\Carbon::parse($c->data_registro)->format('d/m/Y H:i')}}</td>
	<td>{{$c->getTomadorNome()}}</td>
	<td>{{$c->cte_numero > 0 ? $c->cte_numero : '-' }}</td>
</tr>
@php $soma += $c->valor_transporte; @endphp
@endforeach

<table style="width: 100%;">
	<tbody>
		<tr class="text-left">
			<th width="50%">Soma: R${{ number_format($soma, 2, ',', '.') }}</th>
		</tr>
	</tbody>
</table>
