@extends('relatorios.default')
@section('content')
<style type="text/css">

	.tr-h{
		background: #C9F7F5;
	}
</style>

<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
@if($data_inicial && $data_final)
<caption>Período: {{$data_inicial}} - {{$data_final}}</caption>
@endif

@php
$somaLucro = 0;
$somaVendas = 0;
@endphp

@foreach($data as $item)

@php
$somaLucro = 0;
@endphp

<tr style="background: #C9F7F5">
	<td>Data</td>
	<td>Cliente</td>
	<td>Número da venda/pedido</td>
	<td>Total</td>
	<td>Tipo</td>
</tr>
<tr>
	<td>{{ $item['data'] }}</td>
	<td>{{ $item['cliente'] }}</td>
	<td>{{ $item['numero']}}</td>
	<td>R$ {{ number_format($item['total'], $casasDecimais, ',', '.')}}</td>
	<td>{{ $item['tipo']}}</td>

</tr>

<tr style="background: #EEE5FF">
	<td>Produto</td>
	<td>Quantidade</td>
	<td>Valor unit.</td>
	<td>Custo</td>
	<td>Lucro</td>
	<td>Lucro %</td>
</tr>
@foreach($item['itens'] as $i)
<tr>
	<td>{{$i['produto']}}</td>
	<td>{{ number_format($i['quantidade'], $casasDecimaisQtd, ',', '.') }}</td>
	<td>{{ number_format($i['valor'], $casasDecimais, ',', '.') }}</td>
	<td>{{ number_format($i['custo'], $casasDecimais, ',', '.') }}</td>
	<td>{{ number_format($i['lucro'], $casasDecimais, ',', '.') }}</td>
	<td>{{ number_format($i['lucro_perc'], 2, ',', '.') }}</td>
</tr>

@php
$somaLucro += ($i['lucro'] * $i['quantidade']);
@endphp

@endforeach

@php
$somaVendas += $item['total'];
@endphp



<tr style="background: #EE2D41; color: #fff;">
<td>Total de lucro:</td>
<td>R$ {{ number_format($somaLucro, $casasDecimais, ',', '.')}}</td>
</tr>

@endforeach

</table>

<h4>Soma Geral de Vendas: <strong style="color: #1BC5BD;">R$ {{ number_format($somaVendas, 2, ',', '.') }}</strong></h4>
@endsection
