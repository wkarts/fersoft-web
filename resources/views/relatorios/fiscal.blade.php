@extends('relatorios.default')
@section('content')

@if($d1 && $d2)
<p>Periodo: {{$d1}} - {{$d2}}</p>
@endif

<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th width="25%" class="text-left">Cliente</th>
		<th width="15%" class="text-left">Data</th>
		<th width="10%" class="text-left">Estado</th>
		<th width="30%" class="text-left">Chave</th>
		<th width="15%" class="text-left">Valor</th>
		<th width="15%" class="text-left">Número</th>
		<th width="15%" class="text-left">Tipo</th>
	</tr>
</thead>

@php
$somaDoc = 0;
$tipo = $data[0]['tipo'];
$cont = 0;
$soma = 0;
@endphp

@foreach($data as $key => $d)

@php
$cont++;
$somaDoc+= $d['valor_total'];
@endphp
<tr class="@if($key%2 == 0) pure-table-odd @endif">
	<td>{{$d['cliente'] == '' ? 'Consumidor Final' : $d['cliente']}}</td>
	<td>{{$d['data']}}</td>
	<td>{{$d['estado']}}</td>
	<td>{{$d['chave']}}</td>
	<td>R$ {{ number_format($d['valor_total'], 2, ',', '.')}}</td>
	<td>{{$d['numero']}}</td>
	<td>{{ strtoupper($d['tipo']) }}</td>
</tr>

@if((isset($data[$key+1]) && $data[$key+1]['tipo'] != $tipo) || !isset($data[$key+1]))
@php
if(isset($data[$key+1])){
	$tipo = $data[$key+1]['tipo'];
}

@endphp
<tr style="background: #1BC5BD; height: 100px !important; font-size: 17px;">
	<td colspan="">Total de registros</td>
	<td colspan="">{{$cont}}</td>
	<td colspan="2">Soma</td>
	<td colspan="3">R$ {{ moeda($somaDoc) }}</td>
</tr>

@php
$cont = 0;
$somaDoc = 0;
@endphp

@endif
@php

$soma += $d['valor_total'];
@endphp
@endforeach

</table>

<table style="width: 100%;">
	<tbody>
		<tr class="text-left">
			<th width="50%">Total de documentos: {{ sizeof($data) }}</th>
			<th width="50%">Soma: R${{ number_format($soma, 2, ',', '.') }}</th>
		</tr>
	</tbody>
</table>

@endsection
