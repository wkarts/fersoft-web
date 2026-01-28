@extends('relatorios.default')
@section('content')

@if($data_inicial && $data_final)
<p>Periodo: {{$data_inicial}} - {{$data_final}}</p>
@endif

@if($funcionario != 'null')
<p>Funcionario: <strong>{{$funcionario}}</strong></p>
@endif

@if($produto != 'null')
<p>Produto: <strong>{{$produto}}</strong></p>
@endif

<p>total de registros: <strong>{{ sizeof($comissoes) }}</strong></p>

<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<th width="45%" class="text-left">Data</th>
		<th width="15%" class="text-left">Valor da comissão</th>
		<th width="15%" class="text-left">Valor da venda</th>
		<th width="15%" class="text-left">Tipo</th>
		<th width="15%" class="text-left">Funcionário</th>

	</tr>
</thead>
@php
$somaComissao = 0;
$somaVendas = 0;
@endphp
@foreach($comissoes as $key => $c)

<tr class="@if($key%2 == 0) pure-table-odd @endif">
	<td>{{ \Carbon\Carbon::parse($c->created_at)->format('d/m/Y H:i:s')}}</td>
	<td>{{number_format($c->valor, 2, ',', '.')}}</td>
	<td>{{number_format($c->valor_total_venda, 2, ',', '.')}}</td>
	<td>{{ $c->tipo }}</td>

	<td>{{$c->funcionario}}</td>

</tr>
@php
$somaComissao += $c->valor;
$somaVendas += $c->valor_total_venda;
@endphp
@endforeach
</table>

<table style="width: 100%;">
	<tbody>
		<tr class="text-left">
			<th width="25%">Soma comissão</th>
			<th width="25%"><strong>R$ {{number_format($somaComissao, 2, ',', '.')}}</strong></th>
			<th width="25%">Soma vendas</th>
			<th width="25%"><strong>R$ {{number_format($somaVendas, 2, ',', '.')}}</strong></th>
		</tr>
	</tbody>
</table>

@endsection
