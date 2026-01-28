@extends('relatorios.default')
@section('content')

@if($data_inicial && $data_final)
<p>Período: {{$data_inicial}} - {{$data_final}}</p>
@endif

<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th width="15%" class="text-left">CTe</th>
		<th width="15%" class="text-left">Remetente</th>
		<th width="10%" class="text-left">Chave</th>
		<th width="30%" class="text-left">Valor Mercadoria</th>
		<th width="15%" class="text-left">Peso</th>
		<th width="15%" class="text-left">Frete</th>
	</tr>
</thead>

<tbody>
	@php
	$somaMercadoria = 0;
	$somaPeso = 0;
	$somaFrete = 0;
	@endphp
	@foreach($data as $key => $item)
	<tr class="@if($key%2 == 0) pure-table-odd @endif">
		<td>{{ $item->id }}</td>
		<td>{{ $item->remetente->razao_social }}</td>
		<td>{{ $item->chave == "" ? "--" : $item->chave }}</td>
		<td>{{ moeda($item->valor_carga) }}</td>
		<td>{{ ($item->somaMedidas()) }}</td>
		<td>{{ moeda($item->somaComponentes()) }}</td>
	</tr>
	@php
	$somaMercadoria += $item->valor_carga;
	$somaPeso += $item->somaMedidas();
	$somaFrete += $item->somaComponentes();
	@endphp
	@endforeach
</tbody>

<tfoot>
	<td colspan="2">Total de documentos: {{ sizeof($data) }}</td>
	<td></td>
	<td>R$ {{ moeda($somaMercadoria) }}</td>
	<td>{{ ($somaPeso) }}</td>
	<td>R$ {{ moeda($somaFrete) }}</td>

</tfoot>

</table>


@endsection
