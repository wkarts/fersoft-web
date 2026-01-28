@extends('relatorios.default')
@section('content')

@if($data_inicial && $data_final)
<p>Periodo: {{$data_inicial}} - {{$data_final}}</p>
@endif

@if($assessor != null)
<p>Assessor: {{ $assessor->razao_social }}</p>
@endif

<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		@if($assessor == null)
		<th width="25%" class="text-left">Assessor</th>
		@endif
		<th width="25%" class="text-left">Data da Venda</th>
		<th width="25%" class="text-left">Valor da Venda</th>
		<th width="25%" class="text-left">Valor da Comissão</th>
	</tr>
</thead>
@php
$somaVendas = 0;
$somaComissao = 0;
$assessor_id = $data[0]->assessor_id;
$somaAssessorComissao = 0;
$somaAssessorVenda = 0;
@endphp
@foreach($data as $key => $item)

@php
$somaVendas += $item->venda->valor_total;
$somaComissao += $item->valor;
if($item->assessor_id == $assessor_id){
	$somaAssessorVenda += $item->venda->valor_total;
	$somaAssessorComissao += $item->valor;
}else{
	$somaAssessorVenda = $item->venda->valor_total;
	$somaAssessorComissao = $item->valor;
}
@endphp

<tr class="@if($key%2 == 0) pure-table-odd @endif">
	@if($assessor == null)
	<td>{{ $item->assessor->razao_social }}</td>
	@endif
	<td>{{\Carbon\Carbon::parse($item->venda->created_at)->format('d/m/Y H:i')}}</td>
	<td>{{ moeda($item->venda->valor_total) }}</td>
	<td>{{ moeda($item->valor) }}</td>

	
</tr>

@if($assessor == null)
@isset($data[$key+1])
@if($data[$key+1]->assessor_id != $assessor_id)
<tr style="background: #1BC5BD">
	<td>Total de venda</td>
	<td>{{ moeda($somaAssessorVenda) }}</td>
	<td>Total de comissão</td>
	<td>{{ moeda($somaAssessorComissao) }}</td>

</tr>
@endif
@else
<tr style="background: #1BC5BD">
	<td>Total de venda</td>
	<td>{{ moeda($somaAssessorVenda) }}</td>
	<td>Total de comissão</td>
	<td>{{ moeda($somaAssessorComissao) }}</td>
</tr>
@endif
@endif

@php
	$assessor_id = $item->assessor_id;
@endphp

@endforeach
</table>

<table class="table-sm table-borderless"
style="border-bottom: 1px solid rgb(206, 206, 206); margin-bottom:10px;  width: 100%;">
<thead>
	<tr>
		<td>Somatório de vendas:</td>
		<td>R$ {{ moeda($somaVendas) }}</td>
		<td>Somatório de comissão:</td>
		<td>R$ {{ moeda($somaComissao) }}</td>
	</tr>
</thead>
</table>
@endsection
